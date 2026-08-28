<?php

namespace App\Services\GitHub;

use App\Enums\TechnologyProvenance;
use App\Models\Project;
use App\Models\Technology;
use App\Services\GitHub\Exceptions\GitHubApiUnavailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Observes a project's declared stack from the public repository it
 * advertises: composer.json and package.json at the repository root,
 * matched against the curated vocabulary's observation keys.
 *
 * Observation is additive and never destructive to creator intent. A
 * creator declaration the repository stops supporting falls back to
 * declared; an observation the repository stops supporting disappears.
 * Verification and visibility are never touched — this is stack
 * evidence, not deployment evidence.
 */
final class StackObservationService
{
    public function __construct(private readonly RepoFileFetcher $files) {}

    /**
     * @throws GitHubApiUnavailable When GitHub is unreachable or rate-limits the read.
     */
    public function observe(Project $project): StackObservationResult
    {
        $repository = GitHubRepository::fromUrl($project->github_url);

        if ($repository === null) {
            return StackObservationResult::failed(StackObservationFailureReason::RepoUrlInvalid);
        }

        $composer = $this->files->fetch($repository, 'composer.json');

        if ($composer === null) {
            return StackObservationResult::failed(StackObservationFailureReason::ComposerJsonMissing);
        }

        $declared = self::decodeDependencies($composer);

        if ($declared === null) {
            return StackObservationResult::failed(StackObservationFailureReason::ComposerJsonInvalid);
        }

        $npm = self::decodeDependencies((string) $this->files->fetch($repository, 'package.json'));

        if ($npm !== null) {
            $declared = array_merge($declared, $npm);
        }

        $observed = $this->matchTechnologies($declared);

        $this->reconcile($project, $observed);

        return StackObservationResult::observed($observed->pluck('name')->values()->all());
    }

    /**
     * The dependency names and constraints a repository declares:
     * composer require and require-dev plus npm dependencies and
     * devDependencies. Runtime or dev tooling — either way the repo
     * declares the project uses it. An unreadable package.json is
     * ignored; composer.json is the authority for a Laravel project.
     *
     * @return array<string, string>|null
     */
    private static function decodeDependencies(string $json): ?array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return null;
        }

        $declared = [];

        foreach (['require', 'require-dev', 'dependencies', 'devDependencies'] as $section) {
            foreach ($decoded[$section] ?? [] as $name => $constraint) {
                if (is_string($name) && is_string($constraint)) {
                    $declared[$name] = $constraint;
                }
            }
        }

        return $declared;
    }

    /**
     * @param  array<string, string>  $declared
     * @return Collection<int, Technology>
     */
    private function matchTechnologies(array $declared): Collection
    {
        return Technology::query()
            ->whereNotNull('observation_keys')
            ->get()
            ->filter(fn (Technology $technology): bool => ObservationKeyMatcher::matches(
                (array) $technology->observation_keys,
                $declared,
            ))
            ->values();
    }

    /**
     * @param  Collection<int, Technology>  $observed
     */
    private function reconcile(Project $project, Collection $observed): void
    {
        DB::transaction(function () use ($project, $observed): void {
            $observedAt = Carbon::now();
            $observedIds = $observed->pluck('id')->all();
            $rows = $project->technologies->keyBy('id');

            // Demote or prune rows the evidence no longer supports. A
            // creator declaration survives on its own; an observation
            // without evidence disappears.
            foreach ($rows as $id => $technology) {
                if (in_array($id, $observedIds, true)) {
                    continue;
                }

                if ($technology->pivot->is_declared) {
                    $project->technologies()->updateExistingPivot($id, [
                        'provenance' => TechnologyProvenance::Declared->value,
                        'observed_at' => null,
                    ]);

                    continue;
                }

                $project->technologies()->detach($id);
            }

            foreach ($observed as $technology) {
                $project->technologies()->syncWithoutDetaching([$technology->getKey() => [
                    'provenance' => TechnologyProvenance::Observed->value,
                    'is_declared' => $rows[$technology->getKey()]?->pivot->is_declared ?? false,
                    'observed_at' => $observedAt,
                ]]);
            }
        });
    }
}
