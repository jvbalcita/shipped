<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ship_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->text('problem')->nullable();
            $table->text('audience')->nullable();
            $table->text('shipped')->nullable();
            $table->text('build_decisions')->nullable();
            $table->text('hardest_problem')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->text('next')->nullable();
            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamps();

            $table->unique('project_id', 'ship_stories_project_id_unique');
        });

        $compactText = static fn (mixed $value): string => Str::limit(
            preg_replace('/\s+/', ' ', trim(strip_tags((string) $value))) ?? '',
            2000,
            '',
        );

        DB::table('projects')
            ->select(['id', 'description'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $projects) use ($compactText): void {
                $projectIds = $projects->pluck('id');
                $releaseNotes = DB::table('releases')
                    ->whereIn('project_id', $projectIds)
                    ->whereNotNull('published_at')
                    ->orderBy('published_at')
                    ->orderBy('id')
                    ->get(['project_id', 'notes'])
                    ->groupBy('project_id')
                    ->map(fn (Collection $releases): string => (string) $releases->first()->notes);
                $timestamp = now();

                $rows = $projects->map(fn (object $project): array => [
                    'project_id' => $project->id,
                    'problem' => $compactText($project->description),
                    'audience' => '',
                    'shipped' => $compactText($releaseNotes->get($project->id)),
                    'build_decisions' => '',
                    'hardest_problem' => '',
                    'lessons_learned' => '',
                    'next' => null,
                    'approved_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all();

                if ($rows !== []) {
                    DB::table('ship_stories')->insert($rows);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_stories');
    }
};
