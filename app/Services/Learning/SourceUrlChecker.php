<?php

namespace App\Services\Learning;

use App\Models\KnowledgeItemSource;
use App\Services\Documents\ThumbnailStore;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

class SourceUrlChecker
{
    private static bool $loggedMissingPdftoppm = false;

    public function __construct(private readonly SafeUrlFetcher $fetcher, private readonly ThumbnailStore $thumbnails) {}

    public function check(KnowledgeItemSource $source): void
    {
        if (! $source->isUrl()) {
            return;
        }

        $result = $this->fetcher->head((string) $source->url);
        if (in_array($result->status, [405, 501], true)) {
            $result = $this->fetcher->get((string) $source->url, 1024 * 1024);
        }
        $reachable = ($result->status >= 200 && $result->status < 300) || in_array($result->status, [401, 403], true);
        $source->forceFill([
            'checked_at' => now(),
            'check_status' => $result->status,
            'check_failures' => $reachable ? 0 : min(255, $source->check_failures + 1),
            'content_version' => $result->version,
        ])->saveQuietly();

        if (! $reachable || in_array($result->status, [401, 403], true)) {
            return;
        }
        if ($source->thumbnail_path !== null && ($result->version === null || $result->version === $source->thumbnail_version)) {
            return;
        }

        try {
            $image = $this->preview($result);
            if ($image === null) {
                return;
            }
            $path = 'source-thumbnails/'.$source->id.'.webp';
            $this->thumbnails->put($path, $image);
            $source->forceFill(['thumbnail_path' => $path, 'thumbnail_version' => $result->version ?? sha1($image)])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Failed to refresh source URL preview', ['source_id' => $source->id, 'error' => $e->getMessage()]);
        }
    }

    protected function pdftoppm(): ?string
    {
        return (new ExecutableFinder)->find('pdftoppm');
    }

    private function preview(FetchResult $headers): ?string
    {
        $result = $headers->body !== null ? $headers : $this->fetcher->get($headers->finalUrl, $headers->contentType === 'application/pdf' ? 20 * 1024 * 1024 : 1024 * 1024);
        $this->requireSuccess($result);
        if ($result->contentType === 'application/pdf' || str_starts_with($result->body ?? '', '%PDF-')) {
            return $this->pdfPreview($result->body ?? '');
        }
        if (! in_array($result->contentType, ['text/html', 'application/xhtml+xml'], true)) {
            return null;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($result->body ?? '', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        foreach (['//meta[@property="og:image"]/@content', '//meta[@name="twitter:image"]/@content'] as $query) {
            $nodes = $xpath->query($query);
            $url = $nodes === false ? '' : trim((string) $nodes->item(0)?->nodeValue);
            if ($url !== '') {
                $image = $this->fetcher->get(SafeUrlFetcher::resolveUrl($result->finalUrl, $url), 5 * 1024 * 1024);
                $this->requireSuccess($image);
                $bytes = $image->body ?? '';
                $valid = match ($image->contentType) {
                    'image/jpeg' => str_starts_with($bytes, "\xff\xd8\xff"),
                    'image/png' => str_starts_with($bytes, "\x89PNG"),
                    'image/gif' => str_starts_with($bytes, 'GIF8'),
                    'image/webp' => str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP',
                    default => false,
                };
                if (! $valid) {
                    throw new \RuntimeException('Preview image type or signature is invalid');
                }

                return $bytes;
            }
        }

        return null;
    }

    private function requireSuccess(FetchResult $result): void
    {
        if ($result->status < 200 || $result->status >= 300) {
            throw new \RuntimeException($result->reason ?? 'Preview returned HTTP '.$result->status);
        }
    }

    private function pdfPreview(string $body): ?string
    {
        $executable = $this->pdftoppm();
        if ($executable === null) {
            if (! self::$loggedMissingPdftoppm) {
                Log::info('pdftoppm is unavailable; skipping source PDF previews');
                self::$loggedMissingPdftoppm = true;
            }

            return null;
        }

        $pdfPath = tempnam(sys_get_temp_dir(), 'source-pdf-');
        if ($pdfPath === false) {
            throw new \RuntimeException('Could not create temporary PDF');
        }
        $outPrefix = $pdfPath.'-preview';
        try {
            if (file_put_contents($pdfPath, $body) === false) {
                throw new \RuntimeException('Could not write temporary PDF');
            }
            $process = Process::timeout(30)->run([$executable, '-f', '1', '-l', '1', '-r', '72', '-scale-to', '1200', '-png', $pdfPath, $outPrefix]);
            $output = (glob($outPrefix.'-*.png') ?: [])[0] ?? null;
            if (! $process->successful() || $output === null) {
                throw new \RuntimeException('pdftoppm could not render the first page');
            }
            $image = file_get_contents($output);
            if ($image === false || ! str_starts_with($image, "\x89PNG")) {
                throw new \RuntimeException('pdftoppm did not produce a PNG');
            }

            return $image;
        } finally {
            foreach (glob($outPrefix.'-*') ?: [] as $file) {
                @unlink($file);
            }
            @unlink($pdfPath);
        }
    }
}
