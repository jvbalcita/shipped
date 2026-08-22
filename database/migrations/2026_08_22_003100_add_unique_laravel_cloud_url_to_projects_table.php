<?php

use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Collapse duplicate Cloud origins, then unique the stored canonical URL.
     * Multiple NULLs remain allowed; empty strings are stored as NULL.
     */
    public function up(): void
    {
        DB::table('projects')
            ->where('laravel_cloud_url', '')
            ->update(['laravel_cloud_url' => null]);

        $duplicateOrigins = DB::table('projects')
            ->select('laravel_cloud_url')
            ->whereNotNull('laravel_cloud_url')
            ->groupBy('laravel_cloud_url')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('laravel_cloud_url');

        foreach ($duplicateOrigins as $origin) {
            /** @var Collection<int, int|string> $ids */
            $ids = DB::table('projects')
                ->where('laravel_cloud_url', $origin)
                ->orderByRaw("CASE WHEN verification_status = 'verified' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->pluck('id');

            $loserIds = $ids->slice(1)->values()->all();

            if ($loserIds === []) {
                continue;
            }

            DB::table('projects')->whereIn('id', $loserIds)->update([
                'laravel_cloud_url' => null,
                'is_public' => false,
                'verification_status' => 'unverified',
                'verified_at' => null,
                'verification_failure_reason' => ProjectVerificationService::ORIGIN_ALREADY_USED,
            ]);
        }

        if ($this->hasUniqueIndex()) {
            return;
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->unique('laravel_cloud_url', 'projects_laravel_cloud_url_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->hasUniqueIndex()) {
            return;
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropUnique('projects_laravel_cloud_url_unique');
        });
    }

    private function hasUniqueIndex(): bool
    {
        return collect(Schema::getIndexes('projects'))
            ->contains(fn (array $index): bool => $index['name'] === 'projects_laravel_cloud_url_unique');
    }
};
