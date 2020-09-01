<?php

namespace App\Actions;

use App\Shell;

class ConfigureFrontendFramework
{
    use AbortsCommands;

    private $shell;
    private $laravelUi;
    private $availableFrontends = ['bootstrap', 'react', 'vue'];

    public function __construct(Shell $shell, LaravelUi $laravelUi)
    {
        $this->shell = $shell;
        $this->laravelUi = $laravelUi;
    }

    public function __invoke()
    {
        if (! config('lambo.store.frontend')) {
            return false;
        }

        app('console-writer')->logStep('Configuring frontend scaffolding');

        if (! $this->chooseValidFrontend()) {
            app('console-writer')->success('No frontend framework will be installed.', ' OK ');
            return false;
        }

        $this->laravelUi->install();

        $process = $this->shell->execInProject(sprintf("php artisan ui %s%s", config('lambo.store.frontend'), $this->extraOptions()));

        $this->abortIf(! $process->isSuccessful(), sprintf("Installation of %s UI scaffolding did not complete successfully.", config('lambo.store.frontend')), $process);

        app('console-writer')->success(config('lambo.store.frontend') . ' ui scaffolding installed.');
    }

    private function chooseValidFrontend(): bool
    {
        if (in_array(strtolower(config('lambo.store.frontend')), $this->availableFrontends)) {
            return true;
        }

        $configuredFrontend = $this->chooseFrontend();
        if ($configuredFrontend !== 'none') {
            config(['lambo.store.frontend' => $configuredFrontend]);
            app('console-writer')->success("Using {$configuredFrontend} ui scaffolding.", ' OK ');
            return true;
        }
        return false;
    }

    private function chooseFrontend()
    {
        $this->availableFrontends[] = 'none';
        $message = sprintf("<fg=yellow>I can't install %s</>. Please choose one of the following options", config('lambo.store.frontend'));
        $preselectedChoice = count($this->availableFrontends) - 1;

        return app('console')->choice($message, $this->availableFrontends, $preselectedChoice);
    }

    private function extraOptions()
    {
        return config('lambo.store.with_output') ? '' : ' --quiet';
    }
}
