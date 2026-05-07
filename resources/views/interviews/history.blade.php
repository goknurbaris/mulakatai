<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulakat AI - Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <main class="mx-auto w-full max-w-6xl px-4 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-white">My Interviews</h1>
                <p class="text-sm text-zinc-400">Continue in-progress sessions or review completed reports.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('interviews.start') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 hover:bg-zinc-200">New Interview</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-zinc-200 hover:bg-zinc-800">Logout</button>
                </form>
            </div>
        </div>

        <section class="mt-6 space-y-3">
            <div class="grid gap-3 md:grid-cols-4">
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                    <p class="text-xs uppercase tracking-wide text-zinc-400">Total Sessions</p>
                    <p class="mt-2 text-2xl font-semibold text-white">{{ $stats['total_sessions'] }}</p>
                </article>
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                    <p class="text-xs uppercase tracking-wide text-zinc-400">Completed</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-300">{{ $stats['completed_sessions'] }}</p>
                </article>
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                    <p class="text-xs uppercase tracking-wide text-zinc-400">In Progress</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-300">{{ $stats['in_progress_sessions'] }}</p>
                </article>
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                    <p class="text-xs uppercase tracking-wide text-zinc-400">Completion Rate</p>
                    <p class="mt-2 text-2xl font-semibold text-indigo-300">{{ $stats['completion_rate'] }}%</p>
                    <p class="mt-1 text-xs text-zinc-500">Avg completed score: {{ number_format($stats['average_completed_score'], 1) }}</p>
                </article>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                    <h2 class="text-sm font-semibold text-white">Score Trend (7 Days)</h2>
                    <div class="mt-3 space-y-2">
                        @php
                            $max7DayScore = collect($analytics['trend_7_days'])->pluck('avg_score')->filter()->max() ?? 100;
                        @endphp
                        @foreach ($analytics['trend_7_days'] as $point)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs text-zinc-400">
                                    <span>{{ $point['label'] }}</span>
                                    <span>
                                        {{ $point['avg_score'] !== null ? number_format((float) $point['avg_score'], 1) : '-' }}
                                        ({{ $point['session_count'] }})
                                    </span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-800">
                                    <div
                                        class="h-full rounded-full bg-indigo-500"
                                        style="width: {{ $point['avg_score'] !== null ? max(6, (($point['avg_score'] / max(1, $max7DayScore)) * 100)) : 0 }}%"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                    <h2 class="text-sm font-semibold text-white">Score Trend (30 Days)</h2>
                    <div class="mt-3 grid gap-1" style="grid-template-columns: repeat(30, minmax(0, 1fr));">
                        @foreach ($analytics['trend_30_days'] as $point)
                            <div class="group relative h-16">
                                <div
                                    class="absolute bottom-0 w-full rounded bg-emerald-500/70"
                                    style="height: {{ $point['avg_score'] !== null ? max(8, $point['avg_score']) : 4 }}%"
                                ></div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">Bars represent daily average score. Empty days stay low.</p>
                </article>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                    <h2 class="text-sm font-semibold text-white">Role Performance</h2>
                    <div class="mt-3 space-y-2">
                        @forelse ($analytics['role_performance'] as $item)
                            <div class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm">
                                <span class="text-zinc-200">{{ $item['label'] }}</span>
                                <span class="text-zinc-400">{{ number_format((float) $item['avg_score'], 1) }} avg · {{ $item['count'] }} sessions</span>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No completed sessions yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                    <h2 class="text-sm font-semibold text-white">Top Focus Topics</h2>
                    <div class="mt-3 space-y-2">
                        @forelse ($analytics['topic_performance'] as $item)
                            <div class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm">
                                <span class="text-zinc-200">{{ $item['topic'] }}</span>
                                <span class="text-zinc-400">{{ number_format((float) $item['avg_score'], 1) }} avg · {{ $item['count'] }} sessions</span>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No topic data yet.</p>
                        @endforelse
                    </div>
                </article>
            </div>

            <form method="GET" action="{{ route('interviews.history') }}" class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                <div class="grid gap-3 md:grid-cols-6">
                    <div>
                        <label for="role" class="mb-1 block text-xs font-medium uppercase tracking-wide text-zinc-400">Role</label>
                        <select id="role" name="role" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-indigo-500">
                            <option value="">All roles</option>
                            @foreach ($roleOptions as $roleValue => $roleLabel)
                                <option value="{{ $roleValue }}" @selected($selectedRole === $roleValue)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="level" class="mb-1 block text-xs font-medium uppercase tracking-wide text-zinc-400">Level</label>
                        <select id="level" name="level" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-indigo-500">
                            <option value="">All levels</option>
                            <option value="junior" @selected($selectedLevel === 'junior')>Junior</option>
                            <option value="mid" @selected($selectedLevel === 'mid')>Mid</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="mb-1 block text-xs font-medium uppercase tracking-wide text-zinc-400">Status</label>
                        <select id="status" name="status" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-indigo-500">
                            <option value="">All statuses</option>
                            <option value="in_progress" @selected($selectedStatus === 'in_progress')>In progress</option>
                            <option value="completed" @selected($selectedStatus === 'completed')>Completed</option>
                        </select>
                    </div>
                    <div>
                        <label for="topic" class="mb-1 block text-xs font-medium uppercase tracking-wide text-zinc-400">Focus Topic</label>
                        <select id="topic" name="topic" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-indigo-500">
                            <option value="">All topics</option>
                            @foreach ($topicOptions as $topicOption)
                                <option value="{{ $topicOption }}" @selected($selectedTopic === $topicOption)>{{ $topicOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 flex items-end gap-2">
                        <button type="submit" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 hover:bg-zinc-200">Apply filters</button>
                        <a href="{{ route('interviews.history') }}" class="rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-zinc-200 hover:bg-zinc-800">Reset</a>
                    </div>
                </div>
            </form>

            @forelse ($sessions as $session)
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-zinc-300">Role: {{ $roleOptions[$session->role] ?? ucfirst($session->role) }} | Level: {{ strtoupper($session->level) }} | Focus: {{ $session->focus_topic ?? '-' }}</p>
                            <p class="mt-1 text-xs text-zinc-500">Created: {{ $session->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($session->status === 'completed')
                                <span class="rounded-full border border-emerald-800/60 bg-emerald-900/30 px-3 py-1 text-xs text-emerald-300">Completed</span>
                                <a href="{{ route('interviews.result', $session) }}" class="rounded-xl border border-zinc-700 px-3 py-2 text-sm text-zinc-200 hover:bg-zinc-800">View Result</a>
                            @else
                                <span class="rounded-full border border-amber-800/60 bg-amber-900/30 px-3 py-1 text-xs text-amber-300">In progress</span>
                                <a href="{{ route('interviews.resume', $session) }}" class="rounded-xl border border-zinc-700 px-3 py-2 text-sm text-zinc-200 hover:bg-zinc-800">Continue</a>
                            @endif
                            <form method="POST" action="{{ route('interviews.destroy', $session) }}" onsubmit="return confirm('Delete this interview session?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-xl border border-rose-700/60 bg-rose-900/20 px-3 py-2 text-sm text-rose-200 hover:bg-rose-900/35">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-6 text-zinc-400">
                    No interview yet. Start your first session.
                </article>
            @endforelse

            @if ($sessions->hasPages())
                <div class="pt-2">
                    {{ $sessions->onEachSide(1)->links() }}
                </div>
            @endif
        </section>
    </main>
</body>
</html>
