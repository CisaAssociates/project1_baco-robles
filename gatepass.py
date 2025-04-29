from flask import Flask, request, jsonify
import threading
import cv2
import face_recognition
import mysql.connector
import numpy as np
from PIL import Image
import io
import os

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

@app.route("/check_gatepass", methods=["GET"])
def check_gatepass():
    global current_uid, known_face_encoding, known_user_name

    uid = request.args.get("uid")
    current_uid = uid

    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT name, face_data FROM users WHERE rfid_tag = %s", (uid,))
    result = cursor.fetchone()

    if result:
        name, face_data = result
        known_user_name = name
        face_image = decode_face_data(face_data)

        if face_image is not None:
            known_face_encoding = encode_face(face_image)
            if known_face_encoding is not None:
                message = f"Authorized: {name}"
                status = "authorized"
            else:
                message = "Error: No face detected"
                status = "error"
        else:
            message = "Error decoding image"
            status = "error"
    else:
        known_user_name = None
        known_face_encoding = None
        message = "UID not recognized"
        status = "unauthorized"

    cursor.close()
    conn.close()

    return jsonify({
        "status": status,
        "uid": uid,
        "message": message
    })

def decode_face_data(binary_data):
    try:
        img = Image.open(io.BytesIO(binary_data)).convert("RGB")
        img = img.resize((640, 480))
        img_rgb = np.array(img, dtype=np.uint8)
        return np.ascontiguousarray(img_rgb)
    except Exception as e:
        print(f"❌ Error decoding image: {e}")
        return None

def encode_face(image):
    try:
        if image is None:
            return None
        if image.dtype != np.uint8 or len(image.shape) != 3 or image.shape[2] != 3:
            return None
        if not image.flags['C_CONTIGUOUS']:
            image = np.ascontiguousarray(image)
        encodings = face_recognition.face_encodings(image, face_recognition.face_locations(image))
        return encodings[0] if encodings else None
    except Exception as e:
        print(f"❌ Face encoding error: {e}")
        return None

def run_flask():
    app.run(host="0.0.0.0", port=5000)

flask_thread = threading.Thread(target=run_flask)
flask_thread.daemon = True
flask_thread.start()

# Video stream
ip_camera_url = "http://192.168.254.189:81/stream"
cap = cv2.VideoCapture(ip_camera_url)

if not cap.isOpened():
    print("❌ Failed to open video stream.")
else:
    print("📹 Gatepass stream started...")

    while True:
        ret, frame = cap.read()
        if not ret:
            break

        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

        if known_face_encoding is not None:
            face_locations = face_recognition.face_locations(rgb_frame)
            face_encodings = face_recognition.face_encodings(rgb_frame, face_locations)

            for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
                match = face_recognition.compare_faces([known_face_encoding], face_encoding)[0]

                if match and current_uid:
                    try:
                        conn = get_db_connection()
                        cursor = conn.cursor()

                        cursor.execute("SELECT id, name FROM users WHERE rfid_tag = %s", (current_uid,))
                        user_result = cursor.fetchone()
                        if user_result:
                            user_id, user_name = user_result

                            cursor.execute("""
                                SELECT id FROM gatepass_logs
                                WHERE user_id = %s AND exit_time IS NULL
                                ORDER BY entry_time DESC LIMIT 1
                            """, (user_id,))
                            last_log = cursor.fetchone()

                            if last_log:
                                cursor.execute(
                                    "UPDATE gatepass_logs SET exit_time = NOW() WHERE id = %s",
                                    (last_log[0],)
                                )
                                print(f"🚪 Exit recorded for {user_name}")
                            else:
                                cursor.execute(
                                    "INSERT INTO gatepass_logs (user_id, entry_time) VALUES (%s, NOW())",
                                    (user_id,)
                                )
                                print(f"🚪 Entry recorded for {user_name}")

                            conn.commit()
                        cursor.close()
                        conn.close()
                    except Exception as e:
                        print(f"❌ DB error: {e}")

        cv2.imshow("Gatepass Verification", frame)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    cap.release()
    cv2.destroyAllWindows()
