import { NextResponse, type NextRequest } from 'next/server';

const API_BASE = (process.env.API_BASE_URL || 'https://admin.hountybunter.click').replace(/\/$/, '');

function requireAuth(req: NextRequest) {
  const header = req.headers.get('Authorization') || req.headers.get('authorization');
  if (!header) {
    return { error: NextResponse.json({ msg: 'Authorization header is required', status: 401 }, { status: 401 }) };
  }
  return { header };
}

async function proxy(userId: string, init: RequestInit) {
  const backendUrl = `${API_BASE}/user/${userId}`;
  const res = await fetch(backendUrl, { ...init, cache: 'no-store' });
  const data = await res.json().catch(() => null);
  return NextResponse.json(data ?? { status: res.status }, { status: res.status });
}

export async function GET(req: NextRequest, { params }: { params: { userId: string } }) {
  const auth = requireAuth(req);
  if ('error' in auth) return auth.error;
  try {
    return await proxy(params.userId, { method: 'GET', headers: { Authorization: auth.header as string } });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}

export async function PATCH(req: NextRequest, { params }: { params: { userId: string } }) {
  const auth = requireAuth(req);
  if ('error' in auth) return auth.error;
  const body = await req.json().catch(() => ({}));
  try {
    return await proxy(params.userId, {
      method: 'PATCH',
      headers: { Authorization: auth.header as string, 'Content-Type': 'application/json' },
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
    return await proxy(params.userId, { method: 'DELETE', headers: { Authorization: auth.header as string } });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}
