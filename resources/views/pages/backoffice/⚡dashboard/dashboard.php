<?php

use Livewire\Component;

new class extends Component
{
    /** @var array<int, array{label: string, value: string, delta: string, positive: bool, icon: string}> */
    public array $stats = [];

    /** @var array<int, array{label: string, value: int}> */
    public array $ticketVolume = [];

    /** @var array<int, array{title: string, meta: string, icon: string, color: string}> */
    public array $auditLog = [];

    public function mount(): void
    {
        $this->stats = [
            ['label' => 'Pending Approvals', 'value' => '23', 'delta' => '+5', 'positive' => false, 'icon' => 'clipboard-document-check'],
            ['label' => 'Open Support Tickets', 'value' => '67', 'delta' => '-8', 'positive' => true, 'icon' => 'lifebuoy'],
            ['label' => 'Staff Online', 'value' => '14', 'delta' => '+2', 'positive' => true, 'icon' => 'user-group'],
            ['label' => 'System Uptime', 'value' => '99.98%', 'delta' => '+0.02%', 'positive' => true, 'icon' => 'server-stack'],
        ];

        $this->ticketVolume = [
            ['label' => 'Mon', 'value' => 61],
            ['label' => 'Tue', 'value' => 48],
            ['label' => 'Wed', 'value' => 72],
            ['label' => 'Thu', 'value' => 39],
            ['label' => 'Fri', 'value' => 55],
            ['label' => 'Sat', 'value' => 21],
            ['label' => 'Sun', 'value' => 17],
        ];

        $this->auditLog = [
            ['title' => 'Staff Priya approved refund #8821', 'meta' => '6 minutes ago', 'icon' => 'check-circle', 'color' => 'emerald'],
            ['title' => 'New staff account created for Alex', 'meta' => '42 minutes ago', 'icon' => 'user-plus', 'color' => 'blue'],
            ['title' => 'Support ticket #3390 escalated', 'meta' => '2 hours ago', 'icon' => 'exclamation-triangle', 'color' => 'amber'],
            ['title' => 'Scheduled maintenance completed', 'meta' => '5 hours ago', 'icon' => 'wrench-screwdriver', 'color' => 'blue'],
        ];
    }
};
