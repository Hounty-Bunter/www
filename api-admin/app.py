import os
from datetime import datetime, timedelta

import bcrypt
import jwt
import mysql.connector
from flask import Flask, jsonify, request

app = Flask(__name__)

DB_HOST = os.getenv("DB_HOST", "localhost")
DB_USER = os.getenv("DB_USER", "hounty")
DB_PASSWORD = os.getenv("DB_PASSWORD", "thebat1939")
DB_NAME = os.getenv("DB_NAME", "myapp")
JWT_SECRET = os.getenv("JWT_SECRET", "change-me")
JWT_EXP_HOURS = int(os.getenv("JWT_EXP_HOURS", "1"))


def get_db_conn():
  return mysql.connector.connect(
      host=DB_HOST,
      user=DB_USER,
      password=DB_PASSWORD,
      database=DB_NAME,
      auth_plugin="mysql_native_password",
  )


@app.route("/")
def index():
  return "API works", 200


@app.route("/login", methods=["POST"])
def login():
  payload = request.get_json(force=True, silent=True) or {}
  username = str(payload.get("username", "")).strip()
  password = payload.get("password") or ""

  if not username or not password:
    return jsonify({"msg": "Username and password are required", "status": 400}), 400

  try:
    conn = get_db_conn()
    cur = conn.cursor(dictionary=True)
    cur.execute("SELECT id, username, password FROM users WHERE username = %s LIMIT 1", (username,))
    row = cur.fetchone()
  except mysql.connector.Error as exc:
    app.logger.error("DB error: %s", exc)
    return jsonify({"msg": "Database error", "status": 500}), 500
  finally:
    try:
      if cur:
        cur.close()
      if conn:
        conn.close()
    except Exception:
      pass

  if not row:
    return jsonify({"msg": "Invalid credentials", "status": 401}), 401

  stored_hash = row.get("password") or ""
  if not bcrypt.checkpw(password.encode("utf-8"), stored_hash.encode("utf-8")):
    return jsonify({"msg": "Invalid credentials", "status": 401}), 401

  exp = datetime.utcnow() + timedelta(hours=JWT_EXP_HOURS)
  token = jwt.encode({"user_id": row["id"], "username": row["username"], "exp": exp}, JWT_SECRET, algorithm="HS256")

  return jsonify({"msg": "ok", "status": 200, "token": token}), 200


if __name__ == "__main__":
  app.run(host="127.0.0.1", port=5000, debug=True)
