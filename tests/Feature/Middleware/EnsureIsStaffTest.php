<?php

use App\Http\Middleware\Demo\EnsureIsStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    $response = (new EnsureIsStaff)->handle(Request::create('/'), fn ($req) => new Response('next'));

    expect($response->getTargetUrl())->toBe(route('auth.login'));
});

it('allows staff users through', function () {
    $user = User::factory()->create(['privilege' => 'staff']);
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureIsStaff)->handle($request, fn ($req) => new Response('passed-through'));

    expect($response->getContent())->toBe('passed-through');
});

it('aborts with 403 for non-staff users', function () {
    $user = User::factory()->create(['privilege' => 'user']);
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    (new EnsureIsStaff)->handle($request, fn ($req) => new Response('next'));
})->throws(HttpException::class);
