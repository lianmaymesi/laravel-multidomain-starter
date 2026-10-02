<?php

namespace App\Modules\Media;

use App\Events\UserAnonymized;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Services\MediaLibrary;
use App\Support\Modules\Module;
use App\Support\Modules\ModuleProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

class MediaServiceProvider extends ModuleProvider
{
    protected function module(): string
    {
        return 'media';
    }

    protected function label(): string
    {
        return 'Media';
    }

    protected function description(): string
    {
        return 'Media library with drag-and-drop uploads, image sizes and a file picker, on local disk or any S3-compatible storage.';
    }

    protected function icon(): string
    {
        return 'photo';
    }

    protected function permissions(): array
    {
        // view: open the Media Library and browse everyone's files in pickers.
        // manage: edit or delete any file (everyone may edit/delete their own).
        return ['media.view', 'media.manage'];
    }

    protected function bootModule(): void
    {
        // media::library (backoffice page), media::field (picker for forms)
        Livewire::addNamespace('media', viewPath: $this->modulePath('resources/views/livewire'));
        Blade::anonymousComponentPath($this->modulePath('resources/views/components'), 'media');

        Module::contribute('backoffice.nav', [[
            'label' => 'Media Library',
            'route' => 'backoffice.media.index',
            'icon' => 'photo',
            'permission' => 'media.view',
            'order' => 25,
        ]]);

        Module::contribute('account.settings.cards', [[
            'component' => 'media::profile-photo',
            'order' => 10,
        ]]);

        // Account deletion keeps the (anonymized) user row. Drop what it uses
        // (profile photo), then every file they uploaded that nothing uses.
        Event::listen(UserAnonymized::class, function (UserAnonymized $event) {
            app(MediaLibrary::class)->attachments($event->user)->delete();

            Media::query()
                ->originals()
                ->where('uploaded_by', $event->user->getKey())
                ->whereDoesntHave('attachments')
                ->get()
                ->each->delete();
        });
    }
}
