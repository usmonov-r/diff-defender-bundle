<?php

namespace Busanstu\DiffDefenderBundle\Command;

use Busanstu\DiffDefenderBundle\Service\AI\OllamaClient;
use Busanstu\DiffDefenderBundle\Service\Context\ContextProvider;
use Busanstu\DiffDefenderBundle\Service\Git\GitWrapper;
use Busanstu\DiffDefenderBundle\Service\Parser\DiffParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand('diff:review')]
class ReviewCommand extends Command
{
    public function __construct(
        private readonly GitWrapper $gitWrapper,
        private readonly DiffParser $parser,
        private readonly ContextProvider $provider,
        private readonly OllamaClient $client,
    ) {
        parent:: __construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $hasIssues = false;
        $io = new SymfonyStyle($input, $output);
        $io->title("Pre Reviewer");

        try {
            $diff = $this->gitWrapper->getStagedDiff();

            if (empty($diff)) {
                $io->warning('No Staged Changes');
                return Command::SUCCESS;
            }

            $changes = $this->parser->parse($diff);
            foreach ($changes as $change) {
                if ($change->status === "D") {
                    continue;
                }

                $snippedLines = implode("\n", $change->addedLines);
                if (empty(trim($snippedLines))) {
                    $io->text('Skipping: Only whitespace/removals.');
                    continue;
                }

                [$addCount, $delCount] = [count($change->addedLines), count($change->removedLines)];
                $io->section("{$change->relativePath} (<info>+$addCount</info> / <fg=#c0392b>-$delCount)</>");

                $context = $this->provider->getContext($change->relativePath);
                if (!empty($context) && preg_match_all('/\s*(.*?)\s*<<</', $context, $matches)) {
                    $paths = $matches[1];
                    foreach ($paths as $path) {
                        $io->writeln("$path");
                    }
                    $io->text("<comment>------------------------------</comment>");
                    $io->newLine();
                }

                $io->text("<comment>--- Analyzed Code Snippet ---</comment>");
                foreach ($change->addedLines as $lineNum => $lineContent) {
                    $io->text(sprintf(" <info>%2d</info> | %s", $lineNum, $lineContent));
                }

                $io->text("<comment>----------------------------------------</comment>");
                $io->newLine();
                $issues = $this->client->analyzeCode($snippedLines, $context);

                if (empty($issues)) {
                    $io->success("CLEAN");
                } else {
                    $hasIssues = true;

                    if (isset($issues['line']) || isset($issues['message'])) {
                        $issues = [$issues];
                    }

                    foreach ($issues as $issue) {
                        if (is_string($issue)) {
                            $issue = ['message' => $issue, 'severity' => 'warning', 'line' => 0];
                        }

                        $realLine = (int)($issue['line'] ?? 0);

                        if (!array_key_exists($realLine, $change->addedLines)) {
                            $realLine = array_key_first($change->addedLines);
                        }

                        $severity = mb_strtolower($issue['severity'] ?? 'warning');
                        $style = match ($severity) {
                            'critical' => 'error',
                            'warning' => 'comment',
                            default => 'info',
                        };

                        $io->block(
                            $issue['message'] ?? ['Unknown issue'],
                            mb_strtoupper($severity),
                            $style,
                            ' '
                        );

                        $line = $realLine ?? '?';
                        $io->writeln("Line: <info>$line</info>");

                        if (!empty($issue['suggestion'])) {
                            $io->text("FIX:" . $issue['suggestion']);
                        }

                        $io->newLine();
                    }
                }
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $hasIssues = true;
            $io->error("Error occurred while getting stages " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
