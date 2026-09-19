<?php

namespace App\Listeners;

use App\Services\DeviceMetricService;
use Illuminate\Auth\Events\Login;

class RecordDeviceLogin
{
    public function __construct(
        private readonly DeviceMetricService $deviceMetricService
    ) {}

    public function handle(Login $event): void
    {
        $userAgent = request()->userAgent();

        $this->deviceMetricService->recordLogin($userAgent);
    }
}
