<?php

namespace Modules\Flashcard\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Modules\Flashcard\Contracts\SessionInterface;
use Modules\Flashcard\Enums\FlashcardActionEnum;
use Modules\Flashcard\Enums\FlashcardStatusEnum;
use Modules\Flashcard\Enums\FlashcardWelcomeScreenEnum;
use Modules\Flashcard\Models\Flashcard;
use Modules\Flashcard\Services\UserService;
use Modules\Flashcard\Services\CommandService;
use Modules\Flashcard\Services\FlashcardService;
use Modules\Flashcard\Traits\FlashcardUtilities;

class FlashcardCommand extends Command implements Isolatable
{
    use FlashcardUtilities;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flashcard:interactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explore and manage your flashcards';

    protected ?User $user = null;

    protected UserService $userService;

    protected FlashcardService $flashcardService;

    protected array $flashcardConfig = [];

    public function __construct(UserService $userService, FlashcardService $flashcardService)
    {
        parent::__construct();
        $this->userService = $userService;
        $this->flashcardService = $flashcardService;
        $this->flashcardConfig = config('flashcard');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info($this->flashcardConfig['messages']['welcome']);

        $choice = $this->choice($this->flashcardConfig['prompts']['select_option'], $this->flashcardConfig['welcome_screen']);
        switch ($choice) {
            case FlashcardWelcomeScreenEnum::LOGIN->value:
                $this->login();
                break;
            case FlashcardWelcomeScreenEnum::CREATE_ACCOUNT->value:
                $this->createAccount();
                break;
            case FlashcardActionEnum::EXIT->value:
                $this->exit();
                return;
            default:
                $this->error($this->flashcardConfig['messages']['invalid_option']);
                break;
        }

        $this->displayMenu();
    }

    protected function createAccount()
    {
        $userExists = true;
        $email = '';
        $password = '';

        while ($userExists) {
            $email = $this->askForEmail();
            $password = $this->askForPassword();

            if (UserService::checkEmailExists($email)) {
                $this->error($this->flashcardConfig['messages']['email_taken']);
            } else {
                $userExists = false;
            }
        }
        $name = $this->ask($this->flashcardConfig['prompts']['enter_name']);

        $user = UserService::create($email, $password, $name);
        if (!$user) {
            $this->error($this->flashcardConfig['messages']['account_not_created']);
            return 0;
        }

        $this->user = $user;

        $this->info($this->flashcardConfig['messages']['account_created']);
        return 0;
    }

    protected function login()
    {
        $validCredentials = false;

        while (!$validCredentials) {
            $email = $this->askForEmail();
            $password = $this->secret($this->flashcardConfig['prompts']['enter_password']);

            $validUser = UserService::verifyCredentials($email, $password);

            if ($validUser) {
                $this->info($this->flashcardConfig['messages']['login_message']);
                $this->setUser($validUser);
                $validCredentials = true;
            } else {
                $this->error($this->flashcardConfig['messages']['invalid_credentials']);
            }
        }
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
