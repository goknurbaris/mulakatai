<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulakat AI - Question {{ $questionNumber }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <main class="mx-auto w-full max-w-4xl px-4 py-10">
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/70 p-5 shadow-xl shadow-black/30">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h1 class="text-lg font-semibold text-white">Interview in progress</h1>
                <span class="rounded-full border border-zinc-700 bg-zinc-800 px-3 py-1 text-xs font-medium text-zinc-300">
                    Question {{ $questionNumber }} / {{ $totalQuestions }}
                </span>
            </div>

            <div class="mb-4 h-2 w-full overflow-hidden rounded-full bg-zinc-800">
                <div class="h-full rounded-full bg-indigo-500" style="width: {{ ($questionNumber / $totalQuestions) * 100 }}%"></div>
            </div>

            <div class="mb-5 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full border border-zinc-700 bg-zinc-800/70 px-3 py-1 text-zinc-300">Topic: {{ $question['topic'] }}</span>
                <span class="rounded-full border border-zinc-700 bg-zinc-800/70 px-3 py-1 text-zinc-300">Difficulty: {{ strtoupper($question['difficulty']) }}</span>
            </div>

            <h2 class="text-2xl font-semibold leading-snug text-white">{{ $question['question'] }}</h2>

        <form method="POST" action="{{ route('interviews.answer', $session) }}">
            @csrf
                <label for="answer" class="mt-6 mb-2 block text-sm font-medium text-zinc-200">Your answer</label>
                <textarea name="answer" id="answer" required minlength="10" class="min-h-[210px] w-full rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-sm text-zinc-100 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">{{ old('answer') }}</textarea>
            @error('answer')
                    <div class="mt-2 text-sm text-rose-400">{{ $message }}</div>
            @enderror
                <button type="submit" class="mt-4 inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200">
                    Submit answer
                </button>
        </form>
        </div>
    </main>
</body>
</html>
