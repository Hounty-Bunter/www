'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';

type User = {
  id: number;
  username: string;
  email: string;
  created_at: string;
  updated_at: string;
};

type MeResponse = {
  status?: number;
  msg?: string;
  user?: User;
};

export default function PanelPage() {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
    if (!token) {
      router.push('/');
      return;
    }
    const fetchUser = async () => {
      // Mirror login page behavior: hit the API via the same /api prefix
      const url = '/api/user/me';

      try {
        const res = await fetch(url, {
          headers: { Authorization: `Bearer ${token}` },
          cache: 'no-store',
        });

        if (res.status === 200) {
          const data = (await res.json()) as MeResponse;
          setUser(data.user ?? null);
        } else if (res.status === 401) {
          router.push('/');
          return;
        } else {
          const data = (await res.json().catch(() => null)) as MeResponse | null;
          setError(data?.msg || `Failed to load profile (status ${res.status})`);
        }
      } catch (err) {
        setError('Connection error');
      } finally {
        setLoading(false);
      }
    };

    fetchUser();
  }, [router]);

  const formatDate = (value?: string) => {
    if (!value) return '—';
    const date = new Date(value);
    return isNaN(date.getTime()) ? value : date.toLocaleString();
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-black via-neutral-950 to-neutral-900 px-6 text-amber-50">
      <div className="w-full max-w-3xl rounded-3xl border border-amber-500/30 bg-black/70 p-10 shadow-[0_25px_70px_rgba(0,0,0,0.45)] ring-1 ring-white/5">
        <div className="mb-6 flex items-center justify-between">
          <div>
            <p className="text-xs uppercase tracking-[0.22em] text-amber-400">Panel</p>
            <h1 className="mt-1 text-3xl font-bold text-white">Account overview</h1>
          </div>
          <span className="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-200">
            {loading ? 'Loading' : user ? 'Authenticated' : 'Guest'}
          </span>
        </div>

        {loading && (
          <p className="text-sm text-amber-100/70">Loading your info…</p>
        )}

        {!loading && error && (
          <div className="rounded-lg border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-100">
            {error}
          </div>
        )}

        {!loading && user && (
          <div className="grid gap-4 md:grid-cols-2">
            <div className="rounded-xl border border-amber-500/30 bg-white/5 p-4 shadow-inner">
              <p className="text-xs uppercase tracking-[0.12em] text-amber-200/80">Username</p>
              <p className="mt-1 text-lg font-semibold text-white">{user.username}</p>
            </div>
            <div className="rounded-xl border border-amber-500/30 bg-white/5 p-4 shadow-inner">
              <p className="text-xs uppercase tracking-[0.12em] text-amber-200/80">Email</p>
              <p className="mt-1 text-lg font-semibold text-white">{user.email}</p>
            </div>
            <div className="rounded-xl border border-amber-500/30 bg-white/5 p-4 shadow-inner">
              <p className="text-xs uppercase tracking-[0.12em] text-amber-200/80">User ID</p>
              <p className="mt-1 text-lg font-semibold text-white">{user.id}</p>
            </div>
            <div className="rounded-xl border border-amber-500/30 bg-white/5 p-4 shadow-inner">
              <p className="text-xs uppercase tracking-[0.12em] text-amber-200/80">Created</p>
              <p className="mt-1 text-lg font-semibold text-white">{formatDate(user.created_at)}</p>
            </div>
            <div className="rounded-xl border border-amber-500/30 bg-white/5 p-4 shadow-inner md:col-span-2">
              <p className="text-xs uppercase tracking-[0.12em] text-amber-200/80">Updated</p>
              <p className="mt-1 text-lg font-semibold text-white">{formatDate(user.updated_at)}</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
