from flask import Flask, request
import mysql.connector

app = Flask(__name__)

def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="rfid_db"
    )

@app.route('/receive_uid', methods=['GET'])
def receive_uid():
    uid = request.args.get('uid')

    if not uid:
        return "UID missing", 400

    try:
        conn = get_db_connection()
        cursor = conn.cursor()

        # Find latest user without RFID and assign the UID
        cursor.execute("UPDATE users SET rfid_tag = %s WHERE rfid_tag IS NULL ORDER BY id DESC LIMIT 1", (uid,))
        conn.commit()

        if cursor.rowcount > 0:
            return f"UID {uid} successfully assigned to the latest user.", 200
        else:
            return "No user waiting for RFID tag.", 404
    except Exception as e:
        return f"Database error: {str(e)}", 500
    finally:
        cursor.close()
        conn.close()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
