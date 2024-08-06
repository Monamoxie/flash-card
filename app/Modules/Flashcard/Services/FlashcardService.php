<?php

namespace Modules\Flashcard\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Flashcard\Models\Flashcard;

class FlashcardService
{
    public static function getEntries(User $user)
    {
        return $user->flashcards()->select('question', 'answer', 'status')->get();
    }

    public static function getTotalEntriesFor(User $user): int
    {
        return $user->flashcards()->count();
    }

    public static function getPaginatedEntriesFor(User $user, int $page, int $perPage): Collection
    {
        return $user->flashcards()->select('question', 'answer', 'status')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
    }

    public function newEntry(string $question, string $answer, User $user): Flashcard
    {
        $flashcard = new Flashcard;
        $flashcard->question = $question;
        $flashcard->answer = $answer;
        $flashcard->user()->associate($user);
        $flashcard->save();

        return $flashcard;
    }

    public function isQuestionExists(string $question, int $userId): bool
    {
        return Flashcard::where('question', $question)->where('user_id', $userId)->exists();
    }
}
