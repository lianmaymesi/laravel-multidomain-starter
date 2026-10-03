<?php

namespace App\Support\Health\Checks;

use App\Support\Health\Check;
use App\Support\Health\Result;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Writes, reads back and deletes a probe file on each configured disk. */
class StorageCheck implements Check
{
    public function name(): string
    {
        return 'storage';
    }

    public function label(): string
    {
        return 'Storage';
    }

    public function run(): Result
    {
        $disks = collect(config('health.disks', [null]))
            ->map(fn (?string $disk) => $disk ?? config('filesystems.default'))
            ->unique()
            ->values();

        $broken = [];

        foreach ($disks as $disk) {
            $path = 'health-check/'.Str::uuid().'.txt';
            $content = Str::random(32);

            try {
                $storage = Storage::disk($disk);
                $written = $storage->put($path, $content);
                $ok = $written && $storage->get($path) === $content;
                $storage->delete($path);
            } catch (Throwable $e) {
                $ok = false;
            }

            if (! $ok) {
                $broken[] = $disk;
            }
        }

        $meta = ['disks' => $disks->all()];

        return $broken === []
            ? Result::ok('Writable: '.$disks->implode(', ').'.', $meta)
            : Result::failed('Not writable: '.implode(', ', $broken).'.', [...$meta, 'broken' => $broken]);
    }
}
