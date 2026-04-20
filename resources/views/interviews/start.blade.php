<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulakat AI - Start Interview</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <main class="mx-auto flex min-h-screen w-full max-w-5xl items-center px-4 py-10">
        <section class="w-full overflow-hidden rounded-3xl border border-zinc-800 bg-zinc-900/70 shadow-2xl shadow-black/40 backdrop-blur">
            <div class="grid gap-0 md:grid-cols-2">
                <div class="border-b border-zinc-800 bg-gradient-to-br from-zinc-900 via-zinc-950 to-black p-8 md:border-b-0 md:border-r">
                    <p class="inline-flex items-center rounded-full border border-zinc-700 bg-zinc-800/70 px-3 py-1 text-xs font-medium text-zinc-300">
                        AI Interview Coach
                    </p>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white">Mulakat AI</h1>
                    <p class="mt-4 text-sm leading-6 text-zinc-300">
                        Practice role-based technical interviews, get scored feedback for every answer, and finish with a personalized 7-day learning plan.
                    </p>
                    <div class="mt-8 rounded-2xl border border-zinc-800 bg-zinc-950/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-zinc-500">Included in this session</p>
                        <ul class="mt-3 space-y-2 text-sm text-zinc-300">
                            <li>10 role-specific interview questions</li>
                            <li>Per-answer rubric scoring</li>
                            <li>Final strengths & gaps report</li>
                            <li>7-day focused study plan</li>
                        </ul>
                    </div>
                </div>

                <div class="p-8">
                    <a href="{{ route('landing') }}" class="inline-flex items-center text-xs font-medium text-zinc-400 transition hover:text-zinc-200">
                        ← Back to introduction
                    </a>
                    <h2 class="text-xl font-semibold text-white">Start your mock interview</h2>
                    <p class="mt-2 text-sm text-zinc-400">Choose your track and level to begin.</p>

        <form method="POST" action="{{ route('interviews.store') }}">
            @csrf
                        <div class="mt-6 space-y-5">
                            <div>
                                <label for="role" class="mb-2 block text-sm font-medium text-zinc-200">Role Track</label>
                                <select name="role" id="role" required class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-sm text-zinc-100 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    <option value="frontend-react" @selected(old('role') === 'frontend-react')>Frontend / React</option>
                </select>
                @error('role')
                                    <div class="mt-2 text-sm text-rose-400">{{ $message }}</div>
                @enderror
            </div>

                            <div>
                                <label for="level" class="mb-2 block text-sm font-medium text-zinc-200">Level</label>
                                <select name="level" id="level" required class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-sm text-zinc-100 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    <option value="junior" @selected(old('level') === 'junior')>Junior</option>
                    <option value="mid" @selected(old('level') === 'mid')>Mid</option>
                </select>
                @error('level')
                                    <div class="mt-2 text-sm text-rose-400">{{ $message }}</div>
                @enderror
            </div>

                            <button type="submit" class="w-full rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200">
                                Start 10-Question Interview
                            </button>
                        </div>
        </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
