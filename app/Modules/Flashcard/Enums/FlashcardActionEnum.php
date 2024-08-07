<?php

namespace Modules\Flashcard\Enums;

enum FlashcardActionEnum: string
{
    case LIST = 'List Flashcards';
    case CREATE = 'Create Flashcard';
    case UPDATE = 'Update Flashcard';
    case DELETE = 'Delete Flashcard';
    case PRACTICE = 'Practice Mode';
    case STATISTICS = 'Statistics';
    case RESET = 'Reset';
    case EXIT = 'Exit';
}
