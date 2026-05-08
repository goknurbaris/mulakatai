<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulakat AI - Interview Result</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
    <main class="mx-auto w-full max-w-5xl px-4 py-10">
        <div class="mb-4 flex items-center justify-between gap-3">
            <a href="{{ route('interviews.history') }}" class="text-xs font-medium text-zinc-400 transition hover:text-zinc-200">← Back to dashboard</a>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('interviews.retake', $session) }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-indigo-700/60 bg-indigo-900/20 px-3 py-1.5 text-xs font-semibold text-indigo-200 transition hover:bg-indigo-900/35">Retake Interview</button>
                </form>
                <a href="{{ route('interviews.export', $session) }}" class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-semibold text-zinc-200 transition hover:bg-zinc-800">Export JSON</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-zinc-400 transition hover:text-zinc-200">Logout</button>
                </form>
            </div>
        </div>
        <h1 class="text-3xl font-semibold tracking-tight text-white">Interview Summary</h1>

        <section class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900/70 p-6 shadow-xl shadow-black/30">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-zinc-400">Role: {{ $roleLabel }} | Level: {{ strtoupper($session->level) }} | Focus: {{ $session->focus_topic ?? '-' }}</p>
                    @if (filled($session->interview_objective))
                        <p class="mt-1 text-sm text-zinc-400">Objective: {{ $session->interview_objective }}</p>
                    @endif
                    @if ($session->started_at && $session->completed_at)
                        <p class="mt-1 text-sm text-zinc-400">
                            Duration: {{ round($session->completed_at->diffInSeconds($session->started_at) / 60, 1) }} min
                            ({{ $session->started_at->format('Y-m-d H:i') }} - {{ $session->completed_at->format('Y-m-d H:i') }})
                        </p>
                    @endif
                    <p class="mt-1 text-4xl font-bold text-white">{{ number_format($session->total_score ?? 0, 1) }}<span class="text-lg text-zinc-400">/100</span></p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-400">Top strengths</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
        @foreach ($summary['strengths'] as $strength)
                            <span class="rounded-full border border-emerald-800/60 bg-emerald-900/30 px-3 py-1 text-xs text-emerald-300">{{ $strength }}</span>
        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-400">Top improvement areas</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
        @foreach ($summary['gaps'] as $gap)
                            <span class="rounded-full border border-amber-800/60 bg-amber-900/30 px-3 py-1 text-xs text-amber-300">{{ $gap }}</span>
        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900/70 p-6">
            <h2 class="text-xl font-semibold text-white">Answer breakdown</h2>
            <div class="mt-5 space-y-4">
        @foreach ($answers as $answer)
                    <article class="rounded-xl border border-zinc-800 bg-zinc-950/70 p-4">
                        <p class="text-sm font-semibold text-zinc-100">Q{{ $answer->question_index + 1 }} - {{ $answer->topic }}</p>
                        <p class="mt-1 text-sm text-zinc-400">{{ $answer->question_text }}</p>
                        <p class="mt-3 text-sm text-zinc-300"><span class="font-semibold text-zinc-100">Score:</span> {{ $answer->ai_score }}/100</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <p class="text-xs text-zinc-400">Accuracy: <span class="font-semibold text-zinc-200">{{ $answer->feedback_json['breakdown']['accuracy'] ?? '-' }}</span></p>
                            <p class="text-xs text-zinc-400">Depth: <span class="font-semibold text-zinc-200">{{ $answer->feedback_json['breakdown']['depth'] ?? '-' }}</span></p>
                            <p class="text-xs text-zinc-400">Clarity: <span class="font-semibold text-zinc-200">{{ $answer->feedback_json['breakdown']['clarity'] ?? '-' }}</span></p>
                            <p class="text-xs text-zinc-400">Problem-solving: <span class="font-semibold text-zinc-200">{{ $answer->feedback_json['breakdown']['problem_solving'] ?? '-' }}</span></p>
                        </div>
                        <p class="mt-1 text-sm text-zinc-300"><span class="font-semibold text-zinc-100">Ideal answer:</span> {{ $answer->feedback_json['ideal_answer'] }}</p>
                    </article>
        @endforeach
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900/70 p-6">
            <h2 class="text-xl font-semibold text-white">7-day learning plan</h2>
            @php
                $completedPlanItems = collect($learningPlan)->filter(fn (array $item): bool => (bool) ($item['completed'] ?? false))->count();
                $totalPlanItems = count($learningPlan);
                $planProgress = $totalPlanItems > 0 ? (int) round(($completedPlanItems / $totalPlanItems) * 100) : 0;
            @endphp
            <div class="mt-3">
                <p class="text-sm text-zinc-400">Progress: <span class="font-semibold text-zinc-200">{{ $completedPlanItems }}/{{ $totalPlanItems }}</span> ({{ $planProgress }}%)</p>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-800">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $planProgress }}%"></div>
                </div>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach ($learningPlan as $index => $item)
                    <article class="rounded-xl border {{ $item['completed'] ? 'border-emerald-700/60 bg-emerald-900/10' : 'border-zinc-800 bg-zinc-950/70' }} p-4">
                        <p class="text-sm font-semibold text-zinc-100">{{ $item['day'] }} - {{ $item['focus'] }}</p>
                        <p class="mt-1 text-sm text-zinc-400">{{ $item['task'] }}</p>
                        <form method="POST" action="{{ route('interviews.learning-plan.toggle', [$session, $index]) }}" class="mt-3">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs font-semibold {{ $item['completed'] ? 'border-emerald-700/60 bg-emerald-900/30 text-emerald-200 hover:bg-emerald-900/45' : 'border-zinc-700 bg-zinc-900 text-zinc-200 hover:bg-zinc-800' }}">
                                {{ $item['completed'] ? 'Mark as pending' : 'Mark as done' }}
                            </button>
                        </form>
                    </article>
        @endforeach
            </div>
        </section>
    </main>
</body>
</html>
