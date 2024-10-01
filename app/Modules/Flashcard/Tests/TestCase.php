<?php

namespace Modules\Flashcard\Tests;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Flashcard\Enums\FlashcardWelcomeScreenEnum;
use Modules\Flashcard\Services\UserService;
use stdClass;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected array $flashcardConfig = [];

    protected ?User $testUserModel = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->flashcardConfig = config('flashcard');

        if (config('app.env') !== 'testing') {
            throw new Exception('This should only be triggered in a testing environment');
        }
    }

    protected function getTestUser($requiresModel = false): stdClass|User
    {
        $password = '123456';

        // ::: re-use the existing user data if it exists in local state
        $this->testUserModel = UserService::create(fake()->email, $password, fake()->name);

        // generate an std class with the unhashed password, for use in test assertions
        $user = new stdClass;
        $user->email = $this->testUserModel->email;
        $user->password = $password;
        $user->name = $this->testUserModel->name;
        $user->id = $this->testUserModel->id;

        if ($requiresModel) {
            return $this->testUserModel;
        }

        return $user;
    }

    protected function loginUser()
    {
        if (!is_null($this->testUserModel)) {
            $user = $this->testUserModel;
        } else {
            $user = $this->getTestUser();
        }

        $interaction = $this->artisan('flashcard:interactive')
            ->expectsChoice($this->flashcardConfig['prompts']['select_option'], FlashcardWelcomeScreenEnum::LOGIN->value, $this->flashcardConfig['welcome_screen']);

        $interaction->expectsQuestion($this->flashcardConfig['prompts']['enter_email'], $user->email)
            ->expectsQuestion($this->flashcardConfig['prompts']['enter_password'], '123456');


        return $interaction;
    }
}
