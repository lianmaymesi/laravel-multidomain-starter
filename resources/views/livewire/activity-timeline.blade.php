<div class="space-y-6">

    @if ($isGlobal)
    <div class="flex items-center justify-between gap-3">
        <flux:text class="text-sm text-zinc-500 dark:text-white/50">Every change, permanently — nothing here can be edited or deleted.</flux:text>
        <flux:select wire:model.live="modelFilter" class="max-w-48">
            <flux:select.option value="">All models</flux:select.option>
            @foreach ($this->availableModels() as $fqcn => $label)
            <flux:select.option value="{{ $fqcn }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
    @endif

    @php $days = $this->days(); @endphp

    @if ($days->isEmpty())
    <flux:card>
        <flux:text class="text-sm text-zinc-500 dark:text-white/40">No activity recorded yet.</flux:text>
    </flux:card>
    @endif

    <div class="space-y-8">
        @foreach ($days as $day => $groups)
        <div wire:key="day-{{ $day }}">
            <flux:heading size="sm" class="mb-3 text-zinc-400 dark:text-white/30">
                {{ \Illuminate\Support\Carbon::parse($day)->isToday() ? 'Today' : \Illuminate\Support\Carbon::parse($day)->format('l, F j, Y') }}
            </flux:heading>

            <div class="relative space-y-6 ps-2">
                @foreach ($groups as $group)
                @php
                    $activity = $group['activity'];
                    $similar = $group['similar'];
                    $style = $this->iconFor($activity);
                    $notLast = ! $loop->last;
                @endphp

                {{-- entry --}}
                <div class="relative flex gap-3" wire:key="activity-{{ $activity->id }}" x-data="{ commentsOpen: false }">
                    <div class="flex flex-col items-center">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-white
                            @if ($style['color'] === 'emerald') bg-emerald-500
                            @elseif ($style['color'] === 'blue') bg-blue-500
                            @elseif ($style['color'] === 'red') bg-red-500
                            @elseif ($style['color'] === 'purple') bg-purple-500
                            @elseif ($style['color'] === 'indigo') bg-indigo-500
                            @elseif ($style['color'] === 'teal') bg-teal-500
                            @else bg-zinc-400 dark:bg-white/20
                            @endif">
                            <flux:icon :icon="$style['icon']" class="size-4" />
                        </span>
                        @if ($notLast || $similar->isNotEmpty())
                        <span class="mt-1 w-px flex-1 bg-zinc-200 dark:bg-white/10"></span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1 pb-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 text-xs text-zinc-400 dark:text-white/30">
                                    <span>{{ $activity->created_at->format('g:i A') }}</span>
                                    @if ($isGlobal)
                                    <flux:badge size="sm" color="zinc">{{ $this->modelLabel($activity->subject_type) }} — {{ $this->recordLabel($activity) }}</flux:badge>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-sm font-semibold text-zinc-800 dark:text-white/90">{{ $activity->description }}</p>
                                <div class="mt-0.5 flex items-center gap-1.5 text-xs text-zinc-500 dark:text-white/40">
                                    @if ($activity->causer)
                                    <flux:avatar size="xs" name="{{ $activity->causer->name }}" />
                                    <span>{{ $activity->causer->name }}</span>
                                    @else
                                    <span>System</span>
                                    @endif
                                </div>
                                @php $lines = $this->changeLines($activity); @endphp
                                @if (! empty($lines))
                                <ul class="mt-1.5 space-y-0.5">
                                    @foreach ($lines as $line)
                                    <li class="text-xs text-zinc-500 dark:text-white/50">{{ $line }}</li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                            <button type="button" @click="commentsOpen = !commentsOpen"
                                class="flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs text-zinc-400 dark:text-white/30 hover:bg-zinc-100 dark:hover:bg-white/6">
                                <flux:icon.chat-bubble-left class="size-4" />
                                {{ $activity->comments->count() }}
                            </button>
                        </div>

                        @if ($similar->isNotEmpty())
                        <div x-data="{ open: false }" class="mt-2 ms-1 border-s border-zinc-200 dark:border-white/10 ps-3">
                            <button type="button" @click="open = !open" class="flex items-center gap-1.5 text-sm text-blue-500 hover:text-blue-600">
                                <flux:icon.ellipsis-horizontal-circle class="size-4" />
                                <span x-show="!open">Show {{ $similar->count() }} similar {{ Str::plural('activity', $similar->count()) }}</span>
                                <span x-show="open" x-cloak>Hide similar activities</span>
                            </button>
                            <div x-show="open" x-cloak class="mt-2 space-y-2">
                                @foreach ($similar as $extra)
                                <div class="text-xs text-zinc-500 dark:text-white/40" wire:key="similar-{{ $extra->id }}">
                                    {{ $extra->created_at->format('g:i A') }} — {{ $extra->causer?->name ?? 'System' }}
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div x-show="commentsOpen" x-cloak class="mt-3 space-y-3 rounded-md bg-zinc-50 dark:bg-white/3 p-3">
                            @forelse ($activity->comments->sortBy('created_at') as $comment)
                            <div class="flex items-start gap-2" wire:key="comment-{{ $comment->id }}">
                                <flux:avatar size="xs" name="{{ $comment->user?->name ?? 'Deleted user' }}" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-white/40">
                                        <span class="font-medium text-zinc-700 dark:text-white/70">{{ $comment->user?->name ?? 'Deleted user' }}</span>
                                        <span>{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-700 dark:text-white/75 whitespace-pre-wrap">{{ $comment->body }}</p>
                                    @php $mine = $this->myReaction($comment); @endphp
                                    <div class="mt-1 flex items-center gap-3">
                                        <button type="button" wire:click="toggleReaction({{ $comment->id }}, true)"
                                            class="flex items-center gap-1 text-xs {{ $mine === true ? 'text-emerald-500' : 'text-zinc-400 dark:text-white/30 hover:text-zinc-600 dark:hover:text-white/60' }}">
                                            <flux:icon.hand-thumb-up class="size-3.5" />
                                            {{ $comment->reactions->where('is_like', true)->count() }}
                                        </button>
                                        <button type="button" wire:click="toggleReaction({{ $comment->id }}, false)"
                                            class="flex items-center gap-1 text-xs {{ $mine === false ? 'text-red-500' : 'text-zinc-400 dark:text-white/30 hover:text-zinc-600 dark:hover:text-white/60' }}">
                                            <flux:icon.hand-thumb-down class="size-3.5" />
                                            {{ $comment->reactions->where('is_like', false)->count() }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <flux:text class="text-xs text-zinc-400 dark:text-white/30">No comments yet.</flux:text>
                            @endforelse

                            @can('activity.comment')
                            <form wire:submit="postComment({{ $activity->id }})" class="flex items-start gap-2">
                                <div class="flex-1">
                                    <flux:textarea wire:model="commentDrafts.{{ $activity->id }}" rows="2" placeholder="Explain why this changed…" />
                                </div>
                                <flux:button type="submit" size="sm" variant="primary">Post</flux:button>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    @if ($this->hasMore())
    <div class="flex justify-center">
        <flux:button variant="ghost" wire:click="loadMore">Load more</flux:button>
    </div>
    @endif

</div>
