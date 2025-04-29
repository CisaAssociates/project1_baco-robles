from flask import Flask, request, jsonify
import threading
import cv2
import face_recognition
import mysql.connector
import numpy as np
from PIL import Image
import io
import os
import time
import sys
import traceback

# Force print to flush
print = lambda *args, **kwargs: __builtins__.print(*args, **kwargs, flush=True)

app = Flask(__name__)

# DB Connection
def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="rfid_db"
    )

# Global variables
current_uid = None
known_face_encoding = None
known_user_name = None

def decode_face_data(binary_data):
    try:
        img = Image.open(io.BytesIO(binary_data)).convert("RGB")
        img = img.resize((640, 480))
        img_rgb = np.array(img, dtype=np.uint8)
        img_rgb = np.ascontiguousarray(img_rgb)

        print(f"🖼️ Decoded image shape: {img_rgb.shape}, dtype: {img_rgb.dtype}")
        return img_rgb if img_rgb.shape[2] == 3 else None
    except Exception as e:
        print(f"❌ Error decoding face image: {e}")
        return None

def encode_face(image):
    try:
        if image is None:
            print("❌ Image is None.")
            return None

        if image.dtype != np.uint8 or len(image.shape) != 3 or image.shape[2] != 3:
            print(f"❌ Invalid image type: dtype={image.dtype}, shape={image.shape}")
            return None

        if not image.flags['C_CONTIGUOUS']:
            image = np.ascontiguousarray(image)

        print(f"🔍 Running face detection on image with shape: {image.shape}")
        face_locations = face_recognition.face_locations(image)
        print(f"✅ Found {len(face_locations)} face(s) in image.")

        encodings = face_recognition.face_encodings(image, face_locations)
        return encodings[0] if encodings else None

    except Exception as e:
        print(f"❌ Exception in encode_face: {e}")
        return None

@app.route("/check_status", methods=["GET"])
def check_status():
    global current_uid, known_face_encoding, known_user_name

    uid = request.args.get("uid")
    current_uid = uid
    print(f"✅ Received UID: {uid}")

    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT name, face_data FROM users WHERE rfid_tag = %s", (uid,))
    result = cursor.fetchone()

    if result:
        name, face_data = result
        known_user_name = name
        face_image = decode_face_data(face_data)

        if face_image is None:
            status = "error"
            message = "Error: Unable to decode face image."
        else:
            known_face_encoding = encode_face(face_image)
            if known_face_encoding is None:
                status = "error"
                message = "Error: No face found in image."
                os.makedirs("debug", exist_ok=True)
                Image.fromarray(face_image).save(f"debug/{uid}_debug.jpg")
            else:
                status = "authorized"
                message = f"Welcome, {name}!"
    else:
        known_user_name = None
        known_face_encoding = None
        status = "unauthorized"
        message = "UID not recognized."

    cursor.close()
    conn.close()

    return jsonify({"status": status, "uid": uid, "message": message})

def run_flask():
    app.run(host="0.0.0.0", port=5000)

flask_thread = threading.Thread(target=run_flask)
flask_thread.daemon = True
flask_thread.start()

ip_camera_url = "http://192.168.254.189:81/stream"
cap = cv2.VideoCapture(ip_camera_url)
cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

try:
    if not cap.isOpened():
        raise Exception("Could not open video stream.")

    print("📹 Streaming started...")

    while True:
        try:
            ret, frame = cap.read()
            if not ret or frame is None:
                print("❌ Frame not received. Attempting reconnect...")
                cap.release()
                time.sleep(2)
                cap = cv2.VideoCapture(ip_camera_url)
                cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
                continue

            rgb_small_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

            if known_face_encoding is not None:
                face_locations = face_recognition.face_locations(rgb_small_frame)
                face_encodings = face_recognition.face_encodings(rgb_small_frame, face_locations)

                for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
                    match = face_recognition.compare_faces([known_face_encoding], face_encoding)[0]
                    name_display = known_user_name if match else "Unknown"

                    cv2.rectangle(frame, (left, top), (right, bottom), (0, 255, 0) if match else (0, 0, 255), 2)
                    cv2.putText(frame, name_display, (left, top - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255,255,255), 2)

                    if match and current_uid:
                        conn = get_db_connection()
                        cursor = conn.cursor()
                        cursor.execute("SELECT id FROM users WHERE rfid_tag = %s", (current_uid,))
                        user_result = cursor.fetchone()
                        if user_result:
                            user_id = user_result[0]
                            cursor.execute("""
                                SELECT status, timestamp FROM attendance_logs
                                WHERE user_id = %s AND DATE(timestamp) = CURDATE()
                                ORDER BY timestamp DESC LIMIT 1
                            """, (user_id,))
                            last_log = cursor.fetchone()

                            if last_log:
                                last_status, last_time = last_log
                                cursor.execute("SELECT TIMESTAMPDIFF(SECOND, %s, NOW())", (last_time,))
                                time_diff = cursor.fetchone()[0]

                                if time_diff < 60:
                                    print(f"⏱️ Skipped log for {known_user_name}, last entry {time_diff} sec ago.")
                                    cursor.close()
                                    conn.close()
                                    continue

                                new_status = 'out' if last_status == 'in' else 'in'
                            else:
                                new_status = 'in'

                            cursor.execute(
                                "INSERT INTO attendance_logs (user_id, timestamp, status) VALUES (%s, NOW(), %s)",
                                (user_id, new_status)
                            )
                            conn.commit()
                            print(f"✅ {new_status.upper()} recorded for {known_user_name} (UID: {current_uid})")

                        cursor.close()
                        conn.close()

            # Display if GUI available
            if os.name != 'posix' or os.environ.get('DISPLAY'):
                cv2.imshow("Face Verification", frame)
                if cv2.waitKey(1) & 0xFF == ord('q'):
                    break

        except Exception as e:
            print("‼️ Error inside loop:", e)
            print(traceback.format_exc())
            time.sleep(2)

except KeyboardInterrupt:
    print("\n🛑 Keyboard interrupt received. Exiting gracefully...")

except Exception as e:
    print("❌ FATAL ERROR:", e)
    print(traceback.format_exc())

finally:
    print("🔄 Cleaning up...")
    cap.release()
    cv2.destroyAllWindows()
    sys.exit(0)
