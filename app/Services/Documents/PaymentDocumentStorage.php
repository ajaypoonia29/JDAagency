<?php

declare(strict_types=1);

namespace App\Services\Documents;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentDocumentStorage
{
    private const PRIVATE_DISK = 'local';

    private const LEGACY_PUBLIC_DISK = 'public';

    public function exists(?string $path): bool
    {
        $path = $this->normalize($path);

        if (! $path) {
            return false;
        }

        return Storage::disk(self::PRIVATE_DISK)->exists($path)
            || Storage::disk(self::LEGACY_PUBLIC_DISK)->exists($path);
    }

    public function existsOnPrivate(?string $path): bool
    {
        $path = $this->normalize($path);

        return filled($path)
            && Storage::disk(self::PRIVATE_DISK)->exists($path);
    }

    public function existsOnLegacyPublic(?string $path): bool
    {
        $path = $this->normalize($path);

        return filled($path)
            && Storage::disk(self::LEGACY_PUBLIC_DISK)->exists($path);
    }

    /**
     * Move a legacy public document to private storage.
     */
    public function privatize(string $path): string
    {
        $path = $this->normalizeRequired($path);
        $private = Storage::disk(self::PRIVATE_DISK);
        $public = Storage::disk(self::LEGACY_PUBLIC_DISK);

        if ($private->exists($path)) {
            $public->delete($path);

            return $path;
        }

        if (! $public->exists($path)) {
            return $path;
        }

        $contents = $public->get($path);

        if (! $private->put($path, $contents)) {
            throw new RuntimeException(
                "Unable to move finance document to private storage: {$path}"
            );
        }

        $public->delete($path);

        return $path;
    }

    public function absolutePath(?string $path): ?string
    {
        $path = $this->normalize($path);

        if (! $path) {
            return null;
        }

        $this->privatize($path);

        $private = Storage::disk(self::PRIVATE_DISK);

        if (! $private->exists($path)) {
            return null;
        }

        return $private->path($path);
    }

    public function download(
        string $path,
        string $downloadName,
    ): BinaryFileResponse {
        $absolutePath = $this->absolutePath($path);

        abort_if(! $absolutePath, 404);

        return response()->download(
            $absolutePath,
            $downloadName,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    /**
     * Delete documents from both private and legacy public storage.
     *
     * @param string|array<int, string> $paths
     */
    public function delete(string|array $paths): bool
    {
        $paths = collect((array) $paths)
            ->map(fn (mixed $path): ?string => $this->normalize(
                is_string($path) ? $path : null,
            ))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($paths === []) {
            return true;
        }

        $privateDeleted = Storage::disk(self::PRIVATE_DISK)
            ->delete($paths);
        $publicDeleted = Storage::disk(self::LEGACY_PUBLIC_DISK)
            ->delete($paths);

        return $privateDeleted && $publicDeleted;
    }

    /**
     * @return array{
     *     path: ?string,
     *     disk: ?string,
     *     existed: bool,
     *     contents: ?string
     * }
     */
    public function snapshot(?string $path): array
    {
        $path = $this->normalize($path);

        if (! $path) {
            return [
                'path' => null,
                'disk' => null,
                'existed' => false,
                'contents' => null,
            ];
        }

        foreach (
            [self::PRIVATE_DISK, self::LEGACY_PUBLIC_DISK]
            as $diskName
        ) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path)) {
                return [
                    'path' => $path,
                    'disk' => $diskName,
                    'existed' => true,
                    'contents' => $disk->get($path),
                ];
            }
        }

        return [
            'path' => $path,
            'disk' => null,
            'existed' => false,
            'contents' => null,
        ];
    }

    /**
     * @param array{
     *     path: ?string,
     *     disk: ?string,
     *     existed: bool,
     *     contents: ?string
     * }|null $snapshot
     */
    public function restore(
        ?array $snapshot,
        ?string $generatedPath,
    ): void {
        $snapshotPath = $this->normalize($snapshot['path'] ?? null);
        $generatedPath = $this->normalize($generatedPath);

        if (
            $generatedPath
            && $generatedPath !== $snapshotPath
        ) {
            $this->delete($generatedPath);
        }

        if (! $snapshotPath) {
            return;
        }

        if (($snapshot['existed'] ?? false) !== true) {
            $this->delete($snapshotPath);

            return;
        }

        $diskName = in_array(
            $snapshot['disk'] ?? null,
            [self::PRIVATE_DISK, self::LEGACY_PUBLIC_DISK],
            true,
        )
            ? $snapshot['disk']
            : self::PRIVATE_DISK;

        $this->delete($snapshotPath);

        Storage::disk($diskName)->put(
            $snapshotPath,
            (string) ($snapshot['contents'] ?? ''),
        );
    }

    private function normalizeRequired(string $path): string
    {
        $path = $this->normalize($path);

        if (! $path) {
            throw new InvalidArgumentException(
                'Finance document path is required.'
            );
        }

        return $path;
    }

    private function normalize(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = str_replace('\\', '/', trim((string) $path));
        $path = ltrim($path, '/');

        if (
            $path === ''
            || str_contains($path, "\0")
            || preg_match('/^[A-Za-z]:/', $path)
            || preg_match('#(^|/)\.\.?(/|$)#', $path)
        ) {
            throw new InvalidArgumentException(
                'Invalid finance document path.'
            );
        }

        return $path;
    }
}
