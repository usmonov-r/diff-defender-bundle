<?php

namespace Busanstu\DiffDefenderBundle\Service\Parser;

use Busanstu\DiffDefenderBundle\DTO\FileChange;
use SebastianBergmann\Diff\Line;
use SebastianBergmann\Diff\Parser;

class DiffParser
{
    private Parser $parser;
    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * @return FileChange[]
     */
    public function parse(string $diffString): array
    {
        $diffs = $this->parser->parse($diffString);
        $fileChanges = [];

        foreach ($diffs as $diff) {
            $addedLines = [];
            $removedLines = [];

            foreach ($diff->Chunks() as $chunk) {
                $currentLineNum = $chunk->end();
                foreach ($chunk->lines() as $line)
                {
                    switch ($line->type())
                    {
                        case Line::ADDED:
                            $addedLines[$currentLineNum] = $line->content();
                            $currentLineNum++;
                            break;
                        case Line::UNCHANGED:
                            $currentLineNum++;
                            break;
                        case Line::REMOVED:
                            break;
                    }
                }
            }

            $status = "M";
            if ($diff->from() === '/dev/null') {
                $status = 'A';
            } elseif ($diff->to() === '/dev/null') {
                $status = 'D';
            }

            $rawPath = $diff->to() ?? $diff->from();
            $relativePath = preg_replace('#^[ab]/#', '', $rawPath);
            dump($relativePath);
            $fileChanges[] = new FileChange(
                $relativePath,
                $addedLines,
                $removedLines,
                $status
            );
        }

        return $fileChanges;
    }
}
