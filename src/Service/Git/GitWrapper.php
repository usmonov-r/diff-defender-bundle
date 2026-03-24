<?php

namespace Busanstu\DiffDefenderBundle\Service\Git;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GitWrapper
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function getStagedDiff(): string
    {
        $process = new Process(['git', 'diff', '--staged']);
        $process->setWorkingDirectory($this->projectDir);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
