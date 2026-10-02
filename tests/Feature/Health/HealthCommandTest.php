<?php

use App\Console\Commands\HealthCheckCommand;
use App\Notifications\HealthStatusChanged;
use App\Support\Health\HealthChecker;
use App\Support\Health\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\Fixtures\Health\FailingCheck;
use Tests\Fixtures\Health\PassingCheck;
use Tests\Fixtures\Health\WarningCheck;

uses(RefreshDatabase::class);

it('lists every check and succeeds while healthy', function () {
    config(['health.checks' => [PassingCheck::class]]);

    expect(Artisan::call('health:check'))->toBe(0)
        ->and(Artisan::output())->toContain('Passing')
        ->toContain('All good.')
        ->toContain('Overall: OK');
});

it('exits 1 on failure, and on warnings with --strict', function () {
    config(['health.checks' => [WarningCheck::class]]);
    $this->artisan('health:check')->assertSuccessful();
    $this->artisan('health:check', ['--strict' => true])->assertFailed();

    config(['health.checks' => [FailingCheck::class]]);
    $this->artisan('health:check')->assertFailed();
});

it('prints the full report as JSON', function () {
    config(['health.checks' => [PassingCheck::class]]);

    expect(Artisan::call('health:check', ['--json' => true]))->toBe(0);

    $report = json_decode(Artisan::output(), true);

    expect($report['status'])->toBe('ok')
        ->and($report['checks']['passing']['message'])->toBe('All good.');
});

it('emails only when the overall status changes', function () {
    Notification::fake();
    config(['health.notify' => ['ops@example.com']]);

    config(['health.checks' => [PassingCheck::class]]);
    $this->artisan('health:check', ['--notify' => true]); // first run, healthy: nothing to report

    config(['health.checks' => [FailingCheck::class]]);
    $this->artisan('health:check', ['--notify' => true]); // ok → failed: email
    $this->artisan('health:check', ['--notify' => true]); // still failed: no repeat

    config(['health.checks' => [PassingCheck::class]]);
    $this->artisan('health:check', ['--notify' => true]); // failed → ok: email

    Notification::assertSentOnDemandTimes(HealthStatusChanged::class, 2);
    Notification::assertSentOnDemand(HealthStatusChanged::class, fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === ['ops@example.com']);
    expect(Cache::get(HealthCheckCommand::LAST_STATUS_KEY))->toBe('ok');
});

it('sends nothing without recipients', function () {
    Notification::fake();
    config(['health.notify' => [], 'health.checks' => [FailingCheck::class]]);

    $this->artisan('health:check', ['--notify' => true]);

    Notification::assertNothingSent();
});

it('renders the alert email with what needs attention', function () {
    config(['health.checks' => [FailingCheck::class, PassingCheck::class]]);
    $report = app(HealthChecker::class)->run();

    $mail = (new HealthStatusChanged($report, Status::Ok))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toContain('Health: FAILED')
        ->and(implode("\n", $mail->introLines))->toContain('Failing — failed: Down.')
        ->and(implode("\n", $mail->introLines))->not->toContain('Passing');
});
