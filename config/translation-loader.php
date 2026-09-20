<?php

use App\Modules\Language\Models\LanguageLine;
use Spatie\TranslationLoader\TranslationLoaderManager;

return [

    /*
     * Language lines will be fetched by these loaders. Left empty on purpose: the
     * Language module (app/Modules/Language) sets the Db loader while it is
     * enabled, so database translation overrides never apply with it off.
     */
    'translation_loaders' => [],

    /*
     * This is the model used by the Db Translation loader. You can put any model here
     * that extends Spatie\TranslationLoader\LanguageLine.
     */
    'model' => LanguageLine::class,

    /*
     * This is the translation manager which overrides the default Laravel `translation.loader`
     */
    'translation_manager' => TranslationLoaderManager::class,

];
