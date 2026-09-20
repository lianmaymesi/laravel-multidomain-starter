@php $title = __('Currencies'); @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('Currencies') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Reference only — money is always stored as an integer in the smallest unit (cents, paisa, halala) and formatted per currency for display. Manage which currencies are active and which one is primary from Settings.') }}</flux:text>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07]">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-white/3">
                <tr class="text-start text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-white/30">
                    <th class="px-6 py-3 text-start">{{ __('Currency') }}</th>
                    <th class="px-6 py-3 text-start">{{ __('Code') }}</th>
                    <th class="px-6 py-3 text-start">{{ __('Decimals') }}</th>
                    <th class="px-6 py-3 text-start">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-start">{{ __('Rate vs primary') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-white/5">
                @foreach ($this->currencies() as $currency)
                <tr wire:key="currency-{{ $currency->id }}">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            <flux:text class="font-medium text-zinc-800 dark:text-white/80">{{ $currency->name }}</flux:text>
                            <flux:text class="text-zinc-400 dark:text-white/30">{{ $currency->symbol ?? $currency->code }}</flux:text>
                            @if ($currency->is_primary)
                            <flux:badge size="sm" color="blue">{{ __('Primary') }}</flux:badge>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-3 text-zinc-500 dark:text-white/50">{{ $currency->code }}</td>
                    <td class="px-6 py-3 text-zinc-500 dark:text-white/50">{{ $currency->decimal_digits }}</td>
                    <td class="px-6 py-3">
                        @if ($currency->is_active)
                        <flux:badge size="sm" color="emerald">{{ __('Active') }}</flux:badge>
                        @else
                        <flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-zinc-500 dark:text-white/50">
                        @if ($currency->is_primary)
                            —
                        @elseif ($currency->exchange_rate !== null)
                            1 {{ optional($this->primary())->code }} = {{ rtrim(rtrim(number_format((float) $currency->exchange_rate, 6), '0'), '.') }} {{ $currency->code }}
                            @if ($currency->rate_synced_at)
                            <flux:text class="text-xs text-zinc-400 dark:text-white/30">{{ __('synced :diff', ['diff' => $currency->rate_synced_at->diffForHumans()]) }}</flux:text>
                            @endif
                        @else
                            {{ __('Not synced yet') }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
