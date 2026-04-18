<?php

namespace App\Services\Interview;

class LearningPlanBuilder
{
    /**
     * @param  array<int, string>  $gaps
     * @return array<int, array<string, string>>
     */
    public function build(array $gaps): array
    {
        $focus = array_values(array_unique($gaps));

        if ($focus === []) {
            $focus = [
                'Maintain consistency with timed mock interviews',
                'Refine answer clarity with STAR-style structure',
                'Deepen React and JavaScript fundamentals',
            ];
        }

        $days = [];
        for ($day = 1; $day <= 7; $day++) {
            $topic = $focus[($day - 1) % count($focus)];

            $days[] = [
                'day' => "Day {$day}",
                'focus' => $topic,
                'task' => $day % 2 === 0
                    ? 'Solve 3 targeted interview questions and self-review with rubric.'
                    : 'Study the topic for 45 minutes and write one ideal interview answer.',
            ];
        }

        return $days;
    }
}
