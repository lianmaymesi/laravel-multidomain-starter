<?php

namespace App\Modules\Media;

use App\Events\UserAnonymized;
use App\Modules\Media\Concerns\HasMedia;
use App\Support\Modules\Module;
use App\Support\Modules\ModuleProvider;
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
        return 'File uploads with image thumbnails, on local disk or any S3-compatible storage.';
    }

    protected function icon(): string
    {
        return 'photo';
    }

    protected function bootModule(): void
    {
        // <livewire:media::uploader :model="$user" collection="avatar" />
        Livewire::addNamespace('media', viewPath: $this->modulePath('resources/views/livewire'));

        Module::contribute('account.settings.cards', [[
            'component' => 'media::profile-photo',
            'order' => 10,
        ]]);

        // Account deletion keeps the (anonymized) user row, so its photo would
        // otherwise outlive the account.
        Event::listen(UserAnonymized::class, function (UserAnonymized $event) {
            if (in_array(HasMedia::class, class_uses_recursive($event->user), true)) {
                $event->user->clearMedia();
            }
        });
    }
}
