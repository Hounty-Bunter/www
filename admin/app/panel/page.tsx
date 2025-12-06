export default function PanelPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-amber-50 to-white px-6 text-center text-zinc-900">
      <div className="max-w-xl rounded-3xl border border-amber-100 bg-white p-12 shadow-xl">
        <p className="text-sm uppercase tracking-[0.2em] text-amber-500">
          Panel
        </p>
        <h1 className="mt-4 text-4xl font-semibold">Welcome</h1>
        <p className="mt-4 text-lg text-zinc-600">
          You have successfully logged in. This is the protected panel area.
        </p>
      </div>
    </div>
  );
}
