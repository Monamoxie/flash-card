<?php

namespace Modules\Flashcard\Enums;

enum FlashcardPaginationEnum: string
{
    case NEXT = 'Next';
    case PREVIOUS = 'Previous';
    case RETURN = 'Return to Main Menu';
}
