<?php

namespace Database\Factories;

use App\Content\AcademicYear;
use App\Content\PostStatus;
use App\Content\Section;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = rtrim(fake()->sentence(6), '.');

        return [
            'section' => Section::Info,
            'title' => $title,
            'slug' => Str::slug($title),
            'blocks' => [['type' => 'text', 'version' => 1, 'html' => '<p>'.fake()->paragraph().'</p>']],
            'status' => PostStatus::Draft,
        ];
    }

    public function section(Section $section): static
    {
        return $this->state(['section' => $section]);
    }

    public function draft(): static
    {
        return $this->state(['status' => PostStatus::Draft, 'publish_at' => null, 'first_published_at' => null, 'sort_at' => null]);
    }

    public function published(?CarbonInterface $at = null): static
    {
        $at = CarbonImmutable::instance($at ?? now());

        return $this->state([
            'status' => PostStatus::Published,
            'publish_at' => $at,
            'first_published_at' => $at,
            'sort_at' => $at,
            'academic_year' => AcademicYear::forDate($at, 4),
        ]);
    }

    public function scheduled(CarbonInterface $at): static
    {
        $at = CarbonImmutable::instance($at);

        return $this->state([
            'status' => PostStatus::Scheduled,
            'publish_at' => $at,
            'first_published_at' => $at,
            'sort_at' => $at,
            'academic_year' => AcademicYear::forDate($at, 4),
        ]);
    }
}
