<?php

namespace Atrium\Core\Support\Features;

use Atrium\Core\Support\PortalResolver;
use Illuminate\Http\Request;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable;
use Livewire\Livewire;

/**
 * A portal (app, backoffice, account, ...) as a Pennant scope, so a flag can
 * be on for one portal and off for another:
 *
 *   Feature::for(Portal::named('app'))->activate(WhatsNewCard::class);
 *   Feature::for(Portal::current())->active(WhatsNewCard::class);
 *
 * Stored in the features table as "portal:app".
 */
final class Portal implements FeatureScopeSerializeable
{
    private function __construct(public readonly string $name) {}

    public static function named(string $name): self
    {
        return new self($name);
    }

    /**
     * The portal the current request belongs to. A Livewire update request is
     * posted to /livewire/update, so it is resolved from the page it came from
     * instead — otherwise single-domain mode would see every action as "landing".
     */
    public static function current(): self
    {
        $request = request();

        if (Livewire::isLivewireRequest() && ($url = Livewire::originalUrl())) {
            $request = Request::create($url);
        }

        return new self(app(PortalResolver::class)->resolve($request));
    }

    /**
     * Every portal configured in config/multidomain.php.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_keys(config('multidomain.sub_domains', []));
    }

    public function featureScopeSerialize(): string
    {
        return 'portal:'.$this->name;
    }
}
