<?php

namespace Modules\Flashcard;

use Illuminate\Support\ServiceProvider;
use Modules\Flashcard\Console\Commands\FlashcardCommand;
use Modules\Flashcard\Contracts\SessionInterface;
use Modules\Flashcard\Managers\RedisSessionManager;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlashcardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(SessionInterface::class, function ($app) {
            return new RedisSessionManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->commands([
            FlashcardCommand::class,
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->mergeConfigFrom(
            __DIR__ . '/Config/flashcard.php',
            'flashcard'
        );

        Factory::guessFactoryNamesUsing(function (string $modelName) {

            $modelName = substr($modelName, strrpos($modelName, '\\') + 1);
            return 'Modules\\Flashcard\\Factories\\' . $modelName . 'Factory';
        });
    }
}
