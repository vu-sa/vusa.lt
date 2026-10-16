<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Typesense\Client;

/**
 * Cheap liveness probes, fast enough for the public `/up` endpoint (see DiagnoseHealth).
 * SystemMonitorService keeps the detailed, admin-only diagnostics.
 */
class ServiceProbes
{
    /**
     * Probes Reverb's own `/up` over the host and port Laravel publishes broadcasts to.
     *
     * @return array<string, mixed>
     */
    public function reverbStatus(): array
    {
        if (config('broadcasting.default') !== 'reverb') {
            return ['status' => 'disabled', 'last_check' => now()->toISOString()];
        }

        $options = config('broadcasting.connections.reverb.options');
        $url = sprintf('%s://%s:%s/up', $options['scheme'] ?? 'http', $options['host'] ?? '127.0.0.1', $options['port'] ?? 6001);

        try {
            $response = Http::timeout(2)->get($url);
        } catch (\Throwable $e) {
            return ['status' => 'error', 'url' => $url, 'error' => $e->getMessage(), 'last_check' => now()->toISOString()];
        }

        return [
            'status' => $response->successful() ? 'healthy' : 'error',
            'url' => $url,
            'http_status' => $response->status(),
            'last_check' => now()->toISOString(),
        ];
    }

    public function typesenseIsUp(): bool
    {
        try {
            return (app(Client::class)->health->retrieve()['ok'] ?? false) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function diskUsedPercent(): ?float
    {
        $total = @disk_total_space(base_path());
        $free = @disk_free_space(base_path());

        if (! $total || $free === false) {
            return null;
        }

        return round(($total - $free) / $total * 100, 1);
    }
}
