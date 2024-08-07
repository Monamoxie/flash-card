<?php

namespace Modules\Flashcard\Tests\Feature;

use App\Models\User;
use Modules\Flashcard\Tests\TestCase;
use Faker\Factory;
use Modules\Flashcard\Enums\FlashcardActionEnum;
use Modules\Flashcard\Enums\FlashcardPaginationEnum;
use Modules\Flashcard\Enums\FlashcardStatusEnum;
use Modules\Flashcard\Enums\FlashcardWelcomeScreenEnum;
use Modules\Flashcard\Models\Flashcard;

class FlashcardCommandTest extends TestCase
{

    public function test_entry_and_exit()
    {
        $this->artisan('flashcard:interactive')
            ->expectsOutput($this->flashcardConfig['messages']['welcome'])
            ->expectsChoice($this->flashcardConfig['prompts']['select_option'], FlashcardWelcomeScreenEnum::EXIT->value, $this->flashcardConfig['welcome_screen'])
            ->assertExitCode(0);
    }

    public function test_create_account_journey()
    {
        $email = fake()->email;
        $password = fake()->password;
        $passwordConfirmation = $password;
        $name = fake()->name;

        $interaction = $this->artisan('flashcard:interactive')
            ->expectsOutput($this->flashcardConfig['messages']['welcome'])
            ->expectsChoice($this->flashcardConfig['prompts']['select_option'], FlashcardWelcomeScreenEnum::CREATE_ACCOUNT->value, $this->flashcardConfig['welcome_screen']);

        // ::: provide an empty email address
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], '')
            ->expectsOutput($this->flashcardConfig['messages']['valid_email']);

        // ::: The program will keep insisting for a VALID EMAIL ADDRESS until one is provided
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], $email);

        // ::: password step with empty password
        $interaction->expectsQuestion('Enter your password', '')
            ->expectsOutput($this->flashcardConfig['messages']['password_length']);

        // ::: password step with non matching passwords
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_password'], $password)
            ->expectsQuestion($this->flashcardConfig['prompts']['re_enter_password'], 'wrongassword')
            ->expectsOutput($this->flashcardConfig['messages']['password_mismatch']);

        // ::: password step with CORRECT passwords
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_password'], $password)
            ->expectsQuestion($this->flashcardConfig['prompts']['re_enter_password'], $passwordConfirmation);

        // ::: collect NAME
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_name'], $name);

        // ::: finally, create the user
        $interaction->expectsOutput($this->flashcardConfig['messages']['account_created']);

        // ::: Then redisplay the main menu and exit
        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }

    public function test_create_account_journey_with_an_existing_user_data()
    {
        $user = $this->getTestUser();

        $interaction = $this->artisan('flashcard:interactive')
            ->expectsOutput($this->flashcardConfig['messages']['welcome'])
            ->expectsChoice($this->flashcardConfig['prompts']['select_option'], FlashcardWelcomeScreenEnum::CREATE_ACCOUNT->value, $this->flashcardConfig['welcome_screen'])

            ->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], $user->email)

            ->expectsQuestion($this->flashcardConfig['prompts']['enter_password'], $user->password)
            ->expectsQuestion($this->flashcardConfig['prompts']['re_enter_password'], $user->password);

        // ::: inform the user this email is already taken, 
        $interaction->expectsOutput($this->flashcardConfig['messages']['email_taken']);

        // ::: ask for a new email and have them go through that process again
        $newEmail = fake()->email;
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], $newEmail)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_password'], $user->password)
            ->expectsQuestion($this->flashcardConfig['prompts']['re_enter_password'], $user->password)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_name'], fake()->name);

        // ::: finally, create the user
        $interaction->expectsOutput($this->flashcardConfig['messages']['account_created']);

        // ::: Then redisplay the main menu and exit
        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }

    public function test_login_journey()
    {
        $user = $this->getTestUser();

        $interaction = $this->artisan('flashcard:interactive')
            ->expectsOutput($this->flashcardConfig['messages']['welcome'])
            ->expectsChoice($this->flashcardConfig['prompts']['select_option'], FlashcardWelcomeScreenEnum::LOGIN->value, $this->flashcardConfig['welcome_screen']);

        // ::: empty email address
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], '')
            ->expectsOutput($this->flashcardConfig['messages']['valid_email']);

        // ::: provide a valid email address but wrong password
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], $user->email)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_password'], 'wrongpassword');

        // ::: receive an error message
        $interaction->expectsOutput($this->flashcardConfig['messages']['invalid_credentials']);

        // :: provide correct details
        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], $user->email)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_password'], $user->password);

        // ::: receive a welcome message
        $interaction->expectsOutput($this->flashcardConfig['messages']['login_message']);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }

    public function test_create_flashcard_journey()
    {
        $question = fake()->sentence;
        $answer = fake()->sentence;

        $interaction = $this->loginUser();
        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::CREATE->value,
            $this->flashcardConfig['menu']
        )
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_new_question'], '')
            ->expectsOutput($this->flashcardConfig['messages']['question_cannot_be_empty'])
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_new_question'], $question)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_answer_for_new_question'], $answer);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }

    public function test_create_flashcard_journey_with_duplicate_question()
    {
        $interaction = $this->loginUser();

        $question = fake()->sentence;
        $answer = fake()->sentence;

        // ::: create a flashcard
        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::CREATE->value,
            $this->flashcardConfig['menu']
        )
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_new_question'], $question)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_answer_for_new_question'], $answer);

        // ::: create a flashcard with the same question and get an error message
        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::CREATE->value,
            $this->flashcardConfig['menu']
        )
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_new_question'], $question)
            ->expectsOutput($this->flashcardConfig['messages']['duplicate_question']);


        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_new_question'], fake()->sentence)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_answer_for_new_question'], fake()->sentence);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }

    public function test_list_flashcards_when_empty()
    {
        $interaction = $this->loginUser();

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::LIST->value,
            $this->flashcardConfig['menu']
        )->expectsOutput($this->flashcardConfig['messages']['no_flashcards']);


        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_pagination'],
            FlashcardPaginationEnum::RETURN->value,
            [FlashcardPaginationEnum::RETURN->value]
        );

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }

    public function test_list_flashcards_with_data()
    {
        $user = $this->getTestUser();

        $interaction = $this->loginUser();

        $flashcards = Flashcard::factory()->count(12)->create(['user_id' => $user->id]);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::LIST->value,
            $this->flashcardConfig['menu']
        );

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_pagination'],
            FlashcardPaginationEnum::RETURN->value,
            [FlashcardPaginationEnum::NEXT->value, FlashcardPaginationEnum::RETURN->value]
        );

        foreach ($flashcards as $flashcard) {
            $this->assertDatabaseHas('flashcards', [
                'question' => $flashcard->question,
                'answer' => $flashcard->answer,
                'user_id' => $user->id
            ]);
        }

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }


    public function test_update_flashcard()
    {
        $user = $this->getTestUser();

        $interaction = $this->loginUser();

        $flashcards = Flashcard::factory()->count(12)->create(['user_id' => $user->id]);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::UPDATE->value,
            $this->flashcardConfig['menu']
        );

        $interaction->expectsQuestion('Press R for Return to Main Menu, N for Next,  OR enter the ID of the row you wish to Update Flashcard', 'R');


        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }

    public function test_delete_flashcard()
    {
        $user = $this->getTestUser();

        $interaction = $this->loginUser();

        $flashcards = Flashcard::factory()->count(12)->create(['user_id' => $user->id]);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::DELETE->value,
            $this->flashcardConfig['menu']
        );

        $interaction->expectsQuestion('Press R for Return to Main Menu, N for Next,  OR enter the ID of the row you wish to Delete Flashcard', 'R');


        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }

    public function test_reset_flashcards()
    {
        $user = $this->getTestUser();

        $interaction = $this->loginUser();

        Flashcard::factory()->count(12)->create(['user_id' => $user->id]);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::RESET->value,
            $this->flashcardConfig['menu']
        );

        $interaction->expectsQuestion($this->flashcardConfig['prompts']['confirm_reset_status'], 'no');

        $interaction->expectsOutput($this->flashcardConfig['messages']['levels_intact']);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::RESET->value,
            $this->flashcardConfig['menu']
        );

        $interaction->expectsQuestion($this->flashcardConfig['prompts']['confirm_reset_status'], 'yes');

        $this->assertDatabaseHas('flashcards', [
            'user_id' => $user->id,
            'status' => FlashcardStatusEnum::NOT_ANSWERED->value,
        ]);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::EXIT->value,
            $this->flashcardConfig['menu']
        )->assertExitCode(0);
    }


    public function test_statistics()
    {
        $user = $this->getTestUser();

        $interaction = $this->loginUser();

        $totalUnanswered = fake()->numberBetween(1, 10);
        $totalCorrect = fake()->numberBetween(1, 10);
        $totalIncorrect = fake()->numberBetween(1, 10);

        $grandTotal = $totalUnanswered + $totalCorrect + $totalIncorrect;
        $grandCompleted = $totalCorrect + $totalIncorrect;

        Flashcard::factory()->count($totalUnanswered)->create([
            'user_id' => $user->id,
            'status' => FlashcardStatusEnum::NOT_ANSWERED->value
        ]);

        Flashcard::factory()->count($totalCorrect)->create([
            'user_id' => $user->id,
            'status' => FlashcardStatusEnum::CORRECT->value
        ]);

        Flashcard::factory()->count($totalIncorrect)->create([
            'user_id' => $user->id,
            'status' => FlashcardStatusEnum::INCORRECT->value
        ]);

        $interaction->expectsChoice(
            $this->flashcardConfig['prompts']['select_option'],
            FlashcardActionEnum::STATISTICS->value,
            $this->flashcardConfig['menu']
        );

        $interaction->expectsOutputToContain("TOTAL QUESTIONS: " . $grandTotal);
        $interaction->expectsOutputToContain("TOTAL COMPLETED: " . $grandCompleted);
        $interaction->expectsOutputToContain("TOTAL CORRECT: " . $totalCorrect);

        $interaction->assertExitCode(0);
    }
}
