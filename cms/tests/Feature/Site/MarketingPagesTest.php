<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The five pages ported from Claude Design's prototypes. These have no data of
 * their own, so what is worth guarding is that they still render, that nothing
 * half-finished escaped from the prototypes, and that every call to action goes
 * somewhere real — the designs shipped with placeholder copy and dead forms.
 */
class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            'home' => ['home'],
            'about' => ['about'],
            'campus' => ['campus'],
            'fees' => ['fees'],
            'partnerships' => ['partnerships'],
        ];
    }

    #[DataProvider('pages')]
    public function test_the_page_renders(string $name): void
    {
        $this->get(route($name))->assertOk();
    }

    #[DataProvider('pages')]
    public function test_no_prototype_placeholder_text_survived(string $name): void
    {
        $html = $this->get(route($name))->assertOk()->getContent();

        foreach (['Lorem Ipsum', 'lorem ipsum', 'you@example.com', 'Prototype only', 'prototype only', 'TODO', 'FIXME'] as $leftover) {
            $this->assertStringNotContainsString($leftover, $html, "{$name} still carries prototype text: {$leftover}");
        }
    }

    #[DataProvider('pages')]
    public function test_the_page_never_posts_a_form_that_goes_nowhere(string $name): void
    {
        $html = $this->get(route($name))->assertOk()->getContent();

        // There is no mail or CRM endpoint yet, so no public page may carry a form.
        // Every "register" and "enquiry" control is a mailto: instead.
        $this->assertStringNotContainsString('<form', $html, "{$name} has a form, but nothing can receive it");
    }

    public function test_register_and_enquiry_open_a_prefilled_email(): void
    {
        $admissions = config('cms.contact.admissions');
        $info = config('cms.contact.info');

        $this->get(route('home'))->assertOk()
            ->assertSee('mailto:'.$admissions, false)
            ->assertSee('Register%20my%20interest', false);

        $this->get(route('partnerships'))->assertOk()
            ->assertSee('mailto:'.$info, false)
            ->assertSee('Partnership%20enquiry', false);
    }

    public function test_the_fees_page_says_plainly_that_the_fees_are_not_confirmed(): void
    {
        // These are a sister school's rates standing in. A family must not be able
        // to read this page and think it is budgeting from a confirmed structure.
        $this->get(route('fees'))->assertOk()
            ->assertSee('These fees are not confirmed.')
            ->assertSee('has not yet been approved by the Ministry of Education')
            ->assertSee('Do not budget from it.');
    }

    public function test_the_fee_calculator_is_fed_the_same_numbers_as_the_table(): void
    {
        $html = $this->get(route('fees'))->assertOk()->getContent();

        preg_match('/<script type="application\/json" id="fee-data">(.*?)<\/script>/s', $html, $m);
        $this->assertNotEmpty($m, 'the calculator has no data block');
        $data = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);

        $this->assertSame([525, 633, 633, 633, 633], $data['oneTime']);
        $this->assertSame([2000, 2891, 3118, 3800, 4254], $data['instalment']);
        // Each band's yearly tuition must be exactly three installments.
        foreach ($data['instalment'] as $i => $perTerm) {
            $this->assertSame($perTerm * 3, $data['tuition'][$i], "band {$i} tuition is not three installments");
        }
    }

    public function test_the_campus_page_keeps_the_designs_own_caveats(): void
    {
        $this->get(route('campus'))->assertOk()
            ->assertSee('indicative only')
            ->assertSee('Al Wukair');

        // The construction milestones on the front page are invented placeholders;
        // the design said so and that warning must not be quietly dropped.
        $this->get(route('home'))->assertOk()
            ->assertSee('Placeholder schedule');
    }

    public function test_the_about_page_does_not_invent_people(): void
    {
        $this->get(route('about'))->assertOk()
            ->assertSee('No names yet.')
            ->assertSee('Not appointed');
    }

    #[DataProvider('pages')]
    public function test_the_page_is_described_for_search_and_sharing(string $name): void
    {
        $html = $this->get(route($name))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<title>.{10,}<\/title>/', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route($name).'">', $html);
        $this->assertMatchesRegularExpression('/<meta name="description" content=".{30,}">/', $html);
        $this->assertStringContainsString('property="og:title"', $html);
    }
}
