export default function PanelPage() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-neutral-950 via-black to-neutral-900 px-6 text-amber-50">
      <div className="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-amber-500/30 bg-neutral-950/70 p-10 shadow-[0_25px_70px_rgba(0,0,0,0.45)] ring-1 ring-white/5">
        <div className="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_20%_20%,rgba(251,191,36,0.06),transparent_35%),radial-gradient(circle_at_80%_0%,rgba(251,191,36,0.08),transparent_35%)]" />

        <div className="relative flex flex-col gap-4 text-left">
          <p className="inline-flex w-fit items-center rounded-full bg-amber-500/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.28em] text-amber-300">
            Panel
          </p>
          <h1 className="text-4xl font-bold text-white md:text-5xl">
            Welcome back, admin.
          </h1>
          <p className="max-w-2xl text-base text-amber-50/80 md:text-lg">
            You&apos;re in the protected area. Monitor activity, manage users, and keep the lights on. Stay sharp.
          </p>

          <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div className="rounded-xl border border-amber-500/30 bg-white/5 p-4 shadow-inner">
              <p className="text-xs uppercase tracking-[0.12em] text-amber-200/80">Status</p>
              <p className="mt-1 text-lg font-semibold text-white">Operational</p>
            </div>
            <div className="rounded-xl border border-amber-500/30 bg-white/5 p-4 shadow-inner">
              <p className="text-xs uppercase tracking-[0.12em] text-amber-200/80">Sessions</p>
              <p className="mt-1 text-lg font-semibold text-white">Active</p>
            </div>
            <div className="rounded-xl border border-amber-500/30 bg-white/5 p-4 shadow-inner">
              <p className="text-xs uppercase tracking-[0.12em] text-amber-200/80">Alerts</p>
              <p className="mt-1 text-lg font-semibold text-amber-300">Clean</p>
            </div>
          </div>

          <button className="mt-6 inline-flex w-fit items-center justify-center rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-black shadow-lg transition hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-black">
            Go to dashboard
          </button>
        </div>
      </div>
    </div>
  );
}
