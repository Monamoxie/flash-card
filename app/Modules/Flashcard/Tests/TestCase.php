<?php

namespace Modules\Flashcard\Tests;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Modules\Flashcard\Console\Commands\FlashcardCommand;
use Modules\Flashcard\Enums\FlashcardActionEnum;
use Modules\Flashcard\Enums\FlashcardWelcomeScreenEnum;
use Modules\Flashcard\Models\Flashcard;
use Modules\Flashcard\Services\UserService;
use ReflectionClass;
use stdClass;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected array $flashcardConfig = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->flashcardConfig = config('flashcard');

        if (config('app.env') !== 'testing') {
            throw new Exception('This should only be triggered in a testing environment');
        }

        // $this->withoutExceptionHandling();
    }

    protected function getTestUser($model = false): stdClass|User
    {
        $email = fake()->email;
        $password = fake()->password;
        $name = fake()->name;

        $userModel = UserService::create($email, $password, $name);

        // generate an std class with the unhashed password, for use in test assertions
        $user = new stdClass;
        $user->email = $email;
        $user->password = $password;
        $user->name = $name;
        $user->id = $userModel->id;

        if ($model) {
            return $userModel;
        }

        return $user;
    }

    protected function loginUser()
    {
        $user = $this->getTestUser();

        $interaction = $this->artisan('flashcard:interactive')
            ->expectsChoice($this->flashcardConfig['prompts']['select_option'], FlashcardWelcomeScreenEnum::LOGIN->value, $this->flashcardConfig['welcome_screen']);

        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], $user->email)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_password'], $user->password)
            ->expectsOutput($this->flashcardConfig['messages']['login_message']);

        return $interaction;
    }
}
