'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';

type User = {
  id: number;
  username: string;
  email: string;
  profile_picture: string;
  bio: string;
  is_admin: number;
  created_at: string;
  updated_at: string;
};

export default function UsersPage() {
  const router = useRouter();
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [deletingId, setDeletingId] = useState<number | null>(null);

  useEffect(() => {
    const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
    if (!token) {
      router.push('/');
      return;
    }

    const fetchUsers = async () => {
      try {
        const res = await fetch('/api/users', {
          headers: { Authorization: `Bearer ${token}` },
          cache: 'no-store',
        });

        if (res.status === 200) {
          const data = (await res.json()) as { status?: number; users?: User[] } | User[];
          const list = Array.isArray(data) ? data : data?.users;
          setUsers(list || []);
        } else if (res.status === 401) {
          router.push('/');
          return;
        } else {
          const data = (await res.json().catch(() => null)) as { msg?: string } | null;
          setError(data?.msg || `Failed to load users (status ${res.status})`);
        }
      } catch (err) {
        setError('Connection error');
      } finally {
        setLoading(false);
      }
    };

    fetchUsers();
  }, [router]);

  const handleDelete = async (userId: number) => {
    const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
    if (!token) {
      router.push('/');
      return;
    }

    const confirmed = typeof window !== 'undefined' ? window.confirm('Are you sure you want to delete this user?') : false;
    if (!confirmed) return;

    setError('');
    setDeletingId(userId);
    try {
      const res = await fetch(`/api/user/${userId}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${token}` },
      });
      const data = (await res.json().catch(() => null)) as { msg?: string; status?: number } | null;
      if (res.status === 200) {
        setUsers((prev) => prev.filter((u) => u.id !== userId));
      } else if (res.status === 401) {
        router.push('/');
        return;
      } else {
        setError(data?.msg || `Failed to delete user (status ${res.status})`);
      }
    } catch (err) {
      setError('Connection error');
    } finally {
      setDeletingId(null);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-black via-neutral-950 to-neutral-900 px-6 text-amber-50">
      <div className="w-full max-w-5xl rounded-3xl border border-amber-500/30 bg-black/70 p-10 shadow-[0_25px_70px_rgba(0,0,0,0.45)] ring-1 ring-white/5">
        <div className="mb-6 flex items-center justify-between">
          <div>
            <p className="text-xs uppercase tracking-[0.22em] text-amber-400">Panel</p>
            <h1 className="mt-1 text-3xl font-bold text-white">Users</h1>
          </div>
          <Link
            href="/panel"
            className="rounded-full border border-amber-400/40 bg-amber-500/10 px-4 py-2 text-xs font-semibold text-amber-100 transition hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-400/20"
          >
            Back to panel
          </Link>
        </div>

        {loading && <p className="text-sm text-amber-100/80">Loading users…</p>}

        {!loading && error && (
          <div className="rounded-lg border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-100">
            {error}
          </div>
        )}

        {!loading && !error && (
          <div className="overflow-hidden rounded-xl border border-amber-500/30 bg-white/5">
            <table className="min-w-full text-left text-sm">
              <thead className="bg-amber-500/10 text-amber-100">
                <tr>
                  <th className="px-4 py-3">ID</th>
                  <th className="px-4 py-3">Username</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Admin</th>
                  <th className="px-4 py-3">Created</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-amber-500/20 text-amber-50/90">
                {users.length === 0 && (
                  <tr>
                    <td colSpan={6} className="px-4 py-4 text-center text-amber-100/70">
                      No users found.
                    </td>
                  </tr>
                )}
                {users.map((u) => (
                  <tr key={u.id} className="hover:bg-amber-500/5">
                    <td className="px-4 py-3">{u.id}</td>
                    <td className="px-4 py-3">
                      <Link
                        href={`/user/${u.id}`}
                        className="text-amber-200 underline decoration-amber-400/50 underline-offset-4 hover:text-amber-100"
                      >
                        {u.username}
                      </Link>
                    </td>
                    <td className="px-4 py-3">{u.email}</td>
                    <td className="px-4 py-3">{u.is_admin ? 'Yes' : 'No'}</td>
                    <td className="px-4 py-3">{new Date(u.created_at).toLocaleString()}</td>
                    <td className="px-4 py-3 text-right">
                      <div className="flex justify-end gap-2">
                        <Link
                          href={`/user/${u.id}`}
                          className="rounded-full border border-amber-400/40 px-3 py-1 text-xs font-semibold text-amber-100 transition hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-400/20"
                        >
                          Edit
                        </Link>
                        <button
                          type="button"
                          onClick={() => handleDelete(u.id)}
                          disabled={deletingId === u.id}
                          className="rounded-full border border-red-400/40 px-3 py-1 text-xs font-semibold text-red-100 transition hover:-translate-y-0.5 hover:border-red-300 hover:bg-red-500/20 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                          {deletingId === u.id ? 'Deleting…' : 'Delete'}
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
