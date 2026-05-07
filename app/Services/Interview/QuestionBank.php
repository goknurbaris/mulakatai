<?php

namespace App\Services\Interview;

class QuestionBank
{
    /**
     * @return array<string, string>
     */
    public function roleOptions(): array
    {
        return [
            'frontend' => 'Frontend',
            'backend' => 'Backend',
            'fullstack' => 'Fullstack',
        ];
    }

    public function roleLabel(string $role): string
    {
        return $this->roleOptions()[$role] ?? ucfirst($role);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function topicOptionsByRole(): array
    {
        $topicOptions = [];

        foreach (array_keys($this->roleOptions()) as $role) {
            $topicOptions[$role] = $this->topicOptionsForRole($role);
        }

        return $topicOptions;
    }

    /**
     * @return array<int, string>
     */
    public function topicOptionsForRole(string $role): array
    {
        $questions = $this->forRoleLevel($role, 'mid');
        $topics = array_map(static fn (array $question): string => (string) $question['topic'], $questions);

        return array_values(array_unique($topics));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forRoleLevel(string $role, string $level, ?string $focusTopic = null): array
    {
        $questions = match ($role) {
            'frontend' => $this->frontendQuestions($level),
            'backend' => $this->backendQuestions($level),
            'fullstack' => $this->fullstackQuestions($level),
            default => [],
        };

        if ($focusTopic === null || $focusTopic === '') {
            return $questions;
        }

        return $this->prioritizeFocusTopic($questions, $focusTopic);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function frontendQuestions(string $level): array
    {
        $difficulty = $level === 'junior' ? 'easy' : 'medium';

        return [
            [
                'topic' => 'React State',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'In a small component, when would you choose useState over useReducer?'
                    : 'How would you decide useState vs useReducer in a large feature with shared transitions?',
                'keywords' => ['complex state', 'multiple transitions', 'predictable updates', 'reducer'],
                'ideal_answer' => 'useState is good for simple local state. useReducer is better when state transitions are complex or depend on previous state and actions.',
            ],
            [
                'topic' => 'Component Design',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What are simple signs that a React component should be split into smaller pieces?'
                    : 'How do you split a feature component while preserving ownership boundaries and reuse?',
                'keywords' => ['single responsibility', 'reusability', 'readability', 'testability'],
                'ideal_answer' => 'Split components when responsibilities diverge, JSX becomes hard to read, or logic can be reused and tested separately.',
            ],
            [
                'topic' => 'Rendering',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What are common reasons for unnecessary re-renders in React?'
                    : 'How do you systematically profile and reduce re-renders in a data-heavy React screen?',
                'keywords' => ['props reference', 'memo', 'useMemo', 'useCallback'],
                'ideal_answer' => 'Unstable object/function references and broad state updates can trigger re-renders. memoization and better state boundaries reduce this.',
            ],
            [
                'topic' => 'Data Fetching',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'How would you show loading, error, and empty states on a simple API page?'
                    : 'How would you model loading/error/empty states across multiple dependent API calls?',
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
                'question' => $level === 'junior'
                    ? 'What are your first steps to make a React page load faster?'
                    : 'How would you optimize initial load performance with measurable frontend metrics?',
                'keywords' => ['code splitting', 'lazy loading', 'caching', 'bundle size'],
                'ideal_answer' => 'Reduce bundle size, split routes/components, lazy load non-critical parts, and cache static assets effectively.',
            ],
            [
                'topic' => 'Testing',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What is the difference between unit and integration tests on frontend?'
                    : 'How do you design a frontend test pyramid for confidence and maintainability?',
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
                'question' => $level === 'junior'
                    ? 'How would you explain your technical choice to your teammate?'
                    : 'How do you communicate technical trade-offs to product and engineering stakeholders?',
                'keywords' => ['trade-off', 'impact', 'risk', 'alternative'],
                'ideal_answer' => 'Describe alternatives, explain impact on users and maintainability, and make risks explicit with a clear recommendation.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function backendQuestions(string $level): array
    {
        $difficulty = $level === 'junior' ? 'easy' : 'medium';

        return [
            [
                'topic' => 'Routing & Controllers',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What makes a Laravel controller clean and easy to maintain?'
                    : 'How do you enforce thin-controller patterns across a growing Laravel codebase?',
                'keywords' => ['service class', 'validation', 'single responsibility', 'business logic'],
                'ideal_answer' => 'Move business logic to services/actions, keep validation in Form Requests, and let controllers orchestrate request/response flow.',
            ],
            [
                'topic' => 'Eloquent',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What is an N+1 query problem in Laravel and how do you fix it?'
                    : 'How do you detect and prevent N+1 issues proactively in Eloquent-heavy features?',
                'keywords' => ['eager loading', 'with', 'relationships', 'n+1'],
                'ideal_answer' => 'N+1 happens when relationships are loaded in loops. Prevent it with eager loading using with() and query planning.',
            ],
            [
                'topic' => 'Validation',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'When should you prefer Form Request validation in Laravel?'
                    : 'How do you structure complex validation/authorization rules with Form Requests at scale?',
                'keywords' => ['form request', 'reusability', 'authorization', 'clean controller'],
                'ideal_answer' => 'Form Requests improve reuse, authorization handling, and controller cleanliness for non-trivial validation rules.',
            ],
            [
                'topic' => 'Queues',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'Which Laravel tasks are good candidates for queues?'
                    : 'How do you decide queue boundaries and idempotency for backend workflows?',
                'keywords' => ['email', 'background jobs', 'slow tasks', 'user experience'],
                'ideal_answer' => 'Slow non-blocking tasks like emails, exports, and heavy API work should run in queues to keep requests fast.',
            ],
            [
                'topic' => 'Caching',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'How would you cache frequently read data and keep it fresh?'
                    : 'How do you design cache invalidation strategy for high-traffic, multi-entity data?',
                'keywords' => ['ttl', 'cache tags', 'invalidation', 'freshness'],
                'ideal_answer' => 'Use TTL and event-driven invalidation; invalidate when underlying records change to balance freshness and performance.',
            ],
            [
                'topic' => 'Auth & Security',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What are the first security checks you do in a Laravel API?'
                    : 'How do you review Laravel API security posture before production release?',
                'keywords' => ['authorization', 'validation', 'rate limit', 'csrf', 'sanitization'],
                'ideal_answer' => 'Ensure strong auth/authorization, strict validation, rate limiting, secure defaults, and proper handling of sensitive data.',
            ],
            [
                'topic' => 'Database Design',
                'difficulty' => $difficulty,
                'question' => 'How do you decide between normalized tables and denormalized fields?',
                'keywords' => ['normalization', 'query performance', 'consistency', 'trade-off'],
                'ideal_answer' => 'Prefer normalization for consistency, denormalize selectively for heavy read paths with clear update strategy.',
            ],
            [
                'topic' => 'Testing',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What is the role of unit vs feature tests in Laravel?'
                    : 'How do you balance unit, feature, and integration tests for backend reliability?',
                'keywords' => ['feature test', 'unit test', 'integration', 'confidence'],
                'ideal_answer' => 'Unit tests validate isolated logic; feature tests verify request-to-response behavior and integration confidence.',
            ],
            [
                'topic' => 'Architecture',
                'difficulty' => $level === 'mid' ? 'hard' : 'medium',
                'question' => 'How would you structure a growing Laravel codebase to avoid a fat-model/fat-controller problem?',
                'keywords' => ['service layer', 'action', 'domain', 'modular'],
                'ideal_answer' => 'Introduce clear layers (actions/services), group by domain, and keep models/controllers focused on their core responsibilities.',
            ],
            [
                'topic' => 'Communication',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'How would you explain a backend decision in simple terms?'
                    : 'How do you present backend trade-offs to non-technical stakeholders with clear impact?',
                'keywords' => ['latency', 'cost', 'risk', 'business impact'],
                'ideal_answer' => 'Translate technical options into cost, risk, timeline, and user impact with a clear recommendation.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fullstackQuestions(string $level): array
    {
        $difficulty = $level === 'junior' ? 'easy' : 'medium';

        return [
            [
                'topic' => 'Architecture Decisions',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'In a new feature, how do you decide what to implement on frontend vs backend?'
                    : 'How do you define ownership boundaries between frontend and backend for a complex feature?',
                'keywords' => ['separation of concerns', 'security', 'latency', 'ownership'],
                'ideal_answer' => 'Keep UI/interaction logic on frontend, business rules and sensitive operations on backend, then balance latency and maintainability.',
            ],
            [
                'topic' => 'API Design',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What makes an API easy to use from the frontend side?'
                    : 'How do you design API contracts that stay stable while products evolve?',
                'keywords' => ['consistent schema', 'pagination', 'errors', 'versioning'],
                'ideal_answer' => 'Consistent response shapes, predictable error formats, pagination/filtering conventions, and clear versioning make APIs easier to consume.',
            ],
            [
                'topic' => 'Authentication Flow',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'How would you build a secure login flow in a fullstack app?'
                    : 'How would you design authentication and session/token lifecycle for web and API clients?',
                'keywords' => ['token/session', 'authorization', 'refresh', 'secure storage'],
                'ideal_answer' => 'Use a secure auth mechanism (session or token), enforce authorization server-side, and handle token/session lifecycle safely.',
            ],
            [
                'topic' => 'State & Caching',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'How do frontend state and backend caching work together?'
                    : 'How do you coordinate frontend cache policies with backend cache invalidation?',
                'keywords' => ['client cache', 'server cache', 'stale data', 'invalidation'],
                'ideal_answer' => 'Client cache improves UX responsiveness while server cache reduces backend load; both need coherent invalidation strategies.',
            ],
            [
                'topic' => 'Database Performance',
                'difficulty' => $difficulty,
                'question' => 'What signs show your app is bottlenecked by database access?',
                'keywords' => ['slow queries', 'n+1', 'indexes', 'profiling'],
                'ideal_answer' => 'Slow query logs, N+1 patterns, and high DB latency indicate bottlenecks; optimize with indexing and query design.',
            ],
            [
                'topic' => 'Error Handling',
                'difficulty' => $difficulty,
                'question' => 'How do you design error handling so both frontend users and backend logs are served well?',
                'keywords' => ['user-friendly errors', 'structured logging', 'traceability', 'status codes'],
                'ideal_answer' => 'Expose safe, clear user messages and status codes to clients while keeping detailed structured logs for debugging.',
            ],
            [
                'topic' => 'Testing Strategy',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'How would you split tests between frontend, backend, and end-to-end?'
                    : 'How do you optimize fullstack test strategy for speed and release confidence?',
                'keywords' => ['unit', 'integration', 'e2e', 'critical flow'],
                'ideal_answer' => 'Use unit tests for isolated logic, integration tests for contracts, and E2E for top business-critical journeys.',
            ],
            [
                'topic' => 'CI/CD',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'What checks should a basic CI/CD pipeline run for a fullstack app?'
                    : 'What quality gates should an enterprise fullstack CI/CD pipeline enforce?',
                'keywords' => ['tests', 'lint', 'build', 'migrations', 'rollback'],
                'ideal_answer' => 'A robust pipeline runs quality checks, build steps, safe migration strategy, and supports rollback-friendly deployments.',
            ],
            [
                'topic' => 'Observability',
                'difficulty' => $level === 'mid' ? 'hard' : 'medium',
                'question' => 'How would you monitor a production fullstack app end-to-end?',
                'keywords' => ['metrics', 'logs', 'traces', 'alerting', 'sla'],
                'ideal_answer' => 'Track metrics, structured logs, and traces across frontend/backend with actionable alerts tied to SLA/SLO targets.',
            ],
            [
                'topic' => 'Communication',
                'difficulty' => $difficulty,
                'question' => $level === 'junior'
                    ? 'How do you align frontend and backend teammates when priorities conflict?'
                    : 'How do you drive cross-team trade-off decisions between frontend and backend priorities?',
                'keywords' => ['trade-off', 'scope', 'risk', 'impact'],
                'ideal_answer' => 'Make trade-offs explicit around scope, risk, and user impact, then align teams on a shared delivery plan.',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function prioritizeFocusTopic(array $questions, string $focusTopic): array
    {
        $focused = [];
        $others = [];

        foreach ($questions as $question) {
            if (($question['topic'] ?? null) === $focusTopic) {
                $focused[] = $question;
            } else {
                $others[] = $question;
            }
        }

        return [...$focused, ...$others];
    }
}
