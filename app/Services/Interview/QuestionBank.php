<?php

namespace App\Services\Interview;

class QuestionBank
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forRoleLevel(string $role, string $level): array
    {
        return match ($role) {
            'frontend-react' => $this->frontendReactQuestions($level),
            default => [],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function frontendReactQuestions(string $level): array
    {
        $difficulty = $level === 'junior' ? 'easy' : 'medium';

        return [
            [
                'topic' => 'React State',
                'difficulty' => $difficulty,
                'question' => 'When should you use useState versus useReducer in React?',
                'keywords' => ['complex state', 'multiple transitions', 'predictable updates', 'reducer'],
                'ideal_answer' => 'useState is good for simple local state. useReducer is better when state transitions are complex or depend on previous state and actions.',
            ],
            [
                'topic' => 'Component Design',
                'difficulty' => $difficulty,
                'question' => 'How do you decide whether to split a component into smaller components?',
                'keywords' => ['single responsibility', 'reusability', 'readability', 'testability'],
                'ideal_answer' => 'Split components when responsibilities diverge, JSX becomes hard to read, or logic can be reused and tested separately.',
            ],
            [
                'topic' => 'Rendering',
                'difficulty' => $difficulty,
                'question' => 'What causes unnecessary re-renders in React and how do you reduce them?',
                'keywords' => ['props reference', 'memo', 'useMemo', 'useCallback'],
                'ideal_answer' => 'Unstable object/function references and broad state updates can trigger re-renders. memoization and better state boundaries reduce this.',
            ],
            [
                'topic' => 'Data Fetching',
                'difficulty' => $difficulty,
                'question' => 'How would you handle loading, error, and empty states for an API-driven page?',
                'keywords' => ['loading state', 'error state', 'empty state', 'retry'],
                'ideal_answer' => 'Model each state explicitly in UI, provide clear user feedback, and offer retry or fallback actions on failure.',
            ],
            [
                'topic' => 'JavaScript Fundamentals',
                'difficulty' => $difficulty,
                'question' => 'Explain the event loop in JavaScript in practical terms.',
                'keywords' => ['call stack', 'task queue', 'microtask', 'async'],
                'ideal_answer' => 'JS runs single-threaded execution on the call stack while async callbacks are queued and processed after the current execution completes.',
            ],
            [
                'topic' => 'Accessibility',
                'difficulty' => $difficulty,
                'question' => 'What are your first checks to make a form accessible?',
                'keywords' => ['label', 'keyboard', 'aria', 'error message'],
                'ideal_answer' => 'Use semantic fields with labels, keyboard navigation, screen-reader-friendly error messages, and proper aria attributes when needed.',
            ],
            [
                'topic' => 'Performance',
                'difficulty' => $difficulty,
                'question' => 'How would you improve initial load performance on a React page?',
                'keywords' => ['code splitting', 'lazy loading', 'caching', 'bundle size'],
                'ideal_answer' => 'Reduce bundle size, split routes/components, lazy load non-critical parts, and cache static assets effectively.',
            ],
            [
                'topic' => 'Testing',
                'difficulty' => $difficulty,
                'question' => 'What should be covered by unit tests vs integration tests on frontend?',
                'keywords' => ['unit', 'integration', 'user behavior', 'critical paths'],
                'ideal_answer' => 'Unit tests cover isolated logic and edge cases, while integration tests validate user-facing flows and component interactions.',
            ],
            [
                'topic' => 'System Thinking',
                'difficulty' => $level === 'mid' ? 'hard' : 'medium',
                'question' => 'How would you design frontend state for a dashboard with multiple widgets updating at different times?',
                'keywords' => ['normalized state', 'cache', 'stale data', 'boundaries'],
                'ideal_answer' => 'Define clear state boundaries, normalize shared entities, and use cache-aware fetching to avoid stale or duplicated data.',
            ],
            [
                'topic' => 'Communication',
                'difficulty' => $difficulty,
                'question' => 'How do you communicate trade-offs when choosing a technical approach?',
                'keywords' => ['trade-off', 'impact', 'risk', 'alternative'],
                'ideal_answer' => 'Describe alternatives, explain impact on users and maintainability, and make risks explicit with a clear recommendation.',
            ],
        ];
    }
}
