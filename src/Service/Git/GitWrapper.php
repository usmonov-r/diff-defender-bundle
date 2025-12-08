<?php

namespace Busanstu\DiffDefenderBundle\Service\Git;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitWrapper
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {}

    public function getStagedDiff(): string
    {
        // 1. Create the process
        // We explicitly separate arguments to avoid escaping issues
        $process = new Process(['git', 'diff', '--staged']);

        // 2. Set the working directory to the project root
        $process->setWorkingDirectory($this->projectDir);

        // 3. Run it
        $process->run();

        // 4. Check for errors
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // 5. Return the raw output
        return $process->getOutput();
    }
}
