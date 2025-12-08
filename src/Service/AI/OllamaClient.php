<?php

namespace Busanstu\DiffDefenderBundle\Service\AI;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class OllamaClient
{
    public function __construct(
        private readonly HttpClientInterface $client,
        #[Autowire(env: 'OLLAMA_API_URL')]
        private readonly string $apiUrl,
        #[Autowire(env: 'OLLAMA_MODEL')]
        private readonly string $model
    ) {
    }

    public function analyzeCode(string $diff, string $context): array
    {
        $systemPrompt = <<<PROMPT
You are a Senior PHP Reviewer focused on filtering out only REAL, actionable bugs.

**CRITICAL OUTPUT REQUIREMENT:**
1. Your entire output MUST be a single, valid JSON array.
2. If no issues are found, return exactly: [].

**CHECKLIST (Only flag if 100% CERTAIN):**
- Debugging functions (dump, dd, print_r) -> CRITICAL
- Security flaws (XSS, SQL Injection, Missing Permissions) -> CRITICAL
- Schema Mismatch (Entity attributes conflicting with Migration SQL in context) -> WARNING

**JSON FORMAT DEFINITION:**
[
    {
        "line": <int>, // The absolute line number from the input
        "severity": "critical|warning",
        "message": "<A concise description of the issue>",
        "suggestion": "<The fixed code or clear advice>"
    }
]
PROMPT;

        $contextMsg = "REFERENCE MATERIAL (CONFIGS & MIGRATIONS):\n\n" . $context;
        $taskMsg = "CODE TO REVIEW:\n\n" . $diff;
        $psrCheck = "REVIEW CODE WITH PSR12 AND SPACING RULES:\n\n" . $diff;

        try {
            $response = $this->client->request('POST', $this->apiUrl, [
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $contextMsg],
                        ['role' => 'user', 'content' => $taskMsg],
                        ['role' => 'user', 'content' => $psrCheck]
                    ],
                    'format' => 'json',
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.1,
                        'num_ctx' => 4096
                    ]
                ],
                'timeout' => 60,
            ]);

            $data = $response->toArray();
            $rawJson = $data['message']['content'] ?? '[]';

            return json_decode($rawJson, true) ?? [];
        } catch (Throwable $e) {
            return [
                [
                    'line' => 0,
                    'severity' => 'critical',
                    'message' => 'AI Connection Failed: ' . $e->getMessage(),
                    'suggestion' => 'Check if Ollama is running and OLLAMA_API_URL is correct'
                ]
            ];
        }
    }
}
