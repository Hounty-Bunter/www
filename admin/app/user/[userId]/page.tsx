'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';

type User = {
  id: number;
  username: string;
  email: string;
  is_admin?: number;
  created_at: string;
  updated_at: string;
};

export default function UserDetail() {
  const router = useRouter();
  const params = useParams<{ userId?: string }>();
  const userId = params?.userId ? String(params.userId) : '';
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
    if (!token) {
      router.push('/');
      return;
    }

    const fetchUser = async () => {
      try {
        const res = await fetch(`/api/user/${params.userId}`, {
          headers: { Authorization: `Bearer ${token}` },
          cache: 'no-store',
        });

        if (res.status === 200) {
          const data = (await res.json()) as { user?: User };
          setUser(data?.user ?? null);
        } else if (res.status === 401) {
          router.push('/');
          return;
        } else {
          const data = (await res.json().catch(() => null)) as { msg?: string } | null;
          setError(data?.msg || `Failed to load user (status ${res.status})`);
        }
      } catch (err) {
        setError('Connection error');
      } finally {
        setLoading(false);
      }
    };
    if (userId) fetchUser();
  }, [router, userId]);

  const formatDate = (value?: string) => {
    if (!value) return '—';
    const date = new Date(value);
    return isNaN(date.getTime()) ? value : date.toLocaleString();
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-black via-neutral-950 to-neutral-900 px-6 text-amber-50">
      <div className="w-full max-w-4xl rounded-3xl border border-amber-500/30 bg-black/70 p-10 shadow-[0_25px_70px_rgba(0,0,0,0.45)] ring-1 ring-white/5">
        <div className="mb-8 flex items-center justify-between">
          <div>
            <p className="text-xs uppercase tracking-[0.22em] text-amber-400">Profile</p>
            <h1 className="mt-1 text-3xl font-bold text-white">User #{userId || '?'}</h1>
          </div>
          <Link
            href="/panel/users"
            className="rounded-full border border-amber-400/40 bg-amber-500/10 px-4 py-2 text-xs font-semibold text-amber-100 transition hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-400/20"
          >
            Back to users
          </Link>
        </div>

        {loading && <p className="text-sm text-amber-100/80">Loading user…</p>}

        {!loading && error && (
          <div className="rounded-lg border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-100">
            {error}
          </div>
        )}

        {!loading && !error && user && (
          <div className="grid gap-6 rounded-2xl border border-amber-500/30 bg-white/5 p-6 sm:grid-cols-2">
            <div className="space-y-3">
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-amber-300">Username</p>
                <p className="text-lg font-semibold text-white">{user.username}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-amber-300">Email</p>
                <p className="text-lg font-semibold text-white">{user.email}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-amber-300">Admin</p>
                <p className="text-lg font-semibold text-white">{user.is_admin ? 'Yes' : 'No'}</p>
              </div>
            </div>
            <div className="space-y-3">
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-amber-300">Created</p>
                <p className="text-lg font-semibold text-white">{formatDate(user.created_at)}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-amber-300">Updated</p>
                <p className="text-lg font-semibold text-white">{formatDate(user.updated_at)}</p>
              </div>
            </div>
          </div>
        )}

        {!loading && !error && !user && (
          <div className="rounded-lg border border-amber-400/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
            User not found.
          </div>
        )}
      </div>
    </div>
  );
}
