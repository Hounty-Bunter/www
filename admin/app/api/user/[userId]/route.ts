import type { NextRequest } from 'next/server';
import { NextResponse } from 'next/server';

// Proxy GET /api/user/:id to the Flask backend /user/<id>
export async function GET(req: NextRequest, { params }: { params: { userId: string } }) {
  // Authorization header is required to match the backend behavior.
  const authHeader = req.headers.get('authorization') || req.headers.get('Authorization');
  const userId = params.userId;

  if (!authHeader) {
    return NextResponse.json({ msg: 'Authorization header is required', status: 401, user: null }, { status: 401 });
  }

  if (!userId) {
    return NextResponse.json({ msg: 'Missing user id', status: 400 }, { status: 400 });
  }

  // Prefer explicit API_BASE_URL; otherwise use current origin.
  const upstreamBase = process.env.API_BASE_URL ?? req.nextUrl.origin;
  // Backend serves user detail at /api/user/<id>.
  const backendUrl = `${upstreamBase}/api/user/${userId}`;

  try {
    const res = await fetch(backendUrl, {
      method: 'GET',
      headers: { Authorization: authHeader },
      cache: 'no-store',
    });

    const data = await res.json().catch(() => null);
    return NextResponse.json(data ?? { msg: 'Upstream error', status: res.status }, { status: res.status });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}
