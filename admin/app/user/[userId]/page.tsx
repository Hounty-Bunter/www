'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';

type User = {
  id: number;
  username: string;
  email: string;
  name?: string;
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
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState<{ username: string; email: string; name: string; is_admin: boolean }>({
    username: '',
    email: '',
    name: '',
    is_admin: false,
  });

  useEffect(() => {
    const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
    if (!token) {
      router.push('/');
      return;
    }

    const fetchUser = async () => {
      try {
        const res = await fetch(`/api/user/${userId}`, {
          headers: { Authorization: `Bearer ${token}` },
          cache: 'no-store',
        });

        if (res.status === 200) {
          const data = (await res.json()) as { user?: User };
          const incoming = data?.user ?? null;
          setUser(incoming);
          if (incoming) {
            setForm({
              username: incoming.username ?? '',
              email: incoming.email ?? '',
              name: incoming.name ?? '',
              is_admin: !!incoming.is_admin,
            });
          }
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

  const handleChange = (key: keyof typeof form, value: string | boolean) => {
    setForm((prev) => ({ ...prev, [key]: value }));
  };

  const handleUpdate = async () => {
    const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
    if (!token) {
      router.push('/');
      return;
    }
    if (!userId) {
      setError('Invalid user id');
      return;
    }
    setSaving(true);
    setError('');
    try {
      const res = await fetch(`/api/user/${userId}`, {
        method: 'PATCH',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          username: form.username,
          email: form.email,
          name: form.name,
          is_admin: form.is_admin ? 1 : 0,
        }),
      });
      const data = (await res.json().catch(() => null)) as { user?: User; msg?: string; status?: number } | null;
      if (res.status === 200) {
        const updated = (data?.user as User | undefined) ?? null;
        setUser(updated);
        if (updated) {
          setForm({
            username: updated.username ?? '',
            email: updated.email ?? '',
            name: updated.name ?? '',
            is_admin: !!updated.is_admin,
          });
        }
      } else if (res.status === 401) {
        router.push('/');
        return;
      } else {
        setError(data?.msg || `Failed to update user (status ${res.status})`);
      }
    } catch (err) {
      setError('Connection error');
    } finally {
      setSaving(false);
    }
  };

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
          <div className="space-y-6 rounded-2xl border border-amber-500/30 bg-white/5 p-6">
            <div className="grid gap-4 sm:grid-cols-2">
              <label className="block space-y-2">
                <span className="text-xs uppercase tracking-[0.18em] text-amber-300">Username</span>
                <input
                  className="w-full rounded-lg border border-amber-400/30 bg-black/40 px-3 py-2 text-sm text-white outline-none focus:border-amber-300 focus:ring-2 focus:ring-amber-300/40"
                  value={form.username}
                  onChange={(e) => handleChange('username', e.target.value)}
                  placeholder="Username"
                />
              </label>
              <label className="block space-y-2">
                <span className="text-xs uppercase tracking-[0.18em] text-amber-300">Email</span>
                <input
                  className="w-full rounded-lg border border-amber-400/30 bg-black/40 px-3 py-2 text-sm text-white outline-none focus:border-amber-300 focus:ring-2 focus:ring-amber-300/40"
                  value={form.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  placeholder="Email"
                />
              </label>
              <label className="block space-y-2">
                <span className="text-xs uppercase tracking-[0.18em] text-amber-300">Name</span>
                <input
                  className="w-full rounded-lg border border-amber-400/30 bg-black/40 px-3 py-2 text-sm text-white outline-none focus:border-amber-300 focus:ring-2 focus:ring-amber-300/40"
                  value={form.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  placeholder="Name"
                />
              </label>
              <label className="flex items-center gap-3 rounded-lg border border-amber-400/30 bg-black/40 px-3 py-2 text-sm text-white">
                <input
                  type="checkbox"
                  checked={form.is_admin}
                  onChange={(e) => handleChange('is_admin', e.target.checked)}
                  className="h-4 w-4 accent-amber-400"
                />
                <span className="text-xs uppercase tracking-[0.18em] text-amber-300">Admin</span>
              </label>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-amber-300">Created</p>
                <p className="text-sm font-semibold text-white">{formatDate(user.created_at)}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-amber-300">Updated</p>
                <p className="text-sm font-semibold text-white">{formatDate(user.updated_at)}</p>
              </div>
            </div>

            <div className="flex justify-end">
              <button
                type="button"
                onClick={handleUpdate}
                disabled={saving}
                className="rounded-full border border-amber-400/40 bg-amber-500/20 px-5 py-2 text-sm font-semibold text-amber-50 transition hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-400/30 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {saving ? 'Updating…' : 'Update user'}
              </button>
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
