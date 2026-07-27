<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Termwind\render;

class MakeSubdomainCommand extends Command
{
    protected $signature = 'make:subdomain {name : The subdomain key, e.g. "blog"}';

    protected $description = 'Scaffold a new subdomain portal (css, js, layout, route file, dashboard page)';

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
            return $this->failWith(
                'Invalid subdomain name',
                'Must be lowercase alphanumeric with hyphens, starting with a letter.',
            );
        }

        if (array_key_exists($name, config('multidomain.sub_domains', []))) {
            return $this->failWith(
                'Subdomain already exists',
                "\"{$name}\" is already registered in config/multidomain.php.",
            );
        }

        if (config('multidomain.single_domain')) {
            return $this->failWith(
                'Single-domain mode is active',
                'Every portal shares one host in single-domain mode, so a freshly generated "/" route would collide with the existing ones.',
                'Set APP_SINGLE_DOMAIN=false in .env, then run this command again.',
            );
        }

        render(<<<HTML
            <div class="mx-1 my-1">
                <span class="px-1 bg-blue-600 text-white font-bold">SCAFFOLD</span>
                <span class="ml-1 text-blue-400 font-bold">{$name}</span>
                <span class="ml-1 text-gray">— new subdomain portal</span>
            </div>
            HTML);

        $access = select(
            label: 'Who should be able to access this portal?',
            options: [
                'open' => 'Open — anyone can access, no login required',
                'auth' => 'Requires authentication',
            ],
            default: 'auth',
        );

        $accessLevel = $access === 'auth'
            ? select(
                label: 'Access level',
                options: [
                    'own-role' => "This portal's own \"{$name}\" role (recommended — only users granted that role get in)",
                    'user' => 'Normal user (global, same gate as the "app" portal)',
                    'staff' => 'Staff (global, same gate as the "backoffice" portal)',
                ],
                default: 'own-role',
            )
            : null;

        $middleware = match ($accessLevel) {
            'staff' => "['auth', 'portal:staff']",
            'user' => "['auth', 'portal:user']",
            'own-role' => "['auth', 'portal:{$name}']",
            default => null,
        };

        $openRegistration = confirm(
            label: "Should visitors be able to sign up for \"{$name}\" themselves, from the public register page?",
            default: false,
            hint: 'Adds it to the "Select the user type" dropdown on sign-up, and a Register button on this portal\'s homepage.',
        );

        $registerLabel = $openRegistration
            ? text(
                label: 'What should this portal be called on the sign-up form?',
                placeholder: 'e.g. Blog Writer',
                default: Str::headline($name),
                required: true,
            )
            : null;

        $stubs = base_path('stubs/subdomain');
        $replacements = [
            '{{ name }}' => $name,
            '{{ middleware }}' => $middleware ? "->middleware({$middleware})" : '',
        ];
        $created = [];

        $created[] = $this->putFromStub("{$stubs}/css.stub", resource_path("css/{$name}.css"), $replacements);
        $created[] = $this->putFromStub("{$stubs}/js.stub", resource_path("js/{$name}.js"), $replacements);
        $created[] = $this->putFromStub("{$stubs}/layout.stub", resource_path("views/layouts/{$name}.blade.php"), $replacements);
        $created[] = $this->putFromStub("{$stubs}/route.stub", base_path("routes/{$name}.php"), $replacements);

        $pageDir = resource_path("views/pages/{$name}/⚡dashboard");
        File::ensureDirectoryExists($pageDir);
        $created[] = $this->putFromStub("{$stubs}/dashboard.stub", "{$pageDir}/dashboard.php", $replacements);
        $created[] = $this->putFromStub("{$stubs}/dashboard.blade.stub", "{$pageDir}/dashboard.blade.php", $replacements);

        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        $fileRows = collect($created)
            ->map(fn (string $path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path))
            ->map(fn (string $relative) => '<div><span class="text-green-500 font-bold">✔</span> <span class="text-gray-300">'.e($relative).'</span></div>')
            ->implode('');

        render(<<<HTML
            <div class="mx-1 my-1">
                <div class="text-green-500 font-bold mb-1">Files created</div>
                <div class="ml-2">{$fileRows}</div>
                <div class="mt-1">
                    <span class="text-green-500 font-bold">✔</span>
                    <span class="text-gray-300">Role "{$role->name}" ready — protected from deletion in the backoffice roles screen.</span>
                </div>
            </div>
            HTML);

        $steps = [
            "Add 'name' =&gt; 'name.'.env('APP_MAIN_DOMAIN') to config/multidomain.php sub_domains array (replace name with {$name})",
            "Add this block to routes/web.php:<div class=\"ml-2 text-cyan-300\">Route::domain(config('multidomain.sub_domains.{$name}'))->name('{$name}.')->group(fn () =&gt; include __DIR__.'/{$name}.php');</div>",
            "Add {$name}.&lt;APP_MAIN_DOMAIN&gt; to your local hosts/Herd config",
            "Assign the \"{$name}\" role to any user who should access this portal and be redirected here after login.",
        ];

        if ($openRegistration) {
            $steps[] = "Add '{$name}' =&gt; '".e($registerLabel)."' to config/multidomain.php registerable_portals array — turns on the \"".e($registerLabel)."\" option in the sign-up form's user-type dropdown, and the Register button on this portal's homepage.";
        }

        $stepRows = collect($steps)->map(fn (string $step) => "<li class=\"text-gray-300\">{$step}</li>")->implode('');

        render(<<<HTML
            <div class="mx-1 my-1">
                <div class="px-1 bg-amber-600 text-white font-bold">MANUAL STEPS</div>
                <ol class="ml-2 mt-1">{$stepRows}</ol>
                <div class="mt-1 text-gray">vite.config.js picks up the new css/js entries automatically — no edit needed.</div>
            </div>
            HTML);

        return self::SUCCESS;
    }

    private function failWith(string $title, string ...$lines): int
    {
        $body = collect($lines)
            ->map(fn (string $line) => '<div class="text-red-100">'.e($line).'</div>')
            ->implode('');

        render(<<<HTML
            <div class="mx-1 my-1">
                <div class="px-1 bg-red-600 text-white font-bold">✘ {$title}</div>
                <div class="mt-1">{$body}</div>
            </div>
            HTML);

        return self::FAILURE;
    }

    /** @param array<string, string> $replacements */
    private function putFromStub(string $stubPath, string $destination, array $replacements): string
    {
        $contents = strtr(File::get($stubPath), $replacements);

        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, $contents);

        return $destination;
    }
}
