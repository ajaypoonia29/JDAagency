<?php

declare(strict_types=1);

namespace App\Services\Documents;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceDocumentStorage
{
    public const DISK = 'local';

    public function put(string $path, string $contents): string
    {
        $path = $this->normalizeRequired($path);
        Storage::disk(self::DISK)->put($path, $contents);

        return $path;
    }

    public function exists(?string $path): bool
    {
        $path = $this->normalize($path);

        return $path
            ? Storage::disk(self::DISK)->exists($path)
            : false;
    }

    public function delete(string|array|null $paths): bool
    {
        $paths = collect((array) $paths)
            ->map(fn (mixed $path): ?string => $this->normalize(
                is_string($path) ? $path : null,
            ))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $paths === []
            || Storage::disk(self::DISK)->delete($paths);
    }

    public function absolutePath(?string $path): ?string
    {
        $path = $this->normalize($path);

        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->path($path);
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

    private function normalizeRequired(string $path): string
    {
        $path = $this->normalize($path);

        if (! $path) {
            throw new InvalidArgumentException(
                'Invoice document path is required.',
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
                'Invalid invoice document path.',
            );
        }

        return $path;
    }
}
