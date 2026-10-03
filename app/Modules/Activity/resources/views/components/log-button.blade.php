{{--
    Drop-in activity-log trigger for any list row: a small icon button that
    opens a modal scoped to that one record's history (its own attribute
    changes plus any relation-changes performed on it — see ActivityTimeline).

    Usage: <x-activity-log-button :model="$role" />
--}}
@props(['model'])

@php $modalName = 'activity-'.str(class_basename($model))->kebab().'-'.$model->getKey(); @endphp

<flux:modal.trigger name="{{ $modalName }}">
    <flux:button size="xs" variant="ghost" icon="clock">
        {{ __('Activity') }}
    </flux:button>
</flux:modal.trigger>

<flux:modal name="{{ $modalName }}" class="md:w-xl">
    <div class="space-y-6">
        <flux:heading size="lg">{{ __('Activity') }}</flux:heading>
        <livewire:activity-timeline :model="$model" :key="$modalName" />
    </div>
</flux:modal>
