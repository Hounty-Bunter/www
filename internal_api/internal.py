from flask import Flask, jsonify
import mysql.connector
from mysql.connector import pooling, Error


app = Flask(__name__)

dbconfig = {
    "host": "localhost",
    "user": "hounty",
    "password": "thebat1939",
    "database": "myapp",
    "autocommit": True,
}

pool = pooling.MySQLConnectionPool(pool_name="user_api_pool", pool_size=5, **dbconfig)


def fetch_user(user_id: int):
    conn = pool.get_connection()
    try:
        with conn.cursor(dictionary=True) as cursor:
            cursor.execute(
                """
                SELECT id, username, email, profile_picture, bio, google_id,
                       created_at, updated_at
                FROM users WHERE id = %s
                """,
                (user_id,),
            )
            return cursor.fetchone()
    finally:
        conn.close()


@app.route("/api/user/<int:user_id>", methods=["GET"])
def get_user(user_id: int):
    try:
        user = fetch_user(user_id)
    except Error as exc:
        return jsonify({"error": "Database error", "details": str(exc)}), 500

    if not user:
        return jsonify({"error": "User not found"}), 404

    return jsonify(user)


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=8000, debug=False)
