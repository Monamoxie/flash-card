<?php

namespace Modules\Flashcard\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Flashcard\Enums\FlashcardActionEnum;
use Illuminate\Support\Facades\Validator;
use Modules\Flashcard\Enums\FlashcardPaginationEnum;
use Modules\Flashcard\Enums\FlashcardStatusEnum;
use Modules\Flashcard\Models\Flashcard;
use Symfony\Component\Console\Helper\ProgressBar;

trait FlashcardUtilities
{
    /**
     * For pagination purposes 
     * 
     * Calculate total entries 
     * 
     * @var integer 
     */
    private null|int $totalEntries = null;

    /**
     * For pagination purposes
     *
     * Set how many entries to be displayed per page
     * 
     * @var integer 
     */
    private int $perPage = 10;

    /**
     * For display and table purposes
     * 
     * To avoid publicly exposing the internal primary ID of each row, 
     * I will generate an arbitrary Row ID on each page
     * 
     * @var integer 
     */
    private int $rowID = 0;

    /**
     * For pagination purposes
     * 
     */
    private int $currPage = 1;

    /**
     * For pagination purposes
     * 
     */
    private int $totalPages = 1;

    /**
     * For display and table purposes
     * 
     * @var Collection
     */
    private Collection $flashcards;

    private function askForEmail(): string
    {
        $valid = False;
        while (!$valid) {
            $email = $this->ask($this->flashcardConfig['prompts']['enter_email']);

            $validator = Validator::make(['email' => $email], [
                'email' => ['required', 'email'],
            ]);

            if ($validator->fails()) {
                $this->error($this->flashcardConfig['messages']['valid_email']);
            } else {
                $valid = true;
            }
        }

        return $email;
    }

    private function askForPassword(): string
    {
        $validPassword = false;
        $password = '';

        while (!$validPassword) {
            $password = $this->secret($this->flashcardConfig['prompts']['enter_password']);

            if (empty($password) || strlen($password) < 3) {
                $this->error($this->flashcardConfig['messages']['password_length']);
            } else {
                $passwordConfirmation = $this->secret($this->flashcardConfig['prompts']['re_enter_password']);

                if ($password !== $passwordConfirmation) {
                    $this->error($this->flashcardConfig['messages']['password_mismatch']);
                } else {
                    $validPassword = true;
                }
            }
        }

        return $password;
    }

    private function createFlashcard()
    {
        $question = $this->askForNewQuestion();
        $answer = $this->ask($this->flashcardConfig['prompts']['enter_answer_for_new_question']);

        try {
            $this->flashcardService::newEntry($question, $answer, $this->user);
            $this->alert($this->flashcardConfig['messages']['new_entry_created']);

            return $this->displayMenu();
        } catch (\Throwable $th) {
            $this->error($this->flashcardConfig['messages']['entry_could_not_be_processed']);
            return 0;
        }
    }

    protected function displayMenu()
    {
        $this->resetProps();

        // while (true) {
        $choice = $this->choice($this->flashcardConfig['prompts']['select_option'], $this->flashcardConfig['menu']);

        switch ($choice) {
            case FlashcardActionEnum::LIST->value:
                $this->alert(sprintf('Entering %s Mode', FlashcardActionEnum::LIST->name));
                return $this->listFlashcards();
            case FlashcardActionEnum::CREATE->value:
                $this->alert(sprintf('Entering %s Mode', FlashcardActionEnum::CREATE->name));
                return $this->createFlashcard();
            case FlashcardActionEnum::UPDATE->value:
                $this->alert(sprintf('Entering %s Mode', FlashcardActionEnum::UPDATE->name));
                return $this->updateFlashcard();
            case FlashcardActionEnum::DELETE->value:
                $this->alert(sprintf('Entering %s Mode', FlashcardActionEnum::DELETE->name));
                $this->deleteFlashcard();
                break;
            case FlashcardActionEnum::PRACTICE->value:
                $this->alert(sprintf('Entering %s Mode', FlashcardActionEnum::PRACTICE->name));
                $this->practiceMode();
                break;
            case FlashcardActionEnum::STATISTICS->value:
                $this->alert(sprintf('Entering %s Mode', FlashcardActionEnum::STATISTICS->name));
                return $this->statistics();
                break;
            case FlashcardActionEnum::RESET->value:
                return $this->reset();
            case FlashcardActionEnum::EXIT->value:
                $this->exit();
                $this->info('Goodbye. See you soon!');
                return 0;
            default:
                $this->error('Invalid choice. Please try again.');
        }
        // }

        return 0;
    }

    protected function resetProps()
    {
        [$this->rowID, $this->currPage, $this->totalEntries, $this->totalPages] = [0, 1, null, 1];
    }

    private function askForNewQuestion(): string
    {
        $questionExists = True;
        $question = '';
        while ($questionExists || empty($question)) {
            $question = $this->ask($this->flashcardConfig['prompts']['enter_new_question']);

            if (empty($question)) {
                $this->error($this->flashcardConfig['messages']['question_cannot_be_empty']);
            } else {
                $questionExists = $this->flashcardService::isQuestionExists($question, $this->user->id);
                if ($questionExists) {
                    $this->error($this->flashcardConfig['messages']['duplicate_question']);
                }
            }
        }

        return $question;
    }

    private function listFlashcards(?bool $indexing = true, ?bool $practiceMode = false)
    {
        // ::: For Optimization purposes 
        // To avoid multiple hits to the DB on every page load, I will store the total entries as a property of this class
        if (is_null($this->totalEntries)) {
            $this->totalEntries = $this->flashcardService::getTotalEntriesFor($this->user);
        }

        if ($this->totalEntries < 1) {
            $this->error($this->flashcardConfig['messages']['no_flashcards']);
        }

        $this->totalPages = ceil($this->totalEntries / $this->perPage);

        if ($this->totalPages < 1) {
            $this->totalPages = 1;
        }
        if ($this->currPage < 1 || $this->currPage > $this->totalPages) {
            $this->error("Invalid page number. There are {$this->totalPages} pages.");
            return;
        }

        $this->flashcards = $this->flashcardService->getPaginatedEntriesFor($this->user, $this->currPage, $this->perPage);

        // ::: This is the formular I use for calculating and generating tabular row IDs
        $this->rowID = ($this->currPage - 1) * 10;

        $headers = ['#Row ID', 'Question'];

        // ::: $practiceMode tells us if this table about to be displayed is for practice mode or not
        $headers[] = $practiceMode ? 'Status' : 'Answer';

        $data = $this->flashcards->map(function ($flashcard) use ($practiceMode) {
            $this->rowID++;
            $flashcard->table_id = $this->rowID;

            $row = [$this->rowID, $flashcard->question];

            $row[] = $practiceMode ? $flashcard->status : $flashcard->answer;

            return $row;
        })->toArray();

        $this->info("Displaying page {$this->currPage} of {$this->totalPages}, Total Entries => {$this->totalEntries}");

        // display in a tabular form
        $this->table($headers, $data);

        // ::: $indexing tells us if this action is mainly for indexing/listing purposes, 
        if ($indexing) {
            $this->handleListingPagination();
        }
    }

    private function getPaginationTriggers(): array
    {
        $paginationTriggers = [FlashcardPaginationEnum::RETURN->value];
        if ($this->currPage > 1) {
            $paginationTriggers[] = FlashcardPaginationEnum::PREVIOUS->value;
        }
        if ($this->currPage < $this->totalPages) {
            $paginationTriggers[] =
                FlashcardPaginationEnum::NEXT->value;;
        }

        return $paginationTriggers;
    }

    private function handleListingPagination()
    {
        $paginationTriggers = $this->getPaginationTriggers();

        $choice = $this->choice($this->flashcardConfig['prompts']['select_pagination'], $paginationTriggers);

        switch ($choice) {
            case FlashcardPaginationEnum::PREVIOUS->value:
                $this->currPage -= 1;
                $this->listFlashcards();
                break;
            case FlashcardPaginationEnum::NEXT->value:
                $this->currPage += 1;
                $this->listFlashcards();
                break;
            case FlashcardPaginationEnum::RETURN->value:
                return $this->displayMenu();
                break;
            default:
                $this->error($this->flashcardConfig['messages']['invalid_option']);
                break;
        }
        return;
    }

    /** 
     * Generic Pagination
     * 
     * This will ensure a smooth combination of pagination as well as accepting the ROW ID users want to delete or update 
     * 
     * For instance "Press R to Return, N for Next Page, P for Previous Page OR enter the ID of the row you wish to Update/Delete"
     * 
     */
    private function getGenericPagination(string $type)
    {
        $paginationTriggers = $this->getPaginationTriggers();
        $triggers = '';
        foreach ($paginationTriggers as $trigger) {
            $triggers .= sprintf("%s for %s, ", $trigger[0], $trigger);
        }

        return sprintf("Press %s OR enter the ID of the row you wish to %s", $triggers, ucfirst($type));
    }

    private function updateFlashcard()
    {
        $this->listFlashcards(false);

        $rowToUpdate = $this->getRowFromTable(FlashcardActionEnum::UPDATE->value);
        if (!$rowToUpdate['is_row']) {
            return $this->triggerGenericPagination($rowToUpdate['input'], FlashcardActionEnum::UPDATE->value);
        }

        $rowToUpdate = $rowToUpdate['row'];

        $this->alert('Existing Question => ' . $rowToUpdate->question);
        $newQuestion = $this->ask('Please enter the new question. Press Enter if you wish to leave it unchanged!');

        $this->alert('Existing Answer => ' . $rowToUpdate->answer);
        $newAnswer = $this->ask('Please enter the new answer for the provided question. Press Enter if you wish to leave it unchanged!');

        $question = empty($newQuestion) ? $rowToUpdate->question : $newQuestion;
        $answer = empty($newAnswer) ? $rowToUpdate->answer : $newAnswer;

        $this->flashcardService::updateEntry($rowToUpdate->id, $question, $answer, $this->user);

        $this->alert('Flashcard has been successfully updated. Kindly continue from where you left off');

        return $this->updateFlashcard();
    }

    private function triggerGenericPagination(string $input, string $type)
    {
        switch (strtolower($input)) {
            case 'r':
                return $this->displayMenu();
            case 'n':
                $this->currPage += 1;
                break;
            case 'p':
                $this->currPage -= 1;
                break;
            default:
                $this->error($this->flashcardConfig['messages']['invalid_option']);
                $this->exit();
                break;
        }

        // ::: Reload the flashcard based on the selected type
        switch ($type) {
            case FlashcardActionEnum::UPDATE->value:
                return $this->updateFlashcard();
            case FlashcardActionEnum::DELETE->value:
                return $this->deleteFlashcard();
            case FlashcardActionEnum::PRACTICE->value:
                return $this->practiceMode();
            default:
                return 0;
        }
    }

    private function getRowFromTable(string $intendedAction): array
    {
        $isValidRowID = false;
        $rowToAction = null;
        while (!$isValidRowID) {
            $input = $this->ask($this->getGenericPagination($intendedAction));
            if (is_numeric($input)) {
                $rowToAction = $this->flashcards->filter(function ($row) use ($input) {
                    return $row->table_id == (int) $input;
                })->first();

                if (is_null($rowToAction)) {
                    $this->error('Invalid Row ID. Please try again!');
                } else {
                    $isValidRowID = true;
                }
            } else {
                return ['is_row' => false, 'input' => $input];
            }
        }

        return ['is_row' => true, 'row' => $rowToAction];
    }

    private function deleteFlashcard()
    {
        $this->listFlashcards(indexing: false);

        $rowToDelete = $this->getRowFromTable(FlashcardActionEnum::DELETE->value);
        if (!$rowToDelete['is_row']) {
            return $this->triggerGenericPagination($rowToDelete['input'], FlashcardActionEnum::DELETE->value);
        }

        $rowToDelete = $rowToDelete['row'];

        // delete entry from DB
        $this->flashcardService::deleteEntry($rowToDelete->id, $this->user);

        // delete from local state
        $rowToDelete->delete();

        $this->totalEntries = $this->totalEntries <= 1 ? 0 : $this->totalEntries - 1;

        $this->alert('Flashcard has been successfully deleted. Kindly continue from where you left off');

        $this->deleteFlashcard();
    }

    private function practiceMode()
    {
        $this->listFlashcards(false, true);

        $this->newLine();

        // ::: Calculate the total questions completed
        $statistics = $this->flashcardService::getStatistics($this->user);

        $this->comment(sprintf('Total Completed: %s', $statistics['total_completed']));
        $this->displayProgressBar($statistics['total_completed'], $statistics['total_questions']);

        $this->comment(sprintf('Total Correct: %s', $statistics['total_correct']));
        $this->displayProgressBar($statistics['total_correct'], $statistics['total_questions']);

        while (True) {
            $rowToPractice = $this->getRowFromTable(FlashcardActionEnum::PRACTICE->value);
            if (!$rowToPractice['is_row']) {
                return $this->triggerGenericPagination($rowToPractice['input'], FlashcardActionEnum::PRACTICE->value);
            }

            $rowToPractice = $rowToPractice['row'];

            if ($rowToPractice->status === FlashcardStatusEnum::CORRECT->value) {
                $this->error('The question you picked has already been correctly answered. Please pick something else');
            } else {
                break;
            }
        }

        $this->alert('Question: ' . $rowToPractice->question);
        $answer = $this->ask('Please type your answer');

        if ($answer == $rowToPractice->answer) {
            $this->alert('CONGRATULATIONS! Your answer was correct!');
            $status = FlashcardStatusEnum::CORRECT->value;
        } else {
            $this->error('Oooopppssss! That was incorrect!');
            $this->newLine();
            $this->warn('The correct answer should have been: ' . $rowToPractice->answer);
            $this->newLine(3);

            $status = FlashcardStatusEnum::INCORRECT->value;
        }

        // update DB
        $this->flashcardService->updateStatus($rowToPractice->id, $status, $this->user);

        // update local state
        $this->flashcardService->status = $status;

        // refresh the interface
        $this->practiceMode();
    }

    private function statistics()
    {
        $statistics = $this->flashcardService::getStatistics($this->user);

        $totalQuestions = $statistics['total_questions'];
        $totalCompleted = $statistics['total_completed'];
        $totalCorrect = $statistics['total_correct'];

        $this->newLine();
        $this->info("TOTAL QUESTIONS: " . $totalQuestions);
        $this->newLine(2);

        $this->info("TOTAL COMPLETED: " .  $totalCompleted);
        $this->displayProgressBar($totalCompleted, $totalQuestions);

        $this->info("TOTAL CORRECT: " .  $totalCorrect);
        $this->displayProgressBar($totalCorrect, $totalQuestions);

        return;
    }

    private function displayProgressBar(int|float $totalNum, int|float $over)
    {
        ProgressBar::setFormatDefinition('custom', ' %current%/%max% [%bar%]');

        $progressBar = $this->output->createProgressBar($over);
        $progressBar->setProgress($totalNum);

        $this->newLine(3);
    }

    private function reset()
    {
        while (True) {
            $choice = $this->choice($this->flashcardConfig['prompts']['confirm_reset_status'], [
                'NO', 'YES'
            ]);
            switch (strtolower($choice)) {
                case 'no':
                    $this->info($this->flashcardConfig['messages']['levels_intact']);
                    return $this->displayMenu();
                case 'yes':
                    $this->flashcardService::resetAllStatus($this->user);
                    $this->alert($this->flashcardConfig['messages']['flashcards_reset']);
                    return $this->displayMenu();
                default:
                    $this->error($this->flashcardConfig['messages']['invalid_option']);
                    break;
            }
        }
    }

    protected function exit()
    {
        $this->resetProps();
        return 0;
    }
}
