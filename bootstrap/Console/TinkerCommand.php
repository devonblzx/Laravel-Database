<?php namespace Bootstrap\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class TinkerCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tinker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Interact with your application";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->supportsPsy())
        {
            $this->runPsyShell();
        }
        elseif ($this->supportsBoris())
        {
            $this->runBorisShell();
        }
        else
        {
            $this->comment('Full REPL not supported. Falling back to simple shell.');

            $this->runPlainShell();
        }
    }

    protected function runPsyShell() {
        $this->getApplication()->setCatchExceptions(false);

        $config = new \Psy\Configuration;

        $shell = new \Psy\Shell($config);
        $shell->setIncludes($this->argument('include'));

        $shell->run();
    }

    /**
     * Run the Boris REPL with the current context.
     *
     * @return void
     */
    protected function runBorisShell()
    {
        $this->setupBorisErrorHandling();

        with(new \Boris\Boris('> '))->start();
    }

    /**
     * Setup the Boris exception handling.
     *
     * @return void
     */
    protected function setupBorisErrorHandling()
    {
        restore_error_handler(); restore_exception_handler();

        $this->laravel->make('artisan')->setCatchExceptions(false);

        $this->laravel->error(function() { return ''; });
    }

    /**
     * Run the plain Artisan tinker shell.
     *
     * @return void
     */
    protected function runPlainShell()
    {
        $input = $this->prompt();

        while ($input != 'quit')
        {
            // We will wrap the execution of the command in a try / catch block so we
            // can easily display the errors in a convenient way instead of having
            // them bubble back out to the CLI and stop the entire command loop.
            try
            {
                if (starts_with($input, 'dump '))
                {
                    $input = 'var_dump('.substr($input, 5).');';
                }

                eval($input);
            }

                // If an exception occurs, we will just display the message and keep this
                // loop going so we can keep executing commands. However, when a fatal
                // error occurs, we have no choice but to bail out of this routines.
            catch (\Exception $e)
            {
                $this->error($e->getMessage());
            }

            $input = $this->prompt();
        }
    }

    /**
     * Prompt the developer for a command.
     *
     * @return string
     */
    protected function prompt()
    {
        $dialog = $this->getHelperSet()->get('dialog');

        return $dialog->ask($this->output, "<info>></info>", null);
    }

    /**
     * Determine if the current environment supports Boris.
     *
     * @return bool
     */
    protected function supportsBoris()
    {
        return extension_loaded('readline') && extension_loaded('posix') && extension_loaded('pcntl');
    }

    /**
     * Determine if the current environment supports Boris.
     *
     * @return bool
     */
    protected function supportsPsy()
    {
        return class_exists('\\Psy\\Shell');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['include', InputArgument::IS_ARRAY, 'Include file(s) before starting tinker'],
        ];
    }

}
