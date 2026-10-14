<?php

namespace App\Services\Ai\Contracts;

use App\Exceptions\LlmException;

interface LlmClient
{
    /**
     * Send a single-turn prompt and return the model's raw text reply.
     *
     * @throws LlmException when the provider is unreachable or returns an error
     */
    public function complete(string $system, string $prompt): string;
}
