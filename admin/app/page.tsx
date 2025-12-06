'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';

type LoginResponse = {
  status?: number;
  token?: string;
  msg?: string;
};

function TextField({
  label,
  type = 'text',
  value,
  onChange,
}: {
  label: string;
  type?: string;
  value: string;
  onChange: (value: string) => void;
}) {
  return (
    <label className="block space-y-1">
      <span className="text-sm font-semibold text-slate-100/90">{label}</span>
      <input
        className="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white shadow-inner outline-none transition focus:-translate-y-[1px] focus:border-cyan-300 focus:ring-2 focus:ring-cyan-200/50 placeholder:text-white/50"
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        required
      />
    </label>
  );
}

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
    <main className="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-slate-900 via-slate-950 to-black px-4">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(59,130,246,0.2),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(34,211,238,0.15),transparent_25%),radial-gradient(circle_at_50%_80%,rgba(236,72,153,0.08),transparent_30%)]" />

      <section className="relative z-10 grid w-full max-w-5xl grid-cols-1 gap-8 rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-lg md:grid-cols-2 md:p-10">
        <div className="flex flex-col justify-between space-y-6">
          <div className="space-y-3">
            <p className="inline-flex w-fit items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-100/80">Admin</p>
            <h1 className="text-3xl font-bold text-white md:text-4xl">Control Panel Access</h1>
            <p className="text-sm text-slate-100/80 md:text-base">
              Sign in with your admin credentials to continue. Tokens are stored locally so you can hop straight into the panel next time.
            </p>
          </div>

          <div className="hidden rounded-xl border border-white/10 bg-white/5 p-4 text-sm text-slate-100/70 md:block">
            <p className="font-semibold text-white">Need help?</p>
            <p className="mt-1">Reach out to your workspace owner to reset your access or rotate keys.</p>
          </div>
        </div>

        <form className="space-y-5 rounded-xl border border-white/10 bg-slate-900/50 p-6 shadow-xl" onSubmit={handleSubmit}>
          <div className="flex items-center justify-between">
            <p className="text-base font-semibold text-white">Sign in</p>
            <span className="text-xs uppercase tracking-widest text-cyan-100/70">Secure</span>
          </div>

          <TextField label="Username" value={username} onChange={setUsername} />
          <TextField label="Password" type="password" value={password} onChange={setPassword} />

          {error && <p className="rounded-md border border-red-400/40 bg-red-500/10 px-3 py-2 text-sm text-red-100">{error}</p>}

          <button
            className="relative flex w-full items-center justify-center rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition focus:outline-none focus:ring-2 focus:ring-cyan-300 focus:ring-offset-2 focus:ring-offset-slate-950 disabled:cursor-not-allowed disabled:from-slate-600 disabled:to-slate-700"
            type="submit"
            disabled={loading}
          >
            {loading ? 'Signing in…' : 'Enter Dashboard'}
          </button>
        </form>
      </section>
    </main>
  );
}
