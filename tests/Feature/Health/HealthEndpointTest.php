<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Health\ExplodingCheck;
use Tests\Fixtures\Health\FailingCheck;
use Tests\Fixtures\Health\PassingCheck;
use Tests\Fixtures\Health\WarningCheck;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['health.checks' => [PassingCheck::class], 'health.token' => 'secret-token', 'health.cache_seconds' => 30]);
});

function healthUrl(string $query = ''): string
{
    return 'http://'.config('multidomain.main_domain').'/health'.$query;
}

it('keeps /up as the dependency-free liveness check', function () {
    $this->get('/up')->assertOk();
});

it('shows only the overall status to the public', function () {
    $this->getJson(healthUrl())
        ->assertOk()
        ->assertJsonStructure(['status', 'checked_at'])
        ->assertJsonMissingPath('checks')
        ->assertJsonPath('status', 'ok')
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('shows every check to callers with the token, by header or query', function () {
    $this->getJson(healthUrl(), ['X-Health-Token' => 'secret-token'])
        ->assertOk()
        ->assertJsonPath('checks.passing.status', 'ok')
        ->assertJsonPath('checks.passing.message', 'All good.')
        ->assertJsonPath('checks.passing.meta.answer', 42)
        ->assertJsonStructure(['checks' => ['passing' => ['label', 'status', 'message', 'meta', 'duration_ms']]]);

    $this->getJson(healthUrl('?token=secret-token'))->assertJsonPath('checks.passing.status', 'ok');
    $this->getJson(healthUrl('?token=wrong'))->assertJsonMissingPath('checks');
});

it('never reveals details when no token is configured', function () {
    config(['health.token' => null]);

    $this->getJson(healthUrl('?token='))->assertJsonMissingPath('checks');
});

it('answers 503 when a check fails, 200 for warnings', function () {
    config(['health.checks' => [PassingCheck::class, WarningCheck::class]]);
    $this->getJson(healthUrl('?token=secret-token&fresh=1'))->assertOk()->assertJsonPath('status', 'warning');

    config(['health.checks' => [PassingCheck::class, FailingCheck::class]]);
    $this->getJson(healthUrl('?token=secret-token&fresh=1'))->assertStatus(503)->assertJsonPath('status', 'failed');
});

it('turns a check that throws into a failure instead of an error page', function () {
    config(['health.checks' => [ExplodingCheck::class]]);

    $this->getJson(healthUrl('?token=secret-token&fresh=1'))
        ->assertStatus(503)
        ->assertJsonPath('checks.exploding.status', 'failed')
        ->assertJsonPath('checks.exploding.message', 'RuntimeException: boom');
});

it('reuses a recent report, unless the token holder asks for a fresh one', function () {
    $first = $this->getJson(healthUrl())->json('checked_at');

    $this->travel(5)->seconds();
    expect($this->getJson(healthUrl())->json('checked_at'))->toBe($first);

    // The public can't force a re-run.
    expect($this->getJson(healthUrl('?fresh=1'))->json('checked_at'))->toBe($first);

    expect($this->getJson(healthUrl('?token=secret-token&fresh=1'))->json('checked_at'))->not->toBe($first);
});

it('answers on every portal host — no portal route may shadow /health', function () {
    foreach ([config('multidomain.main_domain'), ...array_values(config('multidomain.sub_domains'))] as $host) {
        $this->getJson("http://{$host}/health")->assertOk()->assertJsonPath('status', 'ok');
    }
});
