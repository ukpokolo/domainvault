<?php

namespace App\Services\Ai;

use App\Exceptions\LlmException;
use App\Services\Ai\Contracts\LlmClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AnthropicLlmClient implements LlmClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl = 'https://api.anthropic.com',
    ) {}

    public function complete(string $system, string $prompt): string
    {
        if ($this->apiKey === '') {
            throw new LlmException('ANTHROPIC_API_KEY is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
                ->timeout(15)
                ->retry(1, 250, throw: false)
                ->post("{$this->baseUrl}/v1/messages", [
                    'model' => $this->model,
                    'max_tokens' => 400,
                    'temperature' => 0,
                    'system' => $system,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);
        } catch (ConnectionException $e) {
            throw new LlmException('Could not reach the LLM provider.', previous: $e);
        }

        if ($response->failed()) {
            throw new LlmException("LLM provider returned HTTP {$response->status()}.");
        }

        $text = collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        if (trim($text) === '') {
            throw new LlmException('LLM provider returned an empty reply.');
        }

        return $text;
    }
}
