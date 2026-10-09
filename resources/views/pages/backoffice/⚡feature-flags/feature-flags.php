<?php

use App\Models\User;
use Atrium\Core\Support\Features\FeatureFlag;
use Atrium\Core\Support\Features\Flags;
use Atrium\Core\Support\Features\Portal;
use Atrium\Core\Support\Features\PortalFlag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Runtime feature flags (app/Features). A portal flag gets a switch per
 * portal; a user flag can be switched on/off for every user already decided,
 * or reset so everyone is decided again by the flag's initial() rule.
 * Unlike modules, flags are read per request, so changes apply immediately.
 */
new #[Layout('layouts.backoffice')] class extends Component
{
    /** Confirmation of the last change, shown above the list. */
    public ?string $status = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('feature-flags.manage'), 403);
    }

    /**
     * Flag values live in Pennant's features table. Until `php artisan migrate`
     * has created it the page still lists flags, but can't read or change them.
     */
    public function migrated(): bool
    {
        $store = config('pennant.default');

        return config("pennant.stores.{$store}.driver") !== 'database'
            || Schema::hasTable(config("pennant.stores.{$store}.table") ?? 'features');
    }

    /**
     * @return array<int, array{name: string, label: string, description: string, scope: string, portals: array<string, bool>, users: array{active: int, total: int}|null}>
     */
    public function flags(): array
    {
        $migrated = $this->migrated();

        return collect(Flags::all())
            ->map(fn (FeatureFlag $flag) => [
                'name' => $flag->name,
                'label' => $flag->label(),
                'description' => $flag->description(),
                'scope' => $flag->scope(),
                'portals' => $flag instanceof PortalFlag && $migrated
                    ? collect($flag->portals())
                        ->mapWithKeys(fn (string $portal) => [$portal => Feature::for(Portal::named($portal))->active($flag->name)])
                        ->all()
                    : [],
                'users' => $flag instanceof PortalFlag || ! $migrated ? null : $this->userCounts($flag),
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    public function setPortal(string $flag, string $portal, bool $active): void
    {
        $instance = $this->authorizeFor($flag);

        abort_unless($instance instanceof PortalFlag && in_array($portal, $instance->portals(), true), 404);

        $this->write(fn () => Feature::for(Portal::named($portal))->{$active ? 'activate' : 'deactivate'}($instance->name));

        $this->flash($active
            ? __('":flag" is now on in the :portal portal.', ['flag' => $instance->label(), 'portal' => $portal])
            : __('":flag" is now off in the :portal portal.', ['flag' => $instance->label(), 'portal' => $portal]));
    }

    /**
     * Portal flag: every portal it applies to. User flag: every user already
     * decided — users seen for the first time later still get initial().
     */
    public function setEverywhere(string $flag, bool $active): void
    {
        $instance = $this->authorizeFor($flag);

        $this->write(function () use ($instance, $active) {
            if ($instance instanceof PortalFlag) {
                foreach ($instance->portals() as $portal) {
                    Feature::for(Portal::named($portal))->{$active ? 'activate' : 'deactivate'}($instance->name);
                }

                return;
            }

            $active
                ? Feature::activateForEveryone($instance->name)
                : Feature::deactivateForEveryone($instance->name);
        });

        $this->flash($active
            ? __('":flag" is now on everywhere.', ['flag' => $instance->label()])
            : __('":flag" is now off everywhere.', ['flag' => $instance->label()]));
    }

    /** Forget every stored value, so each portal/user is decided by initial() again. */
    public function resetFlag(string $flag): void
    {
        $instance = $this->authorizeFor($flag);

        $this->write(fn () => Feature::purge($instance->name));

        $this->flash(__('":flag" is back to its default rule.', ['flag' => $instance->label()]));
    }

    private function authorizeFor(string $flag): FeatureFlag
    {
        abort_unless(Gate::allows('feature-flags.manage'), 403);

        $instance = Flags::all()[$flag] ?? null;

        abort_if($instance === null, 404);

        return $instance;
    }

    private function write(Closure $change): void
    {
        if ($this->migrated()) {
            $change();
        }
    }

    private function flash(string $message): void
    {
        $this->status = $message;
    }

    /**
     * How many users have this flag decided, and for how many it is on.
     * Only the database store can be counted.
     *
     * @return array{active: int, total: int}|null
     */
    private function userCounts(FeatureFlag $flag): ?array
    {
        $store = config('pennant.default');

        if (config("pennant.stores.{$store}.driver") !== 'database') {
            return null;
        }

        $rows = DB::connection(config("pennant.stores.{$store}.connection"))
            ->table(config("pennant.stores.{$store}.table") ?? 'features')
            ->where('name', $flag->name)
            ->get(['scope', 'value'])
            // Not a LIKE: MySQL would read the backslashes in the class name as escapes.
            ->filter(fn (object $row) => str_starts_with($row->scope, User::class.'|'))
            ->pluck('value');

        return [
            'active' => $rows->filter(fn (string $value) => (bool) json_decode($value))->count(),
            'total' => $rows->count(),
        ];
    }
};
