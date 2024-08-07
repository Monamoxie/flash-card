<?php

namespace Modules\Flashcard\Tests\Unit;

use App\Models\User;
use Modules\Flashcard\Enums\FlashcardStatusEnum;
use Modules\Flashcard\Models\Flashcard;
use Modules\Flashcard\Services\FlashcardService;
use Modules\Flashcard\Tests\TestCase;

class FlashcardServiceTest extends TestCase
{

    public function test_get_entries()
    {
        $user = $this->getTestUser(true);
        $flashcard = Flashcard::factory()->create(['user_id' => $user->id]);

        $entries = FlashcardService::getEntries($user);

        $this->assertCount(1, $entries);
        $this->assertEquals($flashcard->id, $entries[0]->id);
    }

    public function test_get_total_entries_for()
    {
        $user = $this->getTestUser(true);
        Flashcard::factory()->count(3)->create(['user_id' => $user->id]);

        $totalEntries = FlashcardService::getTotalEntriesFor($user);

        $this->assertEquals(3, $totalEntries);
    }

    public function test_get_paginated_entries_for()
    {
        $user = $this->getTestUser(true);
        Flashcard::factory()->count(5)->create(['user_id' => $user->id]);

        $page = 2;
        $perPage = 2;

        $paginatedEntries = FlashcardService::getPaginatedEntriesFor($user, $page, $perPage);

        $this->assertCount(2, $paginatedEntries);
    }

    public function test_new_entry()
    {
        $user = $this->getTestUser(true);
        $question = fake()->sentence;
        $answer = fake()->sentence;

        $flashcard = FlashcardService::newEntry($question, $answer, $user);

        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcard->id,
            'question' => $question,
            'answer' => $answer,
            'user_id' => $user->id,
        ]);
    }

    public function test_update_entry()
    {
        $user = $this->getTestUser(true);
        $flashcard = Flashcard::factory()->create(['user_id' => $user->id]);

        $question = fake()->sentence;
        $answer = fake()->sentence;

        $updatedFlashcard = FlashcardService::updateEntry($flashcard->id, $question, $answer, $user);

        $this->assertEquals($question, $updatedFlashcard->question);
        $this->assertEquals($answer, $updatedFlashcard->answer);
    }

    public function test_update_status()
    {
        $user = $this->getTestUser(true);
        $flashcard = Flashcard::factory()->create(['user_id' => $user->id]);
        $status = FlashcardStatusEnum::CORRECT->value;

        $result = FlashcardService::updateStatus($flashcard->id, $status, $user);

        $this->assertTrue($result);

        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcard->id,
            'status' => $status,
        ]);
    }

    public function test_delete_entry()
    {
        $user = $this->getTestUser(true);
        $flashcard = Flashcard::factory()->create(['user_id' => $user->id]);

        $result = FlashcardService::deleteEntry($flashcard->id, $user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('flashcards', [
            'id' => $flashcard->id,
        ]);
    }

    public function test_is_question_exists()
    {
        $user = $this->getTestUser(true);
        $question = 'What is the capital of France?';
        Flashcard::factory()->create(['question' => $question, 'user_id' => $user->id]);

        $exists = FlashcardService::isQuestionExists($question, $user->id);

        $this->assertTrue($exists);
    }

    public function test_get_percentage_answered()
    {
        $user = $this->getTestUser(true);
        Flashcard::factory()->count(5)->create(['user_id' => $user->id, 'status' => FlashcardStatusEnum::CORRECT->value]);
        Flashcard::factory()->count(3)->create(['user_id' => $user->id, 'status' => FlashcardStatusEnum::INCORRECT->value]);

        $percentage = FlashcardService::getPercentageAnswered($user);

        $this->assertEquals(62.5, $percentage);
    }

    public function test_get_statistics()
    {
        $user = $this->getTestUser(true);
        Flashcard::factory()->count(10)->create(['user_id' => $user->id, 'status' => FlashcardStatusEnum::CORRECT->value]);
        Flashcard::factory()->count(5)->create(['user_id' => $user->id, 'status' => FlashcardStatusEnum::INCORRECT->value]);

        $statistics = FlashcardService::getStatistics($user);

        $this->assertEquals(15, $statistics['total_questions']);
        $this->assertEquals(15, $statistics['total_completed']);
        $this->assertEquals(10, $statistics['total_correct']);
        $this->assertEquals(100, $statistics['percentage_completed']);
        $this->assertEquals(66.67, $statistics['percentage_correct']);
    }

    public function test_reset_all_status()
    {
        $user = $this->getTestUser(true);
        $total = fake()->numberBetween(1, 10);
        Flashcard::factory()->count($total)->create(['user_id' => $user->id, 'status' => FlashcardStatusEnum::CORRECT->value]);

        $result = FlashcardService::resetAllStatus($user);

        $this->assertEquals($total, $result);
        $this->assertDatabaseHas('flashcards', [
            'user_id' => $user->id,
            'status' => FlashcardStatusEnum::NOT_ANSWERED->value,
        ]);
    }
}
