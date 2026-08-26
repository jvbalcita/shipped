<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Project;
use App\Models\Release;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoLaunchSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The Shipped Studio Demo Launches. Local and testing only —
     * DatabaseSeeder never calls this in production.
     */
    public function run(): void
    {
        $studio = User::query()->firstOrCreate(['email' => 'studio@shipped.test'], ['name' => 'Shipped Studio', 'username' => 'shipped_studio', 'password' => Hash::make('password')]);

        collect([
            ['name' => 'Northstar', 'tagline' => 'A calmer home for open-source maintainers.', 'category' => 'Developer Tool', 'title' => 'Issue triage that respects your attention.', 'cover' => 'project-covers/northstar.svg', 'stack' => ['Laravel 13', 'PHP 8.5', 'Livewire', 'PostgreSQL', 'Redis']],
            ['name' => 'Field Notes', 'tagline' => 'Shared research, without the meeting theatre.', 'category' => 'SaaS', 'title' => 'Research spaces for teams that write.', 'cover' => 'project-covers/field-notes.svg', 'stack' => ['Laravel 13', 'PHP 8.4', 'Vue', 'MySQL', 'Filament', 'Cashier']],
            ['name' => 'Little Atlas', 'tagline' => 'A tiny geography game for curious people.', 'category' => 'Game', 'title' => 'The first hundred places are live.', 'cover' => 'project-covers/little-atlas.svg', 'stack' => ['Laravel 12', 'PHP 8.4', 'Blade', 'SQLite', 'Tailwind CSS']],
        ])->each(function (array $launch) use ($studio): void {
            $category = Category::query()->where('name', $launch['category'])->firstOrFail();
            $project = Project::query()->updateOrCreate(['slug' => str($launch['name'])->slug()], [
                'user_id' => $studio->id, 'category_id' => $category->id, 'name' => $launch['name'],
                'tagline' => $launch['tagline'], 'description' => 'A fictional Demo Launch by Shipped Studio, included to show the community launch experience before real projects arrive.',
                'is_public' => true, 'is_demo' => true, 'verification_status' => 'unverified', 'cover_image_path' => $launch['cover'],
            ]);
            Release::query()->firstOrCreate(['project_id' => $project->id], ['title' => $launch['title'], 'notes' => 'This is a Demo Launch. It shows how a project can introduce what changed, why it matters, and where people can try it.', 'published_at' => now()->subDay()]);

            $technologyIds = Technology::query()
                ->whereIn('name', $launch['stack'])
                ->pluck('id');

            $project->technologies()->sync($technologyIds);
        });
    }
}
