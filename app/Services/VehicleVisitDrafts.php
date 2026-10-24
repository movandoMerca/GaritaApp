<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Private temporary photos and submission state; retained for same-page retries. */
class VehicleVisitDrafts
{
    public function read(int $owner, string $uuid): array
    {
        return $this->locked($owner, $uuid, function ($draft) { return $draft; }, false);
    }

    public function edit(int $owner, string $uuid, array $state): array
    {
        return $this->locked($owner, $uuid, function ($draft) use ($state) {
            if (empty($draft['frozen'])) $draft['state'] = $state;
            return $draft;
        });
    }

    public function photo(int $owner, string $uuid, int $generation, string $role, string $bytes, string $extension): array
    {
        $this->role($role);
        return $this->locked($owner, $uuid, function ($draft) use ($owner, $uuid, $generation, $role, $bytes, $extension) {
            if (!empty($draft['frozen']) || ($draft['state']['captureGeneration'] ?? -1) !== $generation) return $draft;
            $path = $this->directory($owner, $uuid)."/photos/$generation-$role.$extension";
            if (!Storage::disk('local')->put($path, $bytes)) throw new RuntimeException('No se pudo conservar la fotografía temporal.');
            $previous = $draft['photos'][$role]['path'] ?? null;
            if ($previous && $previous !== $path) Storage::disk('local')->delete($previous);
            $draft['photos'][$role] = ['path' => $path, 'generation' => $generation];
            return $draft;
        });
    }

    public function resetPhotos(int $owner, string $uuid, array $state): array
    {
        return $this->locked($owner, $uuid, function ($draft) use ($state) {
            if (!empty($draft['frozen'])) return $draft;
            foreach ($draft['photos'] ?? [] as $photo) Storage::disk('local')->delete($photo['path']);
            $draft['state'] = $state;
            $draft['photos'] = [];
            return $draft;
        });
    }

    public function freeze(int $owner, string $uuid, array $state): array
    {
        return $this->locked($owner, $uuid, function ($draft) use ($state) {
            if (empty($draft['frozen'])) {
                $draft['state'] = $state;
                $draft['frozen'] = ['state' => $state, 'photos' => $draft['photos'] ?? []];
            }
            return $draft;
        });
    }

    public function thaw(int $owner, string $uuid): array
    {
        return $this->locked($owner, $uuid, function ($draft) { unset($draft['frozen']); return $draft; });
    }

    public function discard(int $owner, string $uuid)
    {
        $this->locked($owner, $uuid, function ($draft) {
            foreach ($draft['photos'] ?? [] as $photo) Storage::disk('local')->delete($photo['path']);
            return [];
        });
    }

    public function bytes(int $owner, string $uuid, array $photo): string
    {
        $path = $photo['path'] ?? '';
        abort_unless(is_string($path) && strpos($path, $this->directory($owner, $uuid).'/photos/') === 0
            && strpos($path, '..') === false, 404);
        return Storage::disk('local')->get($path);
    }

    public function preview(int $owner, string $uuid, int $generation, string $role): array
    {
        $this->role($role);
        $photo = $this->read($owner, $uuid)['photos'][$role] ?? null;
        abort_unless($photo && $photo['generation'] === $generation, 404);
        $bytes = $this->bytes($owner, $uuid, $photo);
        return ['bytes' => $bytes, 'mime' => getimagesizefromstring($bytes)['mime']];
    }

    private function directory(int $owner, string $uuid): string
    {
        abort_unless($owner > 0 && Str::isUuid($uuid), 422);
        return "vehicle-drafts/$owner/$uuid";
    }

    private function role(string $role)
    {
        abort_unless(in_array($role, ['licencia', 'placa', 'visitante'], true), 404);
    }

    private function locked(int $owner, string $uuid, callable $callback, bool $write = true): array
    {
        $disk = Storage::disk('local');
        $directory = $this->directory($owner, $uuid);
        $disk->makeDirectory($directory);
        $lock = fopen($disk->path($directory.'/.lock'), 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('No se pudo verificar el formulario.');
        try {
            $path = $directory.'/draft.json';
            $draft = $disk->exists($path) ? json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR) : [];
            $draft = $callback($draft);
            if ($write && !$disk->put($path, json_encode($draft, JSON_THROW_ON_ERROR))) throw new RuntimeException('No se pudo conservar los datos del formulario.');
            return $draft;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
