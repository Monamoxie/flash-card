<?php

namespace Modules\Flashcard\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Flashcard\Enums\FlashcardStatusEnum;
use Modules\Flashcard\Models\Flashcard;

class FlashcardService
{
    public static function getEntries(User $user)
    {
        return $user->flashcards()->orderBy('id', 'ASC')->select('id', 'question', 'answer', 'status')->get();
    }

    public static function getTotalEntriesFor(User $user): int
    {
        return $user->flashcards()->count();
    }

    public static function getPaginatedEntriesFor(User $user, int $page, int $perPage): Collection
    {
        return $user->flashcards()->select('id', 'question', 'answer', 'status')
            ->orderBy('id', 'ASC')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
    }

    public static function newEntry(string $question, string $answer, User $user): Flashcard
    {
        $flashcard = new Flashcard;
        $flashcard->question = $question;
        $flashcard->answer = $answer;
        $flashcard->user()->associate($user);
        $flashcard->save();

        return $flashcard;
    }

    public static function updateEntry(int $flashcardId, string $question, string $answer, User $user): Flashcard
    {
        $flashcard = $user->flashcards()->where('id', $flashcardId)->first();
        $flashcard->question = $question;
        $flashcard->answer = $answer;
        $flashcard->save();

        return $flashcard;
    }

    public static function updateStatus(int $flashcardId, string $status, User $user): bool
    {
        return $user->flashcards()->where('id', $flashcardId)->update([
            'status' => $status
        ]);
    }

    public static function deleteEntry(int $flashcardId, User $user): bool
    {
        return $user->flashcards()->where('id', $flashcardId)->delete();
    }

    public static function isQuestionExists(string $question, int $userId): bool
    {
        return Flashcard::where('question', $question)->where('user_id', $userId)->exists();
    }

    public static function getPercentageAnswered(User $user): int|float
    {
        $percent = 0;
        $record = DB::table('flashcards')
            ->where('user_id', $user->id)
            ->selectRaw('(SUM(CASE WHEN status = "' . FlashcardStatusEnum::CORRECT->value . '"  THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS percentage_correct')
            ->first();
        $percent = !empty($record->percentage_correct) ? $record->percentage_correct : 0;

        return round($percent, 2);
    }

    public static function getStatistics(User $user): array
    {
        $result = DB::table('flashcards')
            ->where('user_id', $user->id)
            ->selectRaw('COUNT(*) AS total_questions')
            ->selectRaw('SUM(CASE WHEN status IN ("' . FlashcardStatusEnum::CORRECT->value . '", "' . FlashcardStatusEnum::INCORRECT->value . '") THEN 1 ELSE 0 END) AS total_completed')
            ->selectRaw('SUM(CASE WHEN status = "' . FlashcardStatusEnum::CORRECT->value . '" THEN 1 ELSE 0 END) AS total_correct')
            ->selectRaw('(SUM(CASE WHEN status IN ("' . FlashcardStatusEnum::CORRECT->value . '", "' . FlashcardStatusEnum::INCORRECT->value . '") THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS percentage_completed')
            ->selectRaw('(SUM(CASE WHEN status = "' . FlashcardStatusEnum::CORRECT->value . '" THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS percentage_correct')
            ->first();

        return [
            'total_questions' => $result->total_questions ?? 0,
            'total_completed' => $result->total_completed ?? 0,
            'total_correct' => $result->total_correct ?? 0,
            'percentage_completed' => !empty($result->percentage_completed) ? round($result->percentage_completed, 2) : 0,
            'percentage_correct' => !empty($result->percentage_correct) ? round($result->percentage_correct, 2) : 0,
        ];
    }

    public static function resetAllStatus(User $user): bool|int
    {
        return $user->flashcards()->update([
            'status' => FlashcardStatusEnum::NOT_ANSWERED->value
        ]);
    }
}
