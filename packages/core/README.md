# atrium-php/core

Core of [Atrium](https://github.com/atrium-php/atrium): multidomain portals, auth, settings and the module system.

> **Read-only split.** This package is developed in the [atrium-php/atrium](https://github.com/atrium-php/atrium) monorepo under `packages/core`. Send issues and pull requests there.

## Modules as packages

Any installed Composer package with `"type": "atrium-module"` is picked up as a module, first-party or not:

```json
{
    "name": "acme/invoices",
    "type": "atrium-module",
    "require": { "atrium-php/core": "^1.0" },
    "extra": {
        "atrium": {
            "module": "invoices",
            "provider": "Acme\\Invoices\\InvoicesServiceProvider",
            "enabled": true
        }
    }
}
```

The provider extends `Atrium\Core\Support\Modules\ModuleProvider`. `module` defaults to the package name after the slash, `enabled` (the default until `config/modules.php` or the backoffice Modules page say otherwise) to `true`.

## License

MIT
