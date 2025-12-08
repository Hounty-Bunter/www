import { NextResponse, type NextRequest } from 'next/server';

const getBaseUrl = (req: NextRequest) =>
  process.env.API_BASE_URL || req.nextUrl.origin || 'http://localhost:5000';

function requireAuth(req: NextRequest) {
  const header = req.headers.get('authorization') || req.headers.get('Authorization');
  if (!header) {
    return { error: NextResponse.json({ msg: 'Authorization header is required', status: 401 }, { status: 401 }) };
  }
  return { header };
}

async function proxy(req: NextRequest, userId: string, init: RequestInit) {
  const base = getBaseUrl(req);
  const candidates = [
    `${base}/api/user/${userId}`, // preferred if backend exposes /api/user/:id
    `${base}/user/${userId}`,     // fallback to plain /user/:id
  ];

  for (const backendUrl of candidates) {
    const res = await fetch(backendUrl, { ...init, cache: 'no-store' });
    const data = await res.json().catch(() => null);
    if (res.status === 404) continue;
    return NextResponse.json(data ?? { status: res.status }, { status: res.status });
  }

  // If every candidate returned 404
  return NextResponse.json({ msg: 'User not found', status: 404 }, { status: 404 });
}

export async function GET(req: NextRequest, { params }: { params: { userId: string } }) {
  const auth = requireAuth(req);
  if ('error' in auth) return auth.error;
  try {
    return await proxy(req, params.userId, { method: 'GET', headers: { Authorization: auth.header! } });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}

export async function PATCH(req: NextRequest, { params }: { params: { userId: string } }) {
  const auth = requireAuth(req);
  if ('error' in auth) return auth.error;
  const body = await req.json().catch(() => ({}));
  try {
    return await proxy(req, params.userId, {
      method: 'PATCH',
      headers: { Authorization: auth.header!, 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}

export async function DELETE(req: NextRequest, { params }: { params: { userId: string } }) {
  const auth = requireAuth(req);
  if ('error' in auth) return auth.error;
  try {
    return await proxy(req, params.userId, { method: 'DELETE', headers: { Authorization: auth.header! } });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}
