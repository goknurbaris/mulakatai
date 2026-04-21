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
                'question' => 'How do you keep Laravel controllers thin and maintainable?',
                'keywords' => ['service class', 'validation', 'single responsibility', 'business logic'],
                'ideal_answer' => 'Move business logic to services/actions, keep validation in Form Requests, and let controllers orchestrate request/response flow.',
            ],
            [
                'topic' => 'Eloquent',
                'difficulty' => $difficulty,
                'question' => 'What causes N+1 queries and how do you prevent them in Laravel?',
                'keywords' => ['eager loading', 'with', 'relationships', 'n+1'],
                'ideal_answer' => 'N+1 happens when relationships are loaded in loops. Prevent it with eager loading using with() and query planning.',
            ],
            [
                'topic' => 'Validation',
                'difficulty' => $difficulty,
                'question' => 'When would you use Form Requests instead of inline validation?',
                'keywords' => ['form request', 'reusability', 'authorization', 'clean controller'],
                'ideal_answer' => 'Form Requests improve reuse, authorization handling, and controller cleanliness for non-trivial validation rules.',
            ],
            [
                'topic' => 'Queues',
                'difficulty' => $difficulty,
                'question' => 'Which tasks should be moved to queues in a web application?',
                'keywords' => ['email', 'background jobs', 'slow tasks', 'user experience'],
                'ideal_answer' => 'Slow non-blocking tasks like emails, exports, and heavy API work should run in queues to keep requests fast.',
            ],
            [
                'topic' => 'Caching',
                'difficulty' => $difficulty,
                'question' => 'How would you design cache invalidation for frequently accessed data?',
                'keywords' => ['ttl', 'cache tags', 'invalidation', 'freshness'],
                'ideal_answer' => 'Use TTL and event-driven invalidation; invalidate when underlying records change to balance freshness and performance.',
            ],
            [
                'topic' => 'Auth & Security',
                'difficulty' => $difficulty,
                'question' => 'What are your first security checks in a Laravel API project?',
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
                'question' => 'How do feature tests and unit tests complement each other in Laravel?',
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
                'question' => 'How do you explain backend trade-offs to non-technical stakeholders?',
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
                'question' => 'How do you decide what belongs to frontend vs backend in a new feature?',
                'keywords' => ['separation of concerns', 'security', 'latency', 'ownership'],
                'ideal_answer' => 'Keep UI/interaction logic on frontend, business rules and sensitive operations on backend, then balance latency and maintainability.',
            ],
            [
                'topic' => 'API Design',
                'difficulty' => $difficulty,
                'question' => 'What makes an API easy for frontend teams to consume?',
                'keywords' => ['consistent schema', 'pagination', 'errors', 'versioning'],
                'ideal_answer' => 'Consistent response shapes, predictable error formats, pagination/filtering conventions, and clear versioning make APIs easier to consume.',
            ],
            [
                'topic' => 'Authentication Flow',
                'difficulty' => $difficulty,
                'question' => 'How would you implement authentication in a fullstack app?',
                'keywords' => ['token/session', 'authorization', 'refresh', 'secure storage'],
                'ideal_answer' => 'Use a secure auth mechanism (session or token), enforce authorization server-side, and handle token/session lifecycle safely.',
            ],
            [
                'topic' => 'State & Caching',
                'difficulty' => $difficulty,
                'question' => 'How do client-side state and server-side caching complement each other?',
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
                'question' => 'How do you split tests across frontend, backend, and end-to-end?',
                'keywords' => ['unit', 'integration', 'e2e', 'critical flow'],
                'ideal_answer' => 'Use unit tests for isolated logic, integration tests for contracts, and E2E for top business-critical journeys.',
            ],
            [
                'topic' => 'CI/CD',
                'difficulty' => $difficulty,
                'question' => 'What should a good CI/CD pipeline validate for fullstack projects?',
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
                'question' => 'How do you communicate cross-team trade-offs between frontend and backend priorities?',
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
