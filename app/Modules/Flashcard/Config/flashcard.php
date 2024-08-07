<?php

use Modules\Flashcard\Enums\FlashcardActionEnum;
use Modules\Flashcard\Enums\FlashcardModalEnum;
use Modules\Flashcard\Enums\FlashcardPaginationEnum;
use Modules\Flashcard\Enums\FlashcardWelcomeScreenEnum;

return [
    'menu' => [
        FlashcardActionEnum::LIST->value,
        FlashcardActionEnum::CREATE->value,
        FlashcardActionEnum::UPDATE->value,
        FlashcardActionEnum::DELETE->value,
        FlashcardActionEnum::PRACTICE->value,
        FlashcardActionEnum::STATISTICS->value,
        FlashcardActionEnum::RESET->value,
        FlashcardActionEnum::EXIT->value
    ],
    'welcome_screen' => [
        FlashcardWelcomeScreenEnum::LOGIN->value,
        FlashcardWelcomeScreenEnum::CREATE_ACCOUNT->value,
        FlashcardActionEnum::EXIT->value
    ],
    'modal' => [
        FlashcardModalEnum::CREATE_ANOTHER->value,
        FlashcardModalEnum::RETURN->value,
        FlashcardModalEnum::EXIT->value
    ],
    'pagination' => [
        FlashcardPaginationEnum::RETURN->value,
        FlashcardPaginationEnum::PREVIOUS->value,
        FlashcardPaginationEnum::NEXT->value

    ],
    'prompts' => [
        'select_option' => 'Please select an option',
        'select_pagination' => 'Pagination',
        'enter_email' => 'Enter your email',
        'enter_password' => 'Enter your password',
        're_enter_password' => 'Please re-enter your password',
        'enter_name' => 'Enter your name',
        'enter_new_question'  => 'Enter a new question',
        'enter_answer_for_new_question' => 'Enter the answer for the provided question',
    ],
    'messages' => [
        'welcome' => 'Welcome to Flashcard!',
        'email_taken' => 'This email has already been taken. Please try another one',
        'account_created' => 'Account has been created successfully',
        'account_not_created' => 'Account could not be created. Please try again after some time',
        'password_mismatch' => 'Password mismatch. Please try again',
        'password_length' => 'Password must be at least 3 or more characters',
        'valid_email' => 'Please provide a valid email address',
        'invalid_credentials' => 'Invalid Email/Password supplied. Let\'s try again!',
        'login_message' => 'Welcome back!',
        'question_cannot_be_empty' => 'Question cannot be empty. Please try again',
        'duplicate_question' => 'This is a duplicate question. Please try another question',
        'entry_could_not_be_processed' => 'Your entry could not be processed at this moment. Please try again later',
        'new_entry_created' => 'Your question has been successfully created!',
        'invalid_option' => 'Invalid option. Please try again',
        'no_flashcards' => 'No flashcards found!'
    ],
];
