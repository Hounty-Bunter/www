import { NextResponse, type NextRequest } from 'next/server';

const getBaseUrl = (req: NextRequest) =>
  process.env.API_BASE_URL || req.nextUrl.origin || 'http://localhost:5000';

export async function GET(req: NextRequest) {
  const authHeader = req.headers.get('authorization') || req.headers.get('Authorization');
  if (!authHeader) {
    return NextResponse.json({ msg: 'Authorization header is required', status: 401 }, { status: 401 });
  }

  const base = getBaseUrl(req);
  const backendUrl = `${base}/user/me`;

  try {
    const res = await fetch(backendUrl, {
      method: 'GET',
      headers: { Authorization: authHeader },
      cache: 'no-store',
    });
    const data = await res.json().catch(() => null);
    return NextResponse.json(data ?? { status: res.status }, { status: res.status });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}
