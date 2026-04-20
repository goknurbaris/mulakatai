<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulakat AI - AI Interview Coach</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <main class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(99,102,241,0.2),transparent_45%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.15),transparent_40%)]"></div>

        <section class="mx-auto w-full max-w-6xl px-4 py-16 md:py-24">
            <div class="rounded-3xl border border-zinc-800 bg-zinc-900/60 p-8 shadow-2xl shadow-black/40 backdrop-blur md:p-12">
                <p class="inline-flex rounded-full border border-zinc-700 bg-zinc-800/70 px-3 py-1 text-xs font-medium text-zinc-300">
                    AI Interview Simulator
                </p>
                <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-white md:text-5xl">
                    Prepare for real technical interviews with structured AI feedback
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-zinc-300">
                    Mulakat AI simulates role-based interviews, scores each answer with a clear rubric, and builds your personalized 7-day improvement plan.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('interviews.start') }}" class="inline-flex items-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200">
                        Start Interview App
                    </a>
                    <a href="{{ route('features') }}" class="inline-flex items-center rounded-xl border border-zinc-700 bg-zinc-900 px-5 py-3 text-sm font-semibold text-zinc-200 transition hover:bg-zinc-800">
                        See Features
                    </a>
                    <a href="{{ route('interviews.start') }}" class="inline-flex items-center rounded-xl border border-indigo-700/70 bg-indigo-600/20 px-5 py-3 text-sm font-semibold text-indigo-200 transition hover:bg-indigo-600/30">
                        Try Demo Session
                    </a>
                </div>
            </div>

            <section id="features" class="mt-16 grid scroll-mt-24 gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
                    <h2 class="text-lg font-semibold text-white">Role-specific simulation</h2>
                    <p class="mt-2 text-sm text-zinc-400">Choose Frontend, Backend, or Fullstack and answer 10 interview questions in a realistic flow.</p>
                    <a href="{{ route('interviews.start') }}" class="mt-4 inline-flex text-sm font-medium text-indigo-300 hover:text-indigo-200">Start now →</a>
                </article>
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
                    <h2 class="text-lg font-semibold text-white">Transparent scoring</h2>
                    <p class="mt-2 text-sm text-zinc-400">Each answer is scored with accuracy, depth, clarity, and problem-solving criteria.</p>
                    <a href="{{ route('interviews.start') }}" class="mt-4 inline-flex text-sm font-medium text-indigo-300 hover:text-indigo-200">See scoring in action →</a>
                </article>
                <article class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
                    <h2 class="text-lg font-semibold text-white">Actionable learning plan</h2>
                    <p class="mt-2 text-sm text-zinc-400">Finish with strengths, improvement areas, and a practical 7-day study plan.</p>
                    <a href="{{ route('interviews.start') }}" class="mt-4 inline-flex text-sm font-medium text-indigo-300 hover:text-indigo-200">Generate your plan →</a>
                </article>
            </section>
        </section>
    </main>
</body>
</html>
