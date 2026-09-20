<?php

namespace App\Services;

use App\Models\MatchModel;
use App\Models\PopChoiceAnswer;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class PopChoiceCompatibilityService
{
    public const MIN_SHARED_ANSWERS = 5;

    /**
     * Build a private compatibility estimate for two confirmed matches.
     * Individual answers are deliberately never returned to the client.
     */
    public function calculate(User $user, User $match): array
    {
        if (!$this->areMatched($user, $match)) {
            throw new RuntimeException(
                'Compatibility is only available for confirmed matches.'
            );
        }

        $userAnswers = $this->answersByQuestion($user);
        $matchAnswers = $this->answersByQuestion($match);

        $sharedAnswers = $userAnswers->intersectByKeys($matchAnswers);
        $sharedCount = $sharedAnswers->count();

        if ($sharedCount < self::MIN_SHARED_ANSWERS) {
            return [
                'available' => false,
                'score' => null,
                'shared_answers_count' => $sharedCount,
                'minimum_shared_answers' => self::MIN_SHARED_ANSWERS,
                'confidence' => 'insufficient',
                'categories' => (object) [],
            ];
        }

        $totalWeight = 0;
        $matchedWeight = 0;
        $categoryScores = [];

        foreach ($sharedAnswers as $questionId => $userAnswer) {
            $matchAnswer = $matchAnswers->get($questionId);
            $weight = max(1, (int) $userAnswer->popChoice->weight);
            $category = $userAnswer->popChoice->category->value;
            $sameAnswer = $userAnswer->answer === $matchAnswer->answer;

            $totalWeight += $weight;
            $matchedWeight += $sameAnswer ? $weight : 0;

            $categoryScores[$category] ??= [
                'total_weight' => 0,
                'matched_weight' => 0,
                'count' => 0,
            ];

            $categoryScores[$category]['total_weight'] += $weight;
            $categoryScores[$category]['matched_weight'] += $sameAnswer ? $weight : 0;
            $categoryScores[$category]['count']++;
        }

        $categories = collect($categoryScores)
            ->filter(fn (array $category) => $category['count'] >= 2)
            ->map(fn (array $category) => (int) round(
                ($category['matched_weight'] / $category['total_weight']) * 100
            ))
            ->all();

        return [
            'available' => true,
            'score' => (int) round(($matchedWeight / $totalWeight) * 100),
            'shared_answers_count' => $sharedCount,
            'minimum_shared_answers' => self::MIN_SHARED_ANSWERS,
            'confidence' => $this->confidenceFor($sharedCount),
            'categories' => (object) $categories,
        ];
    }

    private function areMatched(User $user, User $match): bool
    {
        if ($user->is($match)) {
            return false;
        }

        return MatchModel::query()
            ->where(function ($query) use ($user, $match) {
                $query->where('user_one_id', $user->id)
                    ->where('user_two_id', $match->id);
            })
            ->orWhere(function ($query) use ($user, $match) {
                $query->where('user_one_id', $match->id)
                    ->where('user_two_id', $user->id);
            })
            ->exists();
    }

    private function answersByQuestion(User $user): Collection
    {
        return PopChoiceAnswer::query()
            ->with('popChoice:id,category,weight')
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('pop_choice_id');
    }

    private function confidenceFor(int $sharedCount): string
    {
        return match (true) {
            $sharedCount >= 12 => 'high',
            $sharedCount >= 8 => 'medium',
            default => 'low',
        };
    }
}
