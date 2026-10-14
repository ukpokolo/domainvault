<?php

use App\DTOs\DomainFilters;
use App\Exceptions\LlmException;
use App\Models\Domain;
use App\Services\Ai\AiSearchService;
use App\Services\Ai\AnthropicLlmClient;
use App\Services\Ai\Contracts\LlmClient;
use App\Services\Ai\FakeLlmClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Cache::flush();
    $this->llm = new FakeLlmClient;
    app()->instance(LlmClient::class, $this->llm);

    Domain::factory()->create(['name' => 'ironfit.com', 'tld' => 'com', 'niche' => 'Fitness', 'registered_year' => now()->year - 14, 'price_cents' => 40_000]);
    Domain::factory()->create(['name' => 'youngfit.com', 'tld' => 'com', 'niche' => 'Fitness', 'registered_year' => now()->year - 2, 'price_cents' => 40_000]);
    Domain::factory()->create(['name' => 'pricyfit.com', 'tld' => 'com', 'niche' => 'Fitness', 'registered_year' => now()->year - 14, 'price_cents' => 900_000]);
    Domain::factory()->create(['name' => 'oldpet.io', 'tld' => 'io', 'niche' => 'Pets', 'registered_year' => now()->year - 14, 'price_cents' => 40_000]);
});

it('interprets a natural language query into validated filters', function () {
    $this->llm->respondWith(['tld' => 'com', 'niche' => 'fitness', 'min_age' => 10, 'max_price' => 500]);

    $filters = app(AiSearchService::class)->interpret('10+ year old .com in fitness under $500');

    expect($filters)->toEqual(new DomainFilters(tld: 'com', niche: 'Fitness', minAge: 10, maxPrice: 500));
});

it('sends the user query to the LLM with a constraining system prompt', function () {
    $this->llm->respondWith([]);

    app(AiSearchService::class)->interpret('anything');

    expect($this->llm->calls)->toHaveCount(1)
        ->and($this->llm->calls[0]['prompt'])->toBe('anything')
        ->and($this->llm->calls[0]['system'])->toContain('ONE JSON object')->toContain('Fitness');
});

it('redirects to the marketplace with the interpreted filters and shows removable chips', function () {
    $this->llm->respondWith(['tld' => 'com', 'niche' => 'Fitness', 'min_age' => 10, 'max_price' => 500]);

    $this->get('/ai-search?query='.urlencode('10+ year old .com in fitness under $500'))
        ->assertRedirect(route('marketplace.index', [
            'tld' => 'com', 'niche' => 'Fitness', 'min_age' => 10, 'max_price' => 500,
            'ai' => '10+ year old .com in fitness under $500',
        ]));

    $this->get(route('marketplace.index', ['tld' => 'com', 'niche' => 'Fitness', 'min_age' => 10, 'max_price' => 500, 'ai' => 'q']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('domains.data.0.name', 'ironfit.com')
            ->has('domains.data', 1)
            ->has('chips', 4)
            ->where('chips.0.label', '.com')
            ->where('chips.2.label', '10+ years old')
            ->where('chips.3.label', 'Under $500'));
});

it('builds chip removal links that drop only that filter', function () {
    $page = $this->get(route('marketplace.index', ['tld' => 'com', 'min_age' => 10]))->viewData('page');
    $chip = collect($page['props']['chips'])->firstWhere('key', 'tld');

    $this->get($chip['remove_url'])->assertInertia(fn (Assert $p) => $p
        ->has('chips', 1)
        ->where('chips.0.key', 'min_age')
        ->has('domains.data', 3));
});

it('drops invalid, unknown and out-of-range values from the LLM reply', function () {
    $this->llm->respondWith([
        'min_age' => 9999,
        'max_price' => 'free',
        'min_authority' => 150,
        'niche' => 'Underwater Basket Weaving',
        'tld' => 'co; DROP TABLE domains',
        'sort' => 'price_desc; --',
        'is_admin' => true,
        'status' => 'sold',
        'min_age_nested' => [1],
    ]);

    $filters = app(AiSearchService::class)->interpret('hack');

    expect($filters->toArray())->toBe([]);
    expect(Domain::count())->toBe(4);
});

it('keeps valid keys while dropping bad ones', function () {
    $this->llm->respondWith(['min_age' => 10, 'min_authority' => 500, 'sort' => 'price_asc']);

    expect(app(AiSearchService::class)->interpret('x')->toArray())->toBe(['min_age' => 10, 'sort' => 'price_asc']);
});

it('tolerates markdown fences around the JSON', function () {
    $this->llm->respondWith("Sure!\n```json\n{\"tld\": \"io\"}\n```");

    expect(app(AiSearchService::class)->interpret('x')->tld)->toBe('io');
});

it('rejects replies that are not JSON', function (string $reply) {
    $this->llm->respondWith($reply);

    app(AiSearchService::class)->interpret('x');
})->with(['plain text', '{broken json', '[]x'])->throws(LlmException::class);

it('falls back to keyword search when the LLM fails', function () {
    $this->llm->failWith('boom');

    $this->get('/ai-search?query=ironfit')
        ->assertRedirect(route('marketplace.index', ['q' => 'ironfit', 'ai' => 'ironfit']))
        ->assertSessionHas('error');
});

it('validates the AI search input', function () {
    $this->get('/ai-search?query=ab')->assertSessionHasErrors('query');
    $this->get('/ai-search')->assertSessionHasErrors('query');
    $this->get('/ai-search?query='.str_repeat('a', 201))->assertSessionHasErrors('query');
});

it('rate limits AI searches', function () {
    $this->llm->respondWith([]);

    foreach (range(1, 20) as $_) {
        $this->get('/ai-search?query=something')->assertRedirect();
    }

    $this->get('/ai-search?query=something')->assertStatus(429);
});

it('parses simple queries with the built-in fake heuristic', function () {
    app()->instance(LlmClient::class, new FakeLlmClient);

    $filters = app(AiSearchService::class)->interpret('10+ year old .com in fitness under $500');

    expect($filters->toArray())->toBe(['tld' => 'com', 'niche' => 'Fitness', 'min_age' => 10, 'max_price' => 500]);
});

it('calls the Anthropic Messages API and extracts the text', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => '{"tld":"com"}']]])]);

    $text = (new AnthropicLlmClient('sk-test', 'claude-haiku-5-5'))->complete('sys', 'hello');

    expect($text)->toBe('{"tld":"com"}');
    Http::assertSent(fn ($r) => $r->url() === 'https://api.anthropic.com/v1/messages'
        && $r->header('x-api-key') === ['sk-test']
        && $r['model'] === 'claude-haiku-5-5'
        && $r['system'] === 'sys'
        && $r['messages'][0]['content'] === 'hello');
});

it('raises LlmException on provider errors or a missing key', function () {
    Http::fake(['*' => Http::response([], 500)]);

    expect(fn () => (new AnthropicLlmClient('sk', 'm'))->complete('s', 'p'))->toThrow(LlmException::class);
    expect(fn () => (new AnthropicLlmClient('', 'm'))->complete('s', 'p'))->toThrow(LlmException::class, 'not configured');
});
