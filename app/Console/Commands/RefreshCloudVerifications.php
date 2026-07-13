<?php

namespace App\Console\Commands;

use App\Models\CloudConnection;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Console\Command;
use Throwable;

class RefreshCloudVerifications extends Command
{
    protected $signature = 'shipped:refresh-cloud-verifications';

    protected $description = 'Refresh Laravel Cloud verification evidence for active connections.';

    public function handle(ProjectVerificationService $verificationService): int
    {
        CloudConnection::query()
            ->where('status', 'connected')
            ->whereNotNull('api_token')
            ->where('api_token', '!=', '')
            ->chunkById(100, function ($connections) use ($verificationService): void {
                foreach ($connections as $connection) {
                    try {
                        $verificationService->refresh($connection);
                    } catch (Throwable $exception) {
                        report($exception);
                        $this->error("Unable to refresh Laravel Cloud connection {$connection->id}.");
                    }
                }
            });

        return self::SUCCESS;
    }
}
