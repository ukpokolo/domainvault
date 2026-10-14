<?php

namespace App\Services\Ai;

use App\Exceptions\LlmException;
use App\Services\Ai\Contracts\LlmClient;

/**
 * Deterministic stand-in for the LLM. Tests script replies with respondWith()/failWith();
 * with nothing scripted it falls back to a small rule-based parser so the demo
 * works without an API key (LLM_DRIVER=fake).
 */
class FakeLlmClient implements LlmClient
{
    /** @var list<array{system: string, prompt: string}> */
    public array $calls = [];

    private string|\Closure|null $reply = null;

    private ?LlmException $failure = null;

    /** @param string|array<string, mixed>|\Closure $reply */
    public function respondWith(string|array|\Closure $reply): static
    {
        $this->reply = is_array($reply) ? json_encode($reply, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT) : $reply;

        return $this;
    }

    public function failWith(string $message = 'fake failure'): static
    {
        $this->failure = new LlmException($message);

        return $this;
    }

    public function complete(string $system, string $prompt): string
    {
        $this->calls[] = ['system' => $system, 'prompt' => $prompt];

        if ($this->failure) {
            throw $this->failure;
        }

        if ($this->reply instanceof \Closure) {
            return ($this->reply)($prompt);
        }

        return $this->reply ?? json_encode($this->heuristic($prompt), JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
    }

    /** @return array<string, mixed> */
    private function heuristic(string $prompt): array
    {
        $text = strtolower($prompt);
        $filters = [];

        if (preg_match('/(\d+)\s*\+?\s*(?:year|yr)/', $text, $m)) {
            $filters['min_age'] = (int) $m[1];
        }
        if (preg_match('/\.(com|net|org|io|co|info|biz)\b/', $text, $m)) {
            $filters['tld'] = $m[1];
        }
        if (preg_match('/(?:under|below|less than|max|<)\s*\$?\s*([\d,]+)\s*(k)?/', $text, $m)) {
            $filters['max_price'] = (int) str_replace(',', '', $m[1]) * (isset($m[2]) && $m[2] === 'k' ? 1000 : 1);
        }
        if (preg_match('/(?:authority|da)\D{0,10}(\d{1,3})/', $text, $m)) {
            $filters['min_authority'] = (int) $m[1];
        }
        foreach (['fitness', 'finance', 'crypto', 'travel', 'health', 'real estate', 'technology', 'gaming', 'food', 'fashion', 'education', 'automotive', 'pets', 'home & garden', 'marketing', 'legal'] as $niche) {
            if (str_contains($text, $niche)) {
                $filters['niche'] = $niche;
                break;
            }
        }

        return $filters;
    }
}
