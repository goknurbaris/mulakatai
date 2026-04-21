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
            <form method="GET" action="{{ route('interviews.history') }}" class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-4">
                <div class="grid gap-3 md:grid-cols-4">
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
                        <label for="status" class="mb-1 block text-xs font-medium uppercase tracking-wide text-zinc-400">Status</label>
                        <select id="status" name="status" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-indigo-500">
                            <option value="">All statuses</option>
                            <option value="in_progress" @selected($selectedStatus === 'in_progress')>In progress</option>
                            <option value="completed" @selected($selectedStatus === 'completed')>Completed</option>
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
