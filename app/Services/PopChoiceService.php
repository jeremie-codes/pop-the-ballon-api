<?php

namespace App\Services;

use App\Models\PopChoice;
use App\Models\PopChoiceAnswer;
use App\Models\PopChoiceSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PopChoiceService
{
    /**
     * Maximum number of automatic Pop Choices per day.
     */
    private const DAILY_LIMIT = 3;

    /**
     * Minimum delay between two automatic Pop Choices.
     */
    private const COOLDOWN_HOURS = 2;

    /**
     * Maximum number of questions in a voluntary session.
     */
    private const SESSION_LIMIT = 5;

    /**
     * Get the next automatic Pop Choice.
     *
     * Automatic mode:
     * - daily limit applies
     * - cooldown applies
     * - unanswered questions only
     * - category balancing
     * - question weight
     */
    public function getNextAutomatic(User $user): ?PopChoice
    {
        if (!$this->canShowAutomaticQuestion($user)) {
            return null;
        }

        return $this->selectBestQuestion($user);
    }

    /**
     * Start a new voluntary Pop Choice session.
     */
    public function startSession(User $user): PopChoiceSession
    {
        /*
         * If the user already has an active session,
         * return it instead of creating another one.
         */
        $existingSession = PopChoiceSession::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        if ($existingSession) {
            return $existingSession;
        }

        return PopChoiceSession::create([
            'user_id' => $user->id,
            'started_at' => now(),
        ]);
    }

    /**
     * Get the next question for a voluntary session.
     */
    public function getNextForSession(
        User $user,
        PopChoiceSession $session
    ): ?PopChoice {
        $this->ensureSessionBelongsToUser($user, $session);

        /*
         * If the session is already completed,
         * no more questions can be requested.
         */
        if ($session->completed_at !== null) {
            return null;
        }

        /*
         * Get answers belonging specifically to this session.
         */
        $sessionAnswers = $session->answers()
            ->latest('answered_at')
            ->get();

        /*
         * Session limit.
         */
        if ($sessionAnswers->count() >= self::SESSION_LIMIT) {
            $this->completeSession($session);

            return null;
        }

        return $this->selectBestQuestion(
            $user,
            $sessionAnswers
        );
    }

    /**
     * Save an answer.
     *
     * If a session is provided, the answer belongs to that session.
     * Otherwise it is an automatic Pop Choice answer.
     */
    public function answer(
        User $user,
        PopChoice $popChoice,
        string $answer,
        ?PopChoiceSession $session = null
    ): PopChoiceAnswer {
        if (!in_array($answer, ['a', 'b'], true)) {
            throw new RuntimeException('Invalid Pop Choice answer.');
        }

        if (!$popChoice->is_active) {
            throw new RuntimeException(
                'This Pop Choice is no longer available.'
            );
        }

        if ($session) {
            $this->ensureSessionBelongsToUser(
                $user,
                $session
            );

            if ($session->completed_at !== null) {
                throw new RuntimeException(
                    'This Pop Choice session is already completed.'
                );
            }

            /*
             * Prevent answering more than the session limit.
             */
            $sessionAnswerCount = $session->answers()->count();

            if ($sessionAnswerCount >= self::SESSION_LIMIT) {
                $this->completeSession($session);

                throw new RuntimeException(
                    'The Pop Choice session has reached its limit.'
                );
            }
        } else {
            /*
             * Automatic mode.
             *
             * We check the daily limit and cooldown again here.
             * This is important because the client cannot bypass
             * these rules simply by calling the answer endpoint.
             */
            if (!$this->canShowAutomaticQuestion($user)) {
                throw new RuntimeException(
                    'You cannot answer an automatic Pop Choice right now.'
                );
            }
        }

        /*
         * Prevent answering the same question more than once.
         */
        $alreadyAnswered = PopChoiceAnswer::query()
            ->where('user_id', $user->id)
            ->where('pop_choice_id', $popChoice->id)
            ->exists();

        if ($alreadyAnswered) {
            throw new RuntimeException(
                'You have already answered this Pop Choice.'
            );
        }

        return DB::transaction(function () use (
            $user,
            $popChoice,
            $answer,
            $session
        ) {
            $popChoiceAnswer = PopChoiceAnswer::create([
                'user_id' => $user->id,
                'pop_choice_id' => $popChoice->id,
                'pop_choice_session_id' => $session?->id,
                'answer' => $answer,
                'answered_at' => now(),
            ]);

            /*
             * Automatically complete the session after
             * the fifth answer.
             */
            if ($session) {
                $answerCount = $session->answers()->count();

                if ($answerCount >= self::SESSION_LIMIT) {
                    $this->completeSession($session);
                }
            }

            return $popChoiceAnswer;
        });
    }

    /**
     * Complete a voluntary session.
     */
    public function completeSession(
        PopChoiceSession $session
    ): PopChoiceSession {
        if ($session->completed_at === null) {
            $session->update([
                'completed_at' => now(),
            ]);
        }

        return $session->fresh();
    }

    /**
     * Check whether an automatic Pop Choice can be displayed.
     */
    public function canShowAutomaticQuestion(User $user): bool
    {
        /*
         * 1. Daily limit.
         */
        $todayCount = PopChoiceAnswer::query()
            ->where('user_id', $user->id)
            ->whereNull('pop_choice_session_id')
            ->whereDate('answered_at', today())
            ->count();

        if ($todayCount >= self::DAILY_LIMIT) {
            return false;
        }

        /*
         * 2. Cooldown.
         *
         * Only automatic Pop Choices affect the automatic cooldown.
         * A voluntary session should not prevent the next automatic
         * Pop Choice from appearing later.
         */
        $lastAutomaticAnswer = PopChoiceAnswer::query()
            ->where('user_id', $user->id)
            ->whereNull('pop_choice_session_id')
            ->latest('answered_at')
            ->first();

        if (!$lastAutomaticAnswer) {
            return true;
        }

        return $lastAutomaticAnswer->answered_at
            ->addHours(self::COOLDOWN_HOURS)
            ->isPast();
    }

    /**
     * Select the best unanswered question.
     *
     * Selection takes into account:
     *
     * - unanswered questions
     * - category balance
     * - weight
     * - randomization
     */
    protected function selectBestQuestion(
        User $user,
        ?Collection $sessionAnswers = null
    ): ?PopChoice {
        /*
         * All questions already answered by the user.
         */
        $answeredQuestionIds = PopChoiceAnswer::query()
            ->where('user_id', $user->id)
            ->pluck('pop_choice_id');

        /*
         * During a session, the answers are already part of
         * the user's answers, but we keep this merge for safety.
         */
        if ($sessionAnswers !== null) {
            $sessionQuestionIds = $sessionAnswers
                ->pluck('pop_choice_id');

            $answeredQuestionIds = $answeredQuestionIds
                ->merge($sessionQuestionIds)
                ->unique();
        }

        /*
         * Candidate questions.
         */
        $questions = PopChoice::query()
            ->where('is_active', true)
            ->whereNotIn('id', $answeredQuestionIds)
            ->get();

        if ($questions->isEmpty()) {
            return null;
        }

        /*
         * Count answers by category.
         */
        $categoryCounts = PopChoiceAnswer::query()
            ->selectRaw('pop_choices.category, COUNT(*) as total')
            ->join('pop_choices', 'pop_choices.id', '=', 'pop_choice_answers.pop_choice_id')
            ->where('pop_choice_answers.user_id', $user->id)
            ->groupBy('pop_choices.category')
            ->pluck(
                'total',
                'pop_choices.category'
            );

        /*
         * Calculate a score for every candidate.
         */
        $scoredQuestions = $questions->map(
            function (PopChoice $question) use (
                $categoryCounts
            ) {
                $category = $question->category->value;

                $categoryCount = $categoryCounts[$category] ?? 0;

                /*
                 * Categories with fewer answers get priority.
                 *
                 * 0 answers => 10
                 * 1 answer  => 8
                 * 2 answers => 6
                 * 3 answers => 4
                 * 4 answers => 2
                 * 5+        => 1
                 */
                $categoryScore = max(
                    1,
                    10 - ($categoryCount * 2)
                );

                /*
                 * Question importance.
                 *
                 * weight 1 => +2
                 * weight 2 => +4
                 * ...
                 * weight 5 => +10
                 */
                $weightScore = $question->weight * 2;

                /*
                 * Random component.
                 *
                 * This prevents the system from always choosing
                 * the exact same question when several questions
                 * have similar scores.
                 */
                $randomScore = random_int(0, 10);

                $score =
                    $categoryScore +
                    $weightScore +
                    $randomScore;

                return [
                    'question' => $question,
                    'score' => $score,
                ];
            }
        );

        return $scoredQuestions
            ->sortByDesc('score')
            ->first()['question'] ?? null;
    }

    /**
     * Make sure the session belongs to the authenticated user.
     */
    protected function ensureSessionBelongsToUser(
        User $user,
        PopChoiceSession $session
    ): void {
        if ($session->user_id !== $user->id) {
            throw new RuntimeException(
                'This Pop Choice session does not belong to you.'
            );
        }
    }
}
