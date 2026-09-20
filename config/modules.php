<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Modules
    |--------------------------------------------------------------------------
    |
    | Single source of truth for which optional features are switched on.
    | Each entry maps to a self-contained module under app/Modules/{Name}.
    | A disabled module registers nothing: no routes, no middleware, no
    | Livewire components, no nav entry. Its tables and data stay untouched,
    | so flipping it back on restores full function with no reseed.
    |
    | Toggle via .env (MODULE_{NAME}=false) — no code change required.
    | Always check with Module::enabled('name') (or @module('name') in Blade)
    | before touching a module from outside its own folder.
    |
    */

    'maintenance' => (bool) env('MODULE_MAINTENANCE', true),

    'currency' => (bool) env('MODULE_CURRENCY', true),

    'language' => (bool) env('MODULE_LANGUAGE', true),

];
