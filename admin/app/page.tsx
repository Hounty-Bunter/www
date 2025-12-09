'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';

type LoginResponse = {
  status?: number;
  token?: string;
  msg?: string;
};

export default function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await fetch('/api/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ username, password }),
      });

      const data = (await response.json()) as LoginResponse;

      if (data.status === 200 && data.token) {
        localStorage.setItem('token', data.token);
        router.push('/panel');
      } else {
        setError(data.msg || 'Login failed');
      }
    } catch (err) {
      setError('Connection error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
     <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-zinc-900 via-zinc-800 to-black px-4 text-zinc-50">
      <div className="absolute left-1/2 top-12 -translate-x-1/2">
        <div className="flex items-center gap-2 rounded-full px-4 py-2 text-base font-semibold shadow-lg ring-1 ring-white/15">
          <span className="text-white">Hounty</span>
          <span className="rounded-md bg-amber-400 px-3 py-1 text-black">Bunter</span>
        </div>
      </div>

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
      </div>
    </div>
  );
}
