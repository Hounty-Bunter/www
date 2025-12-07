'use client';

import Link from 'next/link';

export default function ServerStatusPage() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-black via-neutral-950 to-neutral-900 px-6 text-amber-50">
      <div className="w-full max-w-4xl rounded-3xl border border-amber-500/30 bg-black/70 p-10 shadow-[0_25px_70px_rgba(0,0,0,0.45)] ring-1 ring-white/5">
        <div className="mb-6 flex items-center justify-between">
          <div>
            <p className="text-xs uppercase tracking-[0.22em] text-amber-400">Panel</p>
            <h1 className="mt-1 text-3xl font-bold text-white">Server status</h1>
          </div>
          <Link
            href="/panel"
            className="rounded-full border border-amber-400/40 bg-amber-500/10 px-4 py-2 text-xs font-semibold text-amber-100 transition hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-400/20"
          >
            Back to panel
          </Link>
        </div>

        <p className="text-sm text-amber-100/80">Server-status page</p>
      </div>
    </div>
  );
}

