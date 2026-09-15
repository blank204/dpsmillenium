<?php

namespace Database\Seeders;

use App\Content\CalendarService;
use App\Content\PostService;
use App\Content\PublishOptions;
use App\Content\Section;
use App\Media\FileUploader;
use App\Models\Account;
use App\Models\CalendarType;
use App\Models\Grade;
use App\Models\NewsletterSeries;
use App\Models\Post;
use App\Support\Actor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class DemoSeeder extends Seeder
{
    private PostService $posts;
    private FileUploader $uploader;

    public function run(PostService $posts, CalendarService $calendar, FileUploader $uploader): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo data must never be loaded on the live website.');
        }

        $this->posts = $posts;
        $this->uploader = $uploader;

        Account::updateOrCreate(['username' => 'editor'], ['role' => Account::EDITOR, 'password' => 'demo-editor-pass']);
        Account::updateOrCreate(['username' => 'superadmin'], ['role' => Account::SUPER_ADMIN, 'password' => 'demo-super-pass']);

        $now = CarbonImmutable::now();
        $g = fn (string $label) => Grade::where('label', $label)->value('id');
        $priya = new Actor('Ms. Priya (demo)', null, 'editor', '127.0.0.1', 'Demo seeder', null);
        $khan = new Actor('Mr. Khan (demo)', null, 'editor', '127.0.0.1', 'Demo seeder', null);
        $text = fn (string $html) => ['type' => 'text', 'html' => $html];

        // Information corner
        $this->live(Section::Info, 'Welcome to DPS Millennium: admissions for 2027-28 open soon', [$text('<p>Registration details will be announced here first. Families can follow the campus build on the Campus page.</p>')], $priya, pin: $now->addDays(14));
        $this->live(Section::Info, 'World Mental Health Day photography activity', [$text('<p>A photography activity for students of <strong>Grades IX–XII</strong>.</p><ul><li>Choose one theme</li><li>Submit on A4</li></ul>'), ['type' => 'link', 'label' => 'Read the guidelines', 'url' => 'https://example.org/guidelines']], $khan, grades: ['mode' => 'range', 'from' => $g('IX'), 'to' => $g('XII')]);
        $this->live(Section::Info, 'Grade VII and VIII half yearly seating arrangement', [$text('<p>Find your room below.</p>'), ['type' => 'table', 'has_header' => true, 'paste' => "Class\tRoom\tRoll numbers\nVII A\t101\t1–32\nVII B\t102\t1–30\nVIII A\t201\t1–31"]], $priya, grades: ['mode' => 'specific', 'ids' => [$g('VII'), $g('VIII')]]);
        $this->live(Section::Info, 'Hackathon 2026: register today', [$text('<p>Teams of three. Registration closes Friday.</p>'), ['type' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'mode' => 'player']], $khan, removeOn: null);
        $hidden = $this->live(Section::Info, 'Summer camp registration (closed)', [$text('<p>Registration has closed.</p>')], $priya, removeOn: $now->addMinutes(1));
        Post::whereKey($hidden->id)->update(['remove_on' => $now->subDay()]);
        $this->posts->publish(new Post(['section' => Section::Info]), ['title' => 'Annual Day rehearsal schedule', 'blocks' => [$text('<p>Rehearsals start next week.</p>')]], new PublishOptions(publishAt: $now->addDays(2)->setTime(7, 0)), $priya, false);
        $this->posts->saveDraft(null, Section::Info, ['title' => 'Draft: parent orientation notes', 'blocks' => [$text('<p>To be completed.</p>')]], $khan, false);
        $deleted = $this->live(Section::Info, 'Posted by mistake', [$text('<p>Wrong notice.</p>')], $priya);
        $this->posts->delete($deleted, $priya);

        // Academic circulars
        foreach (['I', 'II', 'III', 'IV', 'V', 'VI'] as $i => $grade) {
            $this->live(Section::Circular, "Grade {$grade} Weekly Test I syllabus 2026-27", [
                $text('<p>Please find the syllabus attached.</p>'),
                ['type' => 'pdf', 'files' => [
                    ['media_id' => $this->pdf("Grade {$grade} Main Subjects.pdf"), 'label' => 'Main subjects'],
                    ['media_id' => $this->pdf("Grade {$grade} French 2L.pdf"), 'label' => 'French 2L'],
                ]],
            ], $i % 2 ? $khan : $priya, grades: ['mode' => 'specific', 'ids' => [$g($grade)]], at: $now->subDays(6 - $i));
        }

        // Events corner
        foreach (['Grade XI assembly on kindness', 'Teachers’ Day felicitation', 'Inter-house quiz final', 'Sports day heats'] as $i => $title) {
            $cover = $this->image("event-{$i}-cover.jpg", [30 + $i * 40, 90, 160]);
            $gallery = array_map(fn ($n) => ['media_id' => $this->image("event-{$i}-{$n}.jpg", [120, 40 + $n * 30, 90]), 'caption' => "Moment {$n}"], range(1, 4));
            $this->posts->publish(new Post(['section' => Section::Event]), [
                'title' => $title,
                'blocks' => [$text('<p>Students and staff came together for this event.</p>'), ['type' => 'gallery', 'items' => $gallery]],
                'cover_media_id' => $cover,
                'event_date_start' => $now->subDays(10 + $i)->format('Y-m-d'),
            ], new PublishOptions(), $khan, false);
        }

        // Newsletters
        $kg = NewsletterSeries::firstOrCreate(['name' => 'Kindergarten Newsletter']);
        $junior = NewsletterSeries::firstOrCreate(['name' => 'Junior School Newsletter']);
        foreach ([[$kg, 'Term I', true], [$kg, 'Term II', false], [$junior, 'Term I', true]] as $i => [$series, $term, $usePdf]) {
            $this->posts->publish(new Post(['section' => Section::Newsletter]), [
                'title' => "{$series->name}: {$term}",
                'blocks' => [],
                'cover_media_id' => $this->image("newsletter-{$i}.jpg", [200, 160, 40]),
                'newsletter_series_id' => $series->id,
                'newsletter_academic_year' => '2026-27',
                'newsletter_term' => $term,
                'newsletter_pdf_media_id' => $usePdf ? $this->pdf("newsletter-{$i}.pdf") : null,
                'newsletter_link' => $usePdf ? null : 'https://example.org/newsletter-flipbook',
            ], new PublishOptions(), $priya, false);
        }

        // Calendar
        $holiday = CalendarType::where('name', 'Holiday')->value('id');
        $exam = CalendarType::where('name', 'Exam')->value('id');
        $y = $now->year;
        foreach ([
            ['National Sport Day', $holiday, "{$y}-02-10", null],
            ['Half yearly examinations', $exam, $now->addDays(20)->format('Y-m-d'), $now->addDays(31)->format('Y-m-d')],
            ['Weekly Test I', $exam, $now->addDays(5)->format('Y-m-d'), null],
            ['Winter break', $holiday, "{$y}-12-24", ($y + 1).'-01-04'],
            ['Qatar National Day', $holiday, "{$y}-12-18", null],
            ['Final examinations', $exam, ($y + 1).'-03-01', ($y + 1).'-03-15'],
        ] as [$title, $type, $start, $end]) {
            $calendar->create(['title' => $title, 'calendar_type_id' => $type, 'start_date' => $start, 'end_date' => $end, 'grades' => ['mode' => 'all']], $khan);
        }
    }

    private function live(Section $section, string $title, array $blocks, Actor $actor, ?CarbonImmutable $pin = null, array $grades = ['mode' => 'none'], ?CarbonImmutable $removeOn = null, ?CarbonImmutable $at = null): Post
    {
        $post = $this->posts->publish(new Post(['section' => $section]), ['title' => $title, 'blocks' => $blocks, 'grades' => $grades], new PublishOptions(removeOn: $removeOn, pinnedUntil: $pin), $actor, false);
        if ($at !== null) {
            Post::whereKey($post->id)->update(['first_published_at' => $at, 'sort_at' => $at, 'publish_at' => $at]);
        }

        return $post->refresh();
    }

    private function image(string $name, array $rgb): int
    {
        $img = imagecreatetruecolor(1600, 1000);
        imagefill($img, 0, 0, imagecolorallocate($img, ...$rgb));
        imagestring($img, 5, 40, 40, 'DPS Millennium demo image: '.$name, imagecolorallocate($img, 255, 255, 255));
        $placeholder = tempnam(sys_get_temp_dir(), 'demo');
        $path = $placeholder.'.jpg';
        imagejpeg($img, $path, 85);

        try {
            return $this->uploader->upload(new UploadedFile($path, $name, 'image/jpeg', null, true), 'image')->id;
        } finally {
            imagedestroy($img);
            @unlink($placeholder);
            @unlink($path);
        }
    }

    private function pdf(string $name): int
    {
        $placeholder = tempnam(sys_get_temp_dir(), 'demo');
        $path = $placeholder.'.pdf';
        file_put_contents($path, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");

        try {
            return $this->uploader->upload(new UploadedFile($path, $name, 'application/pdf', null, true), 'pdf')->id;
        } finally {
            @unlink($placeholder);
            @unlink($path);
        }
    }
}
