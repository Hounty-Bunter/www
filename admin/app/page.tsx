"use client";

import { FormEvent, useMemo, useState } from "react";
import { useRouter } from "next/navigation";

const getLoginEndpoint = () => {
  // Use env-provided API base when available; otherwise default to same-origin path
  const envBase = process.env.NEXT_PUBLIC_API_BASE_URL;
  if (envBase) {
    return `${envBase.replace(/\/$/, "")}/login`;
  }
  if (typeof window !== "undefined") {
    return `${window.location.origin}/login`;
  }
  return "/login";
};

export default function LoginPage() {
  const router = useRouter();
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const loginEndpoint = useMemo(() => getLoginEndpoint(), []);

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(loginEndpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ username, password }),
      });

      const data = await response.json();

      if (response.ok && data?.status === 200 && data?.msg === "ok") {
        if (data?.token) {
          localStorage.setItem("token", data.token);
        }
        router.push("/panel");
        return;
      }

      setError(data?.msg || "wrong u p");
    } catch (err) {
      setError("Unable to reach server. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-zinc-900 via-zinc-800 to-black px-4 text-zinc-50">
      <div className="w-full max-w-md rounded-2xl border border-white/10 bg-white/5 p-10 shadow-2xl backdrop-blur">
        <div className="mb-8 space-y-2 text-center">
          <p className="text-sm uppercase tracking-[0.2em] text-amber-400">
            Admin Access
          </p>
          <h1 className="text-3xl font-semibold leading-tight">Sign in</h1>
          <p className="text-sm text-zinc-400">
            Enter your credentials to continue to the panel.
          </p>
        </div>

        <form className="space-y-5" onSubmit={handleSubmit}>
          <label className="block space-y-2">
            <span className="text-sm text-zinc-200">Username</span>
            <input
              className="w-full rounded-lg border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none ring-amber-400/60 transition focus:border-amber-400/60 focus:ring-2"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              placeholder="Enter username"
              required
              autoComplete="username"
            />
          </label>

          <label className="block space-y-2">
            <span className="text-sm text-zinc-200">Password</span>
            <input
              type="password"
              className="w-full rounded-lg border border-white/10 bg-black/40 px-4 py-3 text-sm text-white outline-none ring-amber-400/60 transition focus:border-amber-400/60 focus:ring-2"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Enter password"
              required
              autoComplete="current-password"
            />
          </label>

          {error ? (
            <p className="text-sm font-medium text-amber-300">{error}</p>
          ) : null}

          <button
            type="submit"
            disabled={loading}
            className="flex w-full items-center justify-center rounded-lg bg-amber-400 px-4 py-3 text-sm font-semibold text-black transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-200 disabled:cursor-not-allowed disabled:bg-amber-200"
          >
            {loading ? "Signing in..." : "Login"}
          </button>
        </form>

        <div className="mt-8 rounded-lg border border-white/5 bg-white/5 p-4 text-xs text-zinc-400">
          <p>Demo credentials:</p>
          <p className="mt-1 font-semibold text-white">admin / password123</p>
        </div>
      </div>
    </div>
  );
}
