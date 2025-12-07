import os
from datetime import datetime, timedelta
from functools import wraps

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
JWT_ALG = "HS256"


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
  token = jwt.encode({"user_id": row["id"], "username": row["username"], "exp": exp}, JWT_SECRET, algorithm=JWT_ALG)

  return jsonify({"msg": "ok", "status": 200, "token": token}), 200


def _get_auth_token() -> str | None:
  auth_header = request.headers.get("Authorization", "")
  if auth_header.lower().startswith("bearer "):
    return auth_header.split(" ", 1)[1].strip() or None
  return None


def _decode_token(raw_token):
  if not raw_token:
    return None, (jsonify({"msg": "Missing token", "status": 401}), 401)

  try:
    payload = jwt.decode(raw_token, JWT_SECRET, algorithms=[JWT_ALG])
    user_id = payload.get("user_id")
  except jwt.ExpiredSignatureError:
    return None, (jsonify({"msg": "Token expired", "status": 401}), 401)
  except jwt.InvalidTokenError:
    return None, (jsonify({"msg": "Invalid token", "status": 401}), 401)

  if not user_id:
    return None, (jsonify({"msg": "Invalid token payload", "status": 401}), 401)

  return payload, None


def require_auth(fn):
  @wraps(fn)
  def wrapper(*args, **kwargs):
    raw_token = _get_auth_token()
    payload, error_response = _decode_token(raw_token)
    if error_response:
      return error_response

    kwargs.update({
        "user_id": payload.get("user_id"),
        "username": payload.get("username"),
        "decoded_token": payload,
    })
    return fn(*args, **kwargs)

  return wrapper


@app.route("/user/me", methods=["GET"])
@require_auth
def me(user_id=None, decoded_token=None, username=None):

  try:
    conn = get_db_conn()
    cur = conn.cursor(dictionary=True)
    cur.execute("SELECT id, username, email, created_at, updated_at FROM users WHERE id = %s", (user_id,))
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
    return jsonify({"msg": "User not found", "status": 404}), 404

  return jsonify({"status": 200, "user": row}), 200


@app.route("/user/<int:user_id>", methods=["GET"])
@require_auth
def get_user(user_id=None, decoded_token=None, username=None):
  try:
    conn = get_db_conn()
    cur = conn.cursor(dictionary=True)
    cur.execute("SELECT id, username, email, created_at, updated_at FROM users WHERE id = %s", (user_id,))
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
    return jsonify({"msg": "User not found", "status": 404}), 404

  return jsonify({"status": 200, "user": row}), 200


@app.route("/user/<int:user_id>", methods=["PATCH"])
@require_auth
def update_user(user_id=None, decoded_token=None, username=None):
  try:
    conn = get_db_conn()
    cur = conn.cursor(dictionary=True)
    cur.execute("UPDATE users SET username = %s, email = %s, created_at = %s, updated_at = %s WHERE id = %s", (username, email, created_at, updated_at, user_id))
    conn.commit()
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

  return jsonify({"status": 200, "message": "User updated"}), 200


@app.route("/user/<int:user_id>", methods=["DELETE"])
@require_auth
def delete_user(user_id=None, decoded_token=None, username=None):
  try:
    conn = get_db_conn()
    cur = conn.cursor(dictionary=True)
    cur.execute("DELETE FROM users WHERE id = %s", (user_id,))
    conn.commit()
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

  return jsonify({"status": 200, "message": "User deleted"}), 200

@app.route("/users", methods=["GET"])
@require_auth
def list_users(user_id=None, decoded_token=None, username=None):
  try:
    conn = get_db_conn()
    cur = conn.cursor(dictionary=True)
    # Return all users; alias username as name if a dedicated name column is absent
    cur.execute("SELECT id, username, email, created_at, updated_at, is_admin FROM users")
    users = cur.fetchall() or []
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

  return jsonify({"status": 200, "users": users}), 200


if __name__ == "__main__":
  app.run(host="127.0.0.1", port=5000, debug=True)
