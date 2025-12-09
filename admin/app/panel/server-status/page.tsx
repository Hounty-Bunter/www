'use client';

import Link from 'next/link';
import Script from 'next/script';
import { FormEvent, useCallback, useEffect, useRef, useState } from 'react';

declare global {
  interface Window {
    io?: any;
  }
}

type Tone = 'status' | 'error' | 'command';
type LogEntry = { id: number; text: string; tone?: Tone };
type Stats = { cpu?: number; memory?: number; timestamp?: string };

const SOCKET_PATH = '/socket.io';
const SOCKET_NAMESPACE = '/ws';

export default function ServerStatusPage() {
  const [command, setCommand] = useState('');
  const [connected, setConnected] = useState(false);
  const [scriptReady, setScriptReady] = useState(false);
  const [logs, setLogs] = useState<LogEntry[]>([]);
  const [stats, setStats] = useState<Stats>({});
  const socketRef = useRef<any>(null);
  const logId = useRef(0);

  const appendLog = useCallback((text: string, tone?: Tone) => {
    const id = ++logId.current;
    setLogs((prev) => [{ id, text, tone }, ...prev].slice(0, 200));
  }, []);

  useEffect(() => {
    if (!scriptReady || typeof window === 'undefined' || !window.io) return;

    const base = (process.env.NEXT_PUBLIC_SOCKET_ENDPOINT || window.location.origin || '').replace(/\/+$/, '');
    const socketUrl = `${base}${SOCKET_NAMESPACE}`;
    const socket = window.io(socketUrl, {
      path: SOCKET_PATH,
      transports: ['websocket'],
    });
    socketRef.current = socket;

    socket.on('connect', () => {
      setConnected(true);
      appendLog(`Connected (${socket.id})`, 'status');
    });

    socket.on('disconnect', () => {
      setConnected(false);
      appendLog('Disconnected', 'error');
    });

    socket.on('connect_error', (err: any) => {
      appendLog(`Connection error: ${err?.message ?? err}`, 'error');
    });

    socket.on('response', (data: any) => {
      if (!data) return;
      appendLog(typeof data.msg === 'string' ? data.msg : JSON.stringify(data));
    });

    socket.on('server-status', (data: any) => {
      const { cpu, memory, timestamp, msg } = data || {};
      setStats({ cpu, memory, timestamp });
      appendLog(msg || `Server status: CPU ${cpu ?? '--'}%, Memory ${memory ?? '--'}%`, 'status');
    });

    return () => {
      socket.disconnect();
      socketRef.current = null;
    };
  }, [appendLog, scriptReady]);

  const sendCommand = useCallback(
    (cmd: string) => {
      const trimmed = cmd.trim();
      if (!trimmed || !socketRef.current) return;
      appendLog(`> ${trimmed}`, 'command');
      socketRef.current.emit('command', trimmed);
      setCommand('');
    },
    [appendLog],
  );

  const handleSubmit = useCallback(
    (event: FormEvent<HTMLFormElement>) => {
      event.preventDefault();
      sendCommand(command);
    },
    [command, sendCommand],
  );

  const lastUpdated = stats.timestamp ? new Date(stats.timestamp).toLocaleTimeString() : '—';

  const quickActions = [
    { label: 'Help', cmd: 'help' },
    { label: 'Ping Test', cmd: 'ping' },
    { label: 'Get Memory', cmd: 'memory' },
    { label: 'Who Am I', cmd: 'whoami' },
    { label: 'Start Monitoring', cmd: 'server-status' },
  ];

  return (
    <>
      <Script
        src="https://cdn.socket.io/4.7.5/socket.io.min.js"
        strategy="afterInteractive"
        onLoad={() => setScriptReady(true)}
      />
      <div className="min-h-screen bg-[#0a0a0a] text-[#f0f0f0]">
        <div className="mx-auto max-w-5xl px-4 py-10">
          <div className="mb-6 flex items-center gap-3">
            <Link
              href="/panel"
              className="inline-flex items-center gap-2 rounded-lg border border-[#202020] bg-[#121212] px-4 py-2 text-sm text-[#f0f0f0] transition hover:-translate-y-0.5 hover:border-[#f5a623] hover:text-white"
            >
              ← Back to Panel
            </Link>
            <span className="flex items-center gap-2 rounded-full border border-[#1a1a1a] bg-[#111] px-3 py-1 text-xs text-[#d6d6d6]">
              <span
                className={`h-2.5 w-2.5 rounded-full ${connected ? 'bg-[#4ade80] shadow-[0_0_0_6px_rgba(74,222,128,0.18)]' : 'bg-rose-500 animate-pulse'}`}
              />
              {connected ? 'Connected' : 'Disconnected'}
            </span>
          </div>

          <div className="rounded-2xl border border-[#1c1c1c] bg-[#0e0e0e] p-6 shadow-[0_25px_70px_rgba(0,0,0,0.55)] ring-1 ring-[#1c1c1c]">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-[#f5a623]">Server</p>
                <h1 className="text-3xl font-semibold text-white">Server Dashboard</h1>
                <p className="text-sm text-[#c9c9c9]">Run quick commands and monitor live server output.</p>
              </div>
              <div className="flex gap-3 text-sm">
                <div className="rounded-xl border border-[#1f1f1f] bg-[#161616] px-4 py-3">
                  <p className="text-xs uppercase text-[#888]">CPU</p>
                  <p className="text-xl font-semibold text-[#4ade80]">{stats.cpu ?? '--'}%</p>
                </div>
                <div className="rounded-xl border border-[#1f1f1f] bg-[#161616] px-4 py-3">
                  <p className="text-xs uppercase text-[#888]">Memory</p>
                  <p className="text-xl font-semibold text-[#4ade80]">{stats.memory ?? '--'}%</p>
                </div>
              </div>
            </div>

            <div className="mt-2 text-xs text-[#8c8c8c]">
              Last update: <span className="text-[#f0f0f0]">{lastUpdated}</span>
            </div>

            <div className="mt-8">
              <h2 className="text-sm font-semibold text-white">Quick Actions</h2>
              <div className="mt-3 flex flex-wrap gap-3">
                {quickActions.map((action) => (
                  <button
                    key={action.cmd}
                    type="button"
                    onClick={() => sendCommand(action.cmd)}
                    disabled={!connected}
                    className={`rounded-lg border px-4 py-2 text-sm font-medium transition ${connected ? 'border-[#f5a623] bg-[#f5a623] text-black hover:-translate-y-0.5 hover:bg-[#ffb948]' : 'cursor-not-allowed border-[#1f1f1f] bg-[#1a1a1a] text-[#6e6e6e]'}`}
                  >
                    {action.label}
                  </button>
                ))}
              </div>
            </div>

            <div className="mt-8">
              <h3 className="text-sm font-semibold text-white">Command Terminal</h3>
              <form onSubmit={handleSubmit} className="mt-3 flex flex-col gap-3 sm:flex-row">
                <input
                  type="text"
                  value={command}
                  onChange={(e) => setCommand(e.target.value)}
                  placeholder="Enter command (help, ping, memory, server-status, whoami)"
                  className="flex-1 rounded-lg border border-[#1f1f1f] bg-[#111111] px-4 py-3 text-sm text-[#f0f0f0] outline-none ring-0 transition focus:border-[#f5a623] focus:bg-[#141414]"
                  disabled={!connected}
                />
                <button
                  type="submit"
                  disabled={!connected || !command.trim()}
                  className={`rounded-lg px-5 py-3 text-sm font-semibold transition ${connected && command.trim()
                      ? 'bg-[#f5a623] text-black hover:-translate-y-0.5 hover:bg-[#ffb948]'
                      : 'cursor-not-allowed bg-[#1a1a1a] text-[#6e6e6e]'
                    }`}
                >
                  Send
                </button>
              </form>
              {!connected && (
                <p className="mt-2 text-xs text-[#f5a623]">Waiting for socket connection…</p>
              )}
            </div>

            <div className="mt-8">
              <h3 className="text-sm font-semibold text-white">Output</h3>
              <div className="mt-3 h-80 overflow-y-auto rounded-xl border border-[#0f3017] bg-[#050505] px-4 py-3 font-mono text-xs text-emerald-300 shadow-inner">
                {logs.length === 0 ? (
                  <p className="text-[#6e6e6e]">No output yet. Use quick actions or send a command.</p>
                ) : (
                  <ul className="space-y-1">
                    {logs.map((log) => (
                      <li
                        key={log.id}
                        className={`whitespace-pre-wrap ${log.tone === 'error' ? 'text-rose-400' : log.tone === 'command' ? 'text-sky-300' : 'text-emerald-300'}`}
                      >
                        {log.text}
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
