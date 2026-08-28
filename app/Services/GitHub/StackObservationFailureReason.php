<?php

namespace App\Services\GitHub;

enum StackObservationFailureReason: string
{
    case RepoUrlInvalid = 'repo_url_invalid';
    case RepoUnreachable = 'repo_unreachable';
    case ComposerJsonMissing = 'composer_json_missing';
    case ComposerJsonInvalid = 'composer_json_invalid';

    public function label(): string
    {
        return match ($this) {
            self::RepoUrlInvalid => 'The GitHub URL does not point at a repository.',
            self::RepoUnreachable => 'The repository could not be read. Make sure it exists and is public.',
            self::ComposerJsonMissing => 'No composer.json was found at the repository root.',
            self::ComposerJsonInvalid => 'The composer.json in the repository could not be read.',
        };
    }
}
