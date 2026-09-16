<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\ActivityComment;
use App\Models\ActivityCommentReaction;
use App\Models\PortalSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ActivityTimeline extends Component
{
    private const PAGE_SIZE = 20;

    public ?string $subjectType = null;

    public ?int $subjectId = null;

    public bool $isGlobal = true;

    public string $modelFilter = '';

    public int $loaded = self::PAGE_SIZE;

    /** @var array<int, string> */
    public array $commentDrafts = [];

    public function mount(?Model $model = null): void
    {
        abort_unless(Gate::allows('activity.view'), 403);

        if ($model) {
            $this->subjectType = $model::class;
            $this->subjectId = $model->getKey();
            $this->isGlobal = false;
        }
    }

    public function loadMore(): void
    {
        $this->loaded += self::PAGE_SIZE;
    }

    private function baseQuery(): Builder
    {
        return Activity::query()
            ->when($this->subjectType, fn ($query) => $query
                ->where('subject_type', $this->subjectType)
                ->where('subject_id', $this->subjectId))
            ->when($this->isGlobal && $this->modelFilter, fn ($query) => $query->where('subject_type', $this->modelFilter));
    }

    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    public function hasMore(): bool
    {
        return $this->totalCount() > $this->loaded;
    }

    /**
     * Available `subject_type` values to filter by, in global mode — always
     * in sync with whatever's actually been logged, so any model added
     * later (with the LogsActivity trait) shows up automatically.
     *
     * @return Collection<string, string>
     */
    public function availableModels(): Collection
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->mapWithKeys(fn (string $fqcn) => [$fqcn => class_basename($fqcn)])
            ->sort();
    }

    /**
     * Entries grouped by calendar day, each day's list run-length-encoded
     * by description so consecutive identical events collapse into one
     * expandable "Show N similar activities" entry.
     *
     * @return Collection<string, Collection<int, array{activity: Activity, similar: Collection<int, Activity>}>>
     */
    public function days(): Collection
    {
        return $this->baseQuery()
            ->with(['causer', 'subject', 'comments.user', 'comments.reactions'])
            ->orderByDesc('created_at')
            ->limit($this->loaded)
            ->get()
            ->groupBy(fn (Activity $activity) => $activity->created_at->toDateString())
            ->sortKeysDesc()
            ->map(fn (Collection $activities) => $this->collapseSimilar($activities));
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array{activity: Activity, similar: Collection<int, Activity>}>
     */
    private function collapseSimilar(Collection $activities): Collection
    {
        $groups = collect();

        foreach ($activities as $activity) {
            $last = $groups->last();

            if ($last && $last['activity']->description === $activity->description
                && $last['activity']->subject_type === $activity->subject_type) {
                $groups->last()['similar']->push($activity);

                continue;
            }

            $groups->push(['activity' => $activity, 'similar' => collect()]);
        }

        return $groups;
    }

    /** @return array{icon: string, color: string} */
    public function iconFor(Activity $activity): array
    {
        return match (true) {
            $activity->event === 'created' => ['icon' => 'plus-circle', 'color' => 'emerald'],
            $activity->event === 'updated' => ['icon' => 'pencil-square', 'color' => 'blue'],
            $activity->event === 'deleted' => ['icon' => 'trash', 'color' => 'red'],
            $activity->description === 'permission granted' => ['icon' => 'key', 'color' => 'purple'],
            $activity->description === 'permission revoked' => ['icon' => 'key', 'color' => 'zinc'],
            $activity->description === 'roles synced' => ['icon' => 'user-group', 'color' => 'indigo'],
            $activity->description === 'role assigned' => ['icon' => 'user-plus', 'color' => 'teal'],
            $activity->description === 'role unassigned' => ['icon' => 'user-minus', 'color' => 'zinc'],
            default => ['icon' => 'bolt', 'color' => 'zinc'],
        };
    }

    public function modelLabel(string $subjectType): string
    {
        return str(class_basename($subjectType))->headline()->toString();
    }

    /**
     * A stable record label even after deletion, falling back to the
     * attribute snapshot captured at the time of the activity.
     */
    public function recordLabel(Activity $activity): string
    {
        $identifierAttribute = match ($activity->subject_type) {
            PortalSetting::class => 'portal',
            default => 'name',
        };

        if ($activity->subject) {
            return (string) ($activity->subject->{$identifierAttribute} ?? "#{$activity->subject_id}");
        }

        $snapshot = $activity->properties['old'] ?? $activity->properties['attributes'] ?? [];

        return $snapshot[$identifierAttribute] ?? "#{$activity->subject_id} (deleted)";
    }

    /**
     * One human-readable line per changed field for automatic create/update/
     * delete logs, or the raw properties for a manually logged relation
     * change (permission granted/revoked, roles synced, role assigned).
     *
     * @return array<int, string>
     */
    public function changeLines(Activity $activity): array
    {
        $attributes = $activity->properties['attributes'] ?? null;
        $old = $activity->properties['old'] ?? null;

        if ($attributes === null && $old === null) {
            return collect($activity->properties)
                ->map(fn ($value, string $key) => "{$key}: ".$this->formatValue($value))
                ->values()
                ->all();
        }

        if ($attributes !== null && $old !== null) {
            return collect($attributes)
                ->map(fn ($new, string $field) => "{$field}: ".$this->formatValue($old[$field] ?? null).' → '.$this->formatValue($new))
                ->values()
                ->all();
        }

        if ($attributes !== null) {
            return collect($attributes)
                ->map(fn ($value, string $field) => "{$field}: ".$this->formatValue($value))
                ->values()
                ->all();
        }

        return collect($old)
            ->map(fn ($value, string $field) => "{$field}: ".$this->formatValue($value).' (deleted)')
            ->values()
            ->all();
    }

    private function formatValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => '—',
            is_array($value) => empty($value) ? '—' : implode(', ', $value),
            default => (string) $value,
        };
    }

    public function postComment(int $activityId): void
    {
        abort_unless(Gate::allows('activity.comment'), 403);

        $body = trim($this->commentDrafts[$activityId] ?? '');

        if ($body === '') {
            return;
        }

        abort_unless(Activity::whereKey($activityId)->exists(), 404);

        ActivityComment::create([
            'activity_id' => $activityId,
            'user_id' => auth()->id(),
            'body' => str($body)->limit(2000)->toString(),
        ]);

        unset($this->commentDrafts[$activityId]);
    }

    public function toggleReaction(int $commentId, bool $isLike): void
    {
        abort_unless(Gate::allows('activity.view'), 403);

        $userId = auth()->id();
        $existing = ActivityCommentReaction::where('activity_comment_id', $commentId)
            ->where('user_id', $userId)
            ->first();

        if ($existing && $existing->is_like === $isLike) {
            $existing->delete();

            return;
        }

        ActivityCommentReaction::updateOrCreate(
            ['activity_comment_id' => $commentId, 'user_id' => $userId],
            ['is_like' => $isLike],
        );
    }

    public function myReaction(ActivityComment $comment): ?bool
    {
        return $comment->reactions->firstWhere('user_id', auth()->id())?->is_like;
    }

    public function render()
    {
        return view('livewire.activity-timeline');
    }
}
