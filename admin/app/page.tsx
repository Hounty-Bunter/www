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
    <main className="flex min-h-screen items-center justify-center bg-gray-100 px-4">
      <section className="w-full max-w-md rounded-xl bg-white p-8 shadow-lg ring-1 ring-gray-200">
        <div className="mb-6 space-y-1">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Admin</p>
          <h1 className="text-2xl font-semibold text-gray-900">Login</h1>
          <p className="text-sm text-gray-600">Enter your credentials to access the panel.</p>
        </div>

        <form className="space-y-4" onSubmit={handleSubmit}>
          <label className="block space-y-1">
            <span className="text-sm font-medium text-gray-700">Username</span>
            <input
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
            />
          </label>

          <label className="block space-y-1">
            <span className="text-sm font-medium text-gray-700">Password</span>
            <input
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </label>

          {error && <p className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-200">{error}</p>}

          <button
            className="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-blue-300"
            type="submit"
            disabled={loading}
          >
            {loading ? 'Signing in…' : 'Login'}
          </button>
        </form>
      </section>
    </main>
  );
}
