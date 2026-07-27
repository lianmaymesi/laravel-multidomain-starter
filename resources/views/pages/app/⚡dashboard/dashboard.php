<?php

use Livewire\Component;

new class extends Component
{
    /** @var array<int, array{label: string, value: string, delta: string, positive: bool, icon: string}> */
    public array $stats = [];

    /** @var array<int, array{label: string, value: int}> */
    public array $weeklyActivity = [];

    /** @var array<int, array{title: string, meta: string, icon: string, color: string}> */
    public array $activity = [];

    public function mount(): void
    {
        $this->stats = [
            ['label' => 'Total Revenue', 'value' => '$48,290', 'delta' => '+12.4%', 'positive' => true, 'icon' => 'currency-dollar'],
            ['label' => 'Active Users', 'value' => '2,842', 'delta' => '+4.1%', 'positive' => true, 'icon' => 'users'],
            ['label' => 'Open Orders', 'value' => '186', 'delta' => '-2.3%', 'positive' => false, 'icon' => 'shopping-bag'],
            ['label' => 'Conversion Rate', 'value' => '3.42%', 'delta' => '+0.6%', 'positive' => true, 'icon' => 'chart-bar'],
        ];

        $this->weeklyActivity = [
            ['label' => 'Mon', 'value' => 42],
            ['label' => 'Tue', 'value' => 68],
            ['label' => 'Wed', 'value' => 51],
            ['label' => 'Thu', 'value' => 87],
            ['label' => 'Fri', 'value' => 74],
            ['label' => 'Sat', 'value' => 33],
            ['label' => 'Sun', 'value' => 58],
        ];

        $this->activity = [
            ['title' => 'New order #4821 received', 'meta' => '2 minutes ago', 'icon' => 'shopping-cart', 'color' => 'blue'],
            ['title' => 'Invoice #1092 paid', 'meta' => '18 minutes ago', 'icon' => 'check-circle', 'color' => 'emerald'],
            ['title' => 'New user registered', 'meta' => '1 hour ago', 'icon' => 'user-plus', 'color' => 'blue'],
            ['title' => 'Payment failed for order #4790', 'meta' => '3 hours ago', 'icon' => 'exclamation-triangle', 'color' => 'amber'],
        ];
    }
};
