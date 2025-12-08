import { NextResponse, type NextRequest } from 'next/server';

const getBaseUrl = (req: NextRequest) =>
  process.env.API_BASE_URL || req.nextUrl.origin || 'http://localhost:5000';

export async function POST(req: NextRequest) {
  const base = getBaseUrl(req);
  const backendUrl = `${base}/login`;

  const body = await req.json().catch(() => ({}));

  try {
    const res = await fetch(backendUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
      cache: 'no-store',
    });
    const data = await res.json().catch(() => null);
    return NextResponse.json(data ?? { status: res.status }, { status: res.status });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}
