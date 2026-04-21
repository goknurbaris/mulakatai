<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulakat AI - Features</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <main class="mx-auto w-full max-w-6xl px-4 py-14">
        <a href="{{ route('landing') }}" class="inline-flex items-center text-sm text-zinc-400 hover:text-zinc-200">← Back to home</a>

        <header class="mt-6 rounded-3xl border border-zinc-800 bg-zinc-900/60 p-8 shadow-2xl shadow-black/40">
            <p class="inline-flex rounded-full border border-zinc-700 bg-zinc-800/70 px-3 py-1 text-xs font-medium text-zinc-300">
                Product Features
            </p>
            <h1 class="mt-4 text-4xl font-bold tracking-tight text-white">What you can do with Mulakat AI</h1>
            <p class="mt-3 max-w-3xl text-zinc-300">
                Practice realistic technical interviews, receive structured feedback, and improve with a guided learning plan.
            </p>
        </header>

        <section class="mt-8 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
                <h2 class="text-lg font-semibold text-white">Role-specific simulation</h2>
                <p class="mt-2 text-sm text-zinc-400">Frontend, Backend, or Fullstack interview flow with 10 guided questions.</p>
            </article>
            <article class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
                <h2 class="text-lg font-semibold text-white">AI scoring engine</h2>
                <p class="mt-2 text-sm text-zinc-400">Each answer is scored by rubric: accuracy, depth, clarity, problem-solving.</p>
            </article>
            <article class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
                <h2 class="text-lg font-semibold text-white">7-day plan</h2>
                <p class="mt-2 text-sm text-zinc-400">Get actionable next steps to improve weak areas quickly.</p>
            </article>
        </section>

        <div class="mt-10">
            <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200">
                Start Interview App
            </a>
        </div>
    </main>
</body>
</html>
