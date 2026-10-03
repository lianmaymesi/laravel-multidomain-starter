<?php

namespace Tests\Fixtures\Billing;

use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for Stripe's HTTP layer, so Cashier calls run for real against
 * canned responses — and nothing ever leaves the test. Unmatched requests get
 * a Stripe-style 404, which Cashier surfaces as an ApiErrorException.
 */
class FakeStripe implements ClientInterface
{
    /** @var array<int, array{method: string, path: string, params: array<string, mixed>}> */
    public array $requests = [];

    /** @var array<int, array{method: string, pattern: string, status: int, body: array<string, mixed>}> */
    private array $responses = [];

    public static function install(): self
    {
        $fake = new self;
        ApiRequestor::setHttpClient($fake);

        return $fake;
    }

    public static function uninstall(): void
    {
        ApiRequestor::setHttpClient(null);
    }

    /** @param  array<string, mixed>  $body */
    public function respond(string $method, string $pathPattern, array $body, int $status = 200): self
    {
        $this->responses[] = ['method' => strtolower($method), 'pattern' => $pathPattern, 'status' => $status, 'body' => $body];

        return $this;
    }

    public function sent(string $method, string $pathPattern): bool
    {
        return collect($this->requests)->contains(fn (array $request) => $request['method'] === strtolower($method)
            && preg_match($pathPattern, $request['path']) === 1);
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['method' => strtolower($method), 'path' => $path, 'params' => (array) $params];

        foreach (array_reverse($this->responses) as $response) {
            if ($response['method'] === strtolower($method) && preg_match($response['pattern'], $path) === 1) {
                return [json_encode($response['body']), $response['status'], []];
            }
        }

        return [json_encode(['error' => ['type' => 'invalid_request_error', 'message' => "FakeStripe: no response for {$method} {$path}"]]), 404, []];
    }
}
