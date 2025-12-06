from flask import Flask, jsonify, request

app = Flask(__name__)


@app.route("/")
def index():
    return "API works", 200


@app.route("/login", methods=["POST"])
def login():
    payload = request.get_json(force=True, silent=True) or {}
    username = payload.get("username")
    password = payload.get("password")

    # Simple demo check; replace with real auth as needed.
    if username == "admin" and password == "password123":
        return jsonify({"msg": "ok", "status": 200, "token": "TOKEN"}), 200

    return jsonify({"msg": "wrong u p", "status": 401, "token": None}), 401


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
