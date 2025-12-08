import type { NextRequest } from 'next/server';
import { NextResponse } from 'next/server';

// Proxy GET /api/user/:id to the Flask backend /user/<id>
export async function GET(req: NextRequest, { params }: { params: { userId: string } }) {
  const token = req.headers.get('authorization');
  const userId = params.userId;

  if (!userId) {
    return NextResponse.json({ msg: 'Missing user id', status: 400 }, { status: 400 });
  }

  // Hit the existing Flask endpoint. Adjust host/port if different in your env.
  const backendUrl = `${process.env.API_BASE_URL ?? 'http://localhost:5000'}/user/${userId}`;

  try {
    const res = await fetch(backendUrl, {
      headers: token ? { Authorization: token } : {},
      cache: 'no-store',
    });

    const data = await res.json().catch(() => null);

    return NextResponse.json(data ?? { msg: 'Upstream error', status: res.status }, { status: res.status });
  } catch (err) {
    return NextResponse.json({ msg: 'Proxy error', detail: `${err}` }, { status: 500 });
  }
}
