<?php

namespace Modules\Flashcard\Enums;

enum FlashcardStatusEnum: string
{
    case NOT_ANSWERED = 'not_answered';
    case INCORRECT = 'incorrect';
    case CORRECT = 'correct';
}
