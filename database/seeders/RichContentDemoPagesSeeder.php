<?php

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\Content;
use App\Models\Page;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds `Pages/Public/MembershipPage.vue` and the summer camps page as ordinary
 * content-block pages — the acceptance test for the rich-content block system: if it
 * can reproduce these two hand-built pages, it's expressive enough for general use.
 *
 * Deliberately NOT called from DatabaseSeeder::run() — this is a one-off demo, not
 * baseline dev data. Run it explicitly:
 *
 *   vendor/bin/sail artisan db:seed --class=RichContentDemoPagesSeeder
 *
 * Neither live Vue page is touched or replaced. These are new pages at their own
 * `demo-*` permalinks on the main ("pagrindinis") tenant, side by side with the real
 * ones, so they can be compared directly:
 *   /lt/demo-tapk-nariu            vs  /lt/tapk-nariu (MembershipPage.vue)
 *   /lt/demo-pirmakursiu-stovyklos vs  /lt/pirmakursiu-stovyklos (SummerCamps.vue)
 *
 * Known gaps — things the static block system has no equivalent for, so the source
 * text/behaviour was adapted rather than copied verbatim:
 *  - MembershipPage's live "data updated N minutes ago" pill's *specific wording* is
 *    computed client-side from a cache timestamp and re-renders on every visit; the
 *    visual pattern itself (a plain dot-tag + italic caption) is now expressible
 *    (`rcTag` mark, `plain`/`green`) and used below (part 3b) with static copy instead.
 *  - number-stat-section only stores static numbers, so the figures below are a
 *    plausible snapshot, not the live-queried figures.
 *  - SummerCamps' `isCurrentYear` copy switch and its live $tChoice camp/unit counts
 *    in the hero — the seeded hero always uses the "returning visitor" copy.
 *  - The FAQ (16 items) and photo gallery (16 images) are trimmed to a representative
 *    subset here — the accordion/gallery blocks handle either count identically, this
 *    is just about keeping the seeder itself readable.
 *
 * Now expressible (previously listed here as gaps, since fixed):
 *  - The mascot section's "Maskotė" / "Aktyvi VU SA narė nuo 2003 m." dot-pills are
 *    real `rcTag` marks (filled/plain, yellow), and its heading is a real `heading`
 *    node with a `size`/`align` attribute (`rc-h-md`, left-aligned) — previously both
 *    were just plain paragraphs, since the editor had no equivalent.
 *  - The mascot section's rounded, contrast-background full-bleed wrapper is a real
 *    `section` block (background: 'contrast', rounded: 'md'), not just the
 *    content-grid's own chrome — demonstrating a block wrapping others.
 *  - The overlay "Faktai / Fun Facts" card is contained within the image (not
 *    overhanging its corner) via `overlayCorner`, matching the source exactly.
 *  - SummerCamps' tenant group labels use `tenantLabelStyle: 'faculty'` (e.g.
 *    "VU Filologijos fakultetas"), matching `SummerCampCard`'s naming.
 */
class RichContentDemoPagesSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('type', 'pagrindinis')->firstOrFail();

        $this->seedPagePair(
            tenant: $tenant,
            permalinkLt: 'demo-tapk-nariu',
            permalinkEn: 'demo-become-a-member',
            titleLt: 'Tapk nariu (blokų demonstracija)',
            titleEn: 'Become a member (block demo)',
            partsLt: $this->membershipParts('lt'),
            partsEn: $this->membershipParts('en'),
        );

        $this->seedPagePair(
            tenant: $tenant,
            permalinkLt: 'demo-pirmakursiu-stovyklos',
            permalinkEn: 'demo-freshmen-camps',
            titleLt: 'Pirmakursių stovyklos (blokų demonstracija)',
            titleEn: 'Freshmen camps (block demo)',
            partsLt: $this->summerCampsParts('lt'),
            partsEn: $this->summerCampsParts('en'),
        );

        $this->command?->info('Seeded demo pages: /lt/demo-tapk-nariu, /lt/demo-pirmakursiu-stovyklos (and their /en/ pairs).');
    }

    /**
     * Creates (or replaces) a lt/en page pair sharing the same structure, paired via
     * `other_lang_id` the same way PageController::store() pairs real pages.
     */
    private function seedPagePair(Tenant $tenant, string $permalinkLt, string $permalinkEn, string $titleLt, string $titleEn, array $partsLt, array $partsEn): void
    {
        $this->deleteExisting($tenant, [$permalinkLt, $permalinkEn]);

        $pageLt = $this->makePage($tenant, $permalinkLt, 'lt', $titleLt, $partsLt);
        $pageEn = $this->makePage($tenant, $permalinkEn, 'en', $titleEn, $partsEn);

        $pageLt->update(['other_lang_id' => $pageEn->id]);
        $pageEn->update(['other_lang_id' => $pageLt->id]);
    }

    /**
     * Re-running the seeder replaces the previous demo pages rather than duplicating
     * them. Deleting the `contents` row is enough — both `content_parts.content_id`
     * and `pages.content_id` cascade on delete, so the parts and the page itself go
     * with it in one statement.
     */
    private function deleteExisting(Tenant $tenant, array $permalinks): void
    {
        $contentIds = Page::withTrashed()->where('tenant_id', $tenant->id)->whereIn('permalink', $permalinks)->pluck('content_id');

        DB::table('contents')->whereIn('id', $contentIds)->delete();
    }

    private function makePage(Tenant $tenant, string $permalink, string $lang, string $title, array $parts): Page
    {
        $content = Content::create();

        foreach ($parts as $order => $part) {
            $content->parts()->create([...$part, 'order' => $order]);
        }

        return Page::create([
            'title' => $title,
            'permalink' => $permalink,
            'lang' => $lang,
            'content_id' => $content->id,
            'tenant_id' => $tenant->id,
            'is_active' => true,
            // Both source pages are full-width, section-driven layouts with no ToC-worthy
            // long-form text — 'wide' + no sidebar matches them far better than 'default'.
            'layout' => 'wide',
            'show_table_of_contents' => false,
            // Each page opens with its own hero, which already carries a title —
            // the plain page <h1> above it would just be a duplicate.
            'show_title' => false,
        ]);
    }

    /** @return array<int, string> */
    private function tiptapParagraphs(array $paragraphs): array
    {
        return [
            'type' => 'doc',
            'content' => array_map(fn (string $text) => [
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $text]],
            ], $paragraphs),
        ];
    }

    /** A single paragraph containing one link, for the FAQ item that has one in the source. */
    private function tiptapParagraphWithLink(string $before, string $linkText, string $href, string $after): array
    {
        return [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => array_filter([
                    $before !== '' ? ['type' => 'text', 'text' => $before] : null,
                    ['type' => 'text', 'text' => $linkText, 'marks' => [['type' => 'link', 'attrs' => ['href' => $href]]]],
                    $after !== '' ? ['type' => 'text', 'text' => $after] : null,
                ]),
            ]],
        ];
    }

    /**
     * A paragraph wrapping its whole text in the `rcTag` mark (App\Tiptap\RCTag) —
     * the dot-pill "tag" seen throughout MembershipPage.vue, e.g. the "Maskotė" badge
     * (filled/yellow) and "Aktyvi VU SA narė nuo 2003 m." (plain/yellow). Previously
     * inexpressible in tiptap content; these were hand-coded page markup the block
     * system had no equivalent for.
     *
     * @return array<string, mixed>
     */
    private function tiptapTagNode(string $text, string $variant, string $color, ?string $align = null): array
    {
        return [
            'type' => 'paragraph',
            'attrs' => $align ? ['align' => $align] : null,
            'content' => [
                ['type' => 'text', 'text' => $text, 'marks' => [['type' => 'rcTag', 'attrs' => ['variant' => $variant, 'color' => $color]]]],
            ],
        ];
    }

    /**
     * A heading node with CustomHeading's `size`/`accent` attributes — reproduces the
     * mascot section's `text-xl sm:text-2xl md:text-3xl` heading (`rc-h-md`) at a
     * semantic h2, left-aligned to match the source's two-column (not centered)
     * layout. Previously inexpressible: the editor only offered a plain h2/h3 toggle
     * with no size/accent/alignment control.
     *
     * @return array<string, mixed>
     */
    private function tiptapHeadingNode(string $text, int $level = 2, ?string $size = null, ?string $accent = null, ?string $align = null): array
    {
        return [
            'type' => 'heading',
            'attrs' => array_filter([
                'level' => $level,
                'size' => $size,
                'accent' => $accent,
                'align' => $align,
            ], fn ($value) => $value !== null),
            'content' => [['type' => 'text', 'text' => $text]],
        ];
    }

    /** A plain paragraph node (not a full `doc`) — for composing heterogeneous tiptap docs by hand. */
    private function tiptapParagraphNode(string $text): array
    {
        return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
    }

    /**
     * MembershipPage.vue, block for block. Text is transcribed from the live page
     * (resources/js/Pages/Public/MembershipPage.vue) rather than paraphrased, so the
     * two read identically where the block system allows it.
     */
    private function membershipParts(string $locale): array
    {
        $lt = fn (string $lt, string $en) => $locale === 'lt' ? $lt : $en;
        $registrationHref = $locale === 'lt' ? '/registracija/nariu-registracija' : '/registration/member-registration';

        return [
            // 1. Hero — split, the page's own header.
            [
                'type' => 'hero',
                'json_content' => [
                    'title' => $lt('Prisijunk prie VU&nbsp;SA bendruomenės!', 'Join the VU&nbsp;SR community!'),
                    'description' => $lt(
                        'Atrask naujas galimybes, rask bendraminčių (-ių) ir darykime pokyčius kartu!',
                        'Discover new opportunities, find like-minded people, and let\'s make changes together!',
                    ),
                    'eyebrow' => '',
                    'imageSrc' => '/images/become-a-member/20250510_VUSA-156.webp',
                    'imageAlt' => 'VU SA students',
                    'objectPosition' => '50% 40%',
                    'overlayContent' => [
                        'title' => $lt('1500+ aktyvių narių', '1500+ active members'),
                        'subtitle' => $lt('Prisijunk prie bendruomenės šiandien', 'Join the community today'),
                    ],
                    'buttons' => [
                        ['text' => $lt('Tapk VU SA nariu (-e)', 'Become a VU SA member'), 'link' => $registrationHref, 'variant' => 'default', 'color' => 'zinc', 'icon' => 'user'],
                        ['text' => $lt('Sužinok daugiau', 'Learn more'), 'link' => '#why-join-section', 'variant' => 'outline', 'color' => 'white', 'icon' => 'chevron-down'],
                    ],
                ],
                'options' => ['variant' => 'split', 'textLeft' => true, 'imageDecorations' => []],
            ],

            // 2. Carousel — "Kodėl verta prisijungti ir ką gausi?"
            [
                'type' => 'carousel-slide-deck',
                'json_content' => [
                    [
                        'icon' => 'users', 'badge' => $lt('Bendruomenė', 'Community'),
                        'title' => $lt('Ar ieškai naujos aplinkos ir draugų?', 'Looking for a new environment and friends?'),
                        'description' => $this->tiptapParagraphs([$lt(
                            'Vilniaus universiteto Studentų atstovybė (VU SA) – tai bendruomenė, jungianti visus Universiteto fakultetus. Stiprioje, motyvuotoje aplinkoje rasi bendraminčių (-ių) bendruomenę ir draugus (-es), kurie (-ios) dalijasi panašiais tikslais ir vertybėmis.',
                            'Vilnius University Student Representation (VU SR) is a community that brings together all University faculties. In a strong, motivated environment, you will find a community of like-minded people and friends who share similar goals and values.',
                        )]),
                        'imageSrc' => '/images/become-a-member/mokymai2025-6.webp', 'imageAlt' => 'Students', 'imageLeft' => true,
                    ],
                    [
                        'icon' => 'trending-up', 'badge' => $lt('Poveikis', 'Impact'),
                        'title' => $lt('Ar nori palikti prasmingą pokytį?', 'Want to make a meaningful change?'),
                        'description' => $this->tiptapParagraphs([$lt(
                            'VU SA nariai (-ės) aktyviai formuoja Universiteto ateitį – dalyvauja sprendimų priėmime, inicijuoja pokyčius studijų kokybėje, infrastruktūroje ir studentų (-čių) gyvenime. Tavo idėjos gali paveikti tūkstančių studentų (-čių) kasdienybę.',
                            'VU SA members actively shape the future of the University - participate in decision-making, initiate changes in study quality, infrastructure and student life. Your ideas can affect the daily lives of thousands of students.',
                        )]),
                        'imageSrc' => '/images/become-a-member/VU SA 24-25-01.webp', 'imageAlt' => 'Student activities and engagement', 'imageLeft' => false,
                    ],
                    [
                        'icon' => 'book-open', 'badge' => $lt('Tobulėjimas', 'Growth'),
                        'title' => $lt('Ar sieki augti karjeroje ir kaip asmenybė?', 'Seeking to grow in career and as a person?'),
                        'description' => $this->tiptapParagraphs([$lt(
                            'Kartu su komanda augsi kaip asmenybė – atrasi savo stiprybes ir mėgstamas sritis. Galbūt atrasi patinkančią karjeros kryptį, o profesinį gyvenimą pradėsi su stipresniu CV ir vertingų įgūdžių bagažu.',
                            'Together with the team, you will grow as a person - discover your strengths and favorite areas. You might discover a career direction you like, and start your professional life with a stronger CV and a valuable skills arsenal.',
                        )]),
                        'imageSrc' => '/images/become-a-member/VU SA 24-25-06.webp', 'imageAlt' => 'Students developing skills', 'imageLeft' => true,
                    ],
                    [
                        'icon' => 'award', 'badge' => $lt('Mokymai', 'Training'),
                        'title' => $lt('Mokymai', 'Training'),
                        'description' => $this->tiptapParagraphs([$lt(
                            'Tapus nariu (-e) tavęs laukia studentų (-čių) atstovų (-ių), kuratorių (-ių) ir kiti mokymai, skirti įgyti vertingų žinių ir įgūdžių: nuo komandinio darbo iki Lietuvos aukštojo mokslo sistemos supratimo.',
                            'As a member, you will have access to student representative, curator and other training designed to gain valuable knowledge and skills: from teamwork to understanding the Lithuanian higher education system.',
                        )]),
                        'imageSrc' => '/images/become-a-member/mokymai2025-3.webp', 'imageAlt' => 'Student training and development', 'imageLeft' => true,
                    ],
                    [
                        'icon' => 'external-link', 'badge' => $lt('Diplomo priedėlis', 'Diploma Supplement'),
                        'title' => $lt('Priedas prie diplomo', 'Diploma supplement'),
                        'description' => $this->tiptapParagraphs([$lt(
                            'Aktyviai dalyvaudamas (-a) Vilniaus universiteto Studentų atstovybės veiklose gali gauti priedą prie savo diplomo! Šis dokumentas įvertins tavo savanorystės valandas ir įgytą patirtį – privalumas darbo rinkoje.',
                            'By actively participating in VU SA activities, you can get a supplement to your diploma! This document will evaluate your volunteer hours and gained experience - an advantage in the job market.',
                        )]),
                        'imageSrc' => '/images/become-a-member/diplomas.webp', 'imageAlt' => 'University diploma and achievements', 'imageLeft' => false,
                    ],
                    [
                        'icon' => 'dollar-sign', 'badge' => $lt('Stipendija', 'Scholarship'),
                        'title' => $lt('Stipendija už savanorystę', 'Volunteer scholarship'),
                        'description' => $this->tiptapParagraphs([$lt(
                            'Už aktyvią ir į rezultatą orientuotą savanorišką veiklą kiekvieną semestrą gali gauti VU visuomeninės veiklos stipendiją, įvertinančią tavo laiką ir pastangas, iniciatyvas bei pasiekimus.',
                            'For active and result-oriented volunteer work, each semester you can receive a VU public activity scholarship that evaluates your time, efforts, initiatives and achievements.',
                        )]),
                        'imageSrc' => '/images/become-a-member/mokymai2025-7.webp', 'imageAlt' => 'Student scholarship and financial support', 'imageLeft' => true,
                    ],
                ],
                'options' => [
                    'title' => $lt('Kodėl verta prisijungti ir ką gausi?', 'Why join and what will you get?'),
                    'subtitle' => $lt(
                        'Sužinok, kodėl verta prisijungti prie VU SA ir kokius privalumus suteiks narystė',
                        'Discover reasons to join VU SA and learn about the benefits of membership',
                    ),
                    'background' => 'none', 'padding' => 'md',
                    'autoplay' => true, 'autoplayDelay' => 8000, 'showNavigation' => true, 'showThumbnails' => true,
                ],
            ],

            // 3. Number stats — "VU SA skaičiais". Static snapshot: the live page computes
            // representative_bodies/student_representatives from the DB on every render.
            [
                'type' => 'number-stat-section',
                'json_content' => [
                    ['endNumber' => (int) now()->diffInYears(Carbon::create(1989, 11, 17)), 'label' => $lt('metų veikimo', 'years')],
                    ['endNumber' => 90, 'label' => $lt('atstovavimo organų', 'representative bodies'), 'showPlus' => true],
                    ['endNumber' => 130, 'label' => $lt('studentų atstovų (-ių)', 'student representatives'), 'showPlus' => true],
                    ['endNumber' => 1500, 'label' => $lt('narių (-ių)', 'members'), 'showPlus' => true],
                    ['endNumber' => 1, 'label' => $lt('vėžlė..?', 'turtle..?')],
                ],
                'options' => ['color' => 'zinc', 'title' => $lt('VU SA skaičiais', 'VU SA in numbers'), 'background' => 'none', 'padding' => 'md'],
            ],

            // 3b. The small "data updated" caption directly under the stats
            // (MembershipPage.vue:124-136) — a plain (no-background) green tag +
            // italic text. The live page's *specific* wording ("atnaujinta prieš N
            // minučių") stays a known gap (it's computed client-side from a cache
            // timestamp on every render); this reproduces the visual pattern with
            // static copy instead.
            [
                'type' => 'tiptap',
                'json_content' => [
                    'type' => 'doc',
                    'content' => [
                        $this->tiptapTagNode(
                            $lt('Duomenys atnaujinami reguliariai', 'Data updated regularly'),
                            'plain', 'green', 'center',
                        ),
                    ],
                ],
                'options' => ['width' => 'content'],
            ],

            // 4. "Susipažink su Lijana!" — wrapped in a `section` block (contrast
            // background, rounded — matches the source's `2xl:rounded-lg bg-white
            // dark:bg-zinc-950` full-bleed section, MembershipPage.vue:139), holding a
            // content-grid, 50/50 (matching the source's `lg:grid-cols-2`), text +
            // a decorated, overlaid image. `verticalAlign: 'center'` reproduces the
            // source's `items-center` (the shorter text column no longer stretches to
            // the taller image's height).
            [
                'type' => 'section',
                'json_content' => [],
                'options' => ['background' => 'contrast', 'rounded' => 'md'],
            ],
            [
                'type' => 'content-grid',
                'json_content' => [[
                    'columns' => [
                        [
                            'width' => 'col-span-6',
                            'content' => ['type' => 'tiptap', 'value' => [
                                'type' => 'doc',
                                'content' => [
                                    $this->tiptapTagNode($lt('Maskotė', 'Mascot'), 'filled', 'yellow', 'start'),
                                    $this->tiptapHeadingNode($lt('Susipažink su Lijana!', 'Meet Lijana!'), level: 2, size: 'md', align: 'start'),
                                    $this->tiptapParagraphNode($lt(
                                        'Mūsų vėžlė Lijana (vardą keičia beveik kasmet) yra ilgiausiai veikianti VU SA narė. Kaip ir jinai, mes tikime, kad nuoseklumas ir kantrybė lemia sėkmę – ne visada reikia skubėti, svarbiausia judėti teisinga kryptimi.',
                                        'Our turtle Lijana (the name changes almost every year) is the longest-serving VU SA member. Like her, we believe that consistency and patience determine success – you don\'t always need to rush, the important thing is to move in the right direction.',
                                    )),
                                    $this->tiptapParagraphNode($lt(
                                        'Lijana simbolizuoja mūsų bendruomenės vieningumą – ilgalaikį įsipareigojimą studentų(-čių) gerovei. Užeik susipažinti su ja VU SA Centriniame biure Observatorijos kieme, Universiteto g. 3!',
                                        'Lijana symbolizes our community\'s unity – long-term commitment to student welfare. Come visit her at the VU SA Central Office in the Observatory Yard, Universiteto st. 3!',
                                    )),
                                    $this->tiptapTagNode($lt('Aktyvi VU SA narė nuo 2003 m.', 'Active VU SA member since 2003'), 'plain', 'yellow', 'start'),
                                ],
                            ]],
                        ],
                        [
                            'width' => 'col-span-6',
                            'content' => [
                                'type' => 'image',
                                'value' => '/images/become-a-member/20250510_VUSA-144.webp',
                                'alt' => 'VU SA turtle mascot Lijana',
                                'objectPosition' => 'bottom right',
                                'overlayContent' => ['title' => $lt('Faktai', 'Fun Facts'), 'subtitle' => $lt('Mėgsta salotų lapus ir studentų (-čių) renginius', 'Loves lettuce leaves and student events')],
                                // Contained (not overhanging) — `overlayOverhang` defaults
                                // false, so the card stays fully inside the image's
                                // rounded corners rather than straddling the edge.
                                'overlayCorner' => 'bottom-left',
                                'decorations' => [['type' => 'line', 'position' => 'top-right', 'size' => 'lg', 'color' => 'vusa-yellow', 'opacity' => 60]],
                            ],
                        ],
                    ],
                ]],
                // No 'padding'/'background' here — the wrapping `section` above already
                // supplies both, and this content-grid used to *also* set padding:'lg',
                // doubling the section's own py-16 into 128px of top+bottom whitespace.
                'options' => ['padding' => 'none', 'gap' => 'gap-8', 'mobileStacking' => true, 'equalHeight' => false, 'verticalAlign' => 'center'],
            ],

            // 4b. Terminates the mascot `section` above — `wraps: 'none'` means it
            // absorbs nothing itself, which also means the *next* real section marker
            // (there isn't one here) would otherwise keep absorbing everything to the
            // end of the page. `padding`/`background: 'none'` (the defaults) make this
            // a zero-height, zero-visual-impact marker — see RichContentParser.vue's
            // `groupedContent` for the grouping rule this depends on.
            [
                'type' => 'section',
                'json_content' => [],
                'options' => ['wraps' => 'none', 'padding' => 'none', 'background' => 'none'],
            ],

            // 5. Card stack — "VU SA strateginės veiklos kryptys". Icons match the live
            // page's `activityCards` (MembershipPage.vue: BookOpen/Palette/TrendingUp) —
            // card-stack's icon support was previously ripped out mid-redesign (the type
            // still had a `@deprecated` marker and the display/editor never rendered or
            // exposed it, even though `cardIcons.ts`/`RCIcon.vue` were built for exactly
            // this); restored here rather than left broken.
            [
                'type' => 'card-stack',
                'json_content' => [
                    [
                        'icon' => 'book-open',
                        'title' => $lt('Kokybiškos studijos ir joms pritaikyta aplinka', 'Quality studies and adapted environment'),
                        'description' => $lt(
                            'Prisidėk prie personalizuotų studijų sąlygų kūrimo – studijų programų tobulinimo, dalykų pasirinkimo laisvės, tarpdiscipliniškumo ir tarptautinės patirties užtikrinimo.',
                            'Contribute to creating conditions for personalized studies – improving study programs, ensuring freedom of subject choice, interdisciplinarity and international experience.',
                        ),
                    ],
                    [
                        'icon' => 'palette',
                        'title' => $lt('Stipri organizacija', 'Strong organization'),
                        'description' => $lt(
                            'Prisidėk prie efektyvių organizacijos procesų kūrimo – padėk užtikrinti, kad visi studentai (-ės) galėtų dalyvauti saviraiškos ir atstovavimo veiklose.',
                            'Contribute to creating effective organizational processes – help ensure that all students can participate in self-expression and representation activities.',
                        ),
                    ],
                    [
                        'icon' => 'trending-up',
                        'title' => $lt('Darni universitetinė bendruomenė', 'Sustainable university community'),
                        'description' => $lt(
                            'Prisidėk prie vieningos ir iniciatyvios bendruomenės kūrimo – stiprink tarpusavio ryšius, skatink lyderystę ir aktyvų dalyvavimą.',
                            'Contribute to building a united and initiative community – strengthen mutual relationships, encourage leadership and active participation.',
                        ),
                    ],
                ],
                'options' => [
                    'title' => $lt('VU SA strateginės veiklos kryptys', 'VU SA strategic activity directions'),
                    'subtitle' => $lt(
                        'Mes veikiame trijose pagrindinėse strateginėse srityse, kurios formuoja universiteto bendruomenės ateitį',
                        'We operate in three main strategic areas that shape the future of the university community',
                    ),
                    'background' => 'muted', 'padding' => 'lg',
                    'autoplay' => true, 'autoplayDelay' => 5000, 'hintText' => $lt('Spustelėk kortelę arba indikatorių', 'Click card or indicator'),
                ],
            ],

            // 6. Photo gallery — "Mūsų veikla kadruose" (10 of the source's 16 images).
            [
                'type' => 'photo-gallery',
                'json_content' => [
                    ['src' => '/images/become-a-member/VU SA 24-25-09.webp', 'alt' => 'Student activities and engagement', 'heightClass' => 'h-40', 'decorations' => [['type' => 'line', 'position' => 'top-right', 'size' => 'sm', 'color' => 'vusa-yellow', 'opacity' => 50]]],
                    ['src' => '/images/become-a-member/VU SA 24-25-13.webp', 'alt' => 'Student collaboration and teamwork', 'heightClass' => 'h-52'],
                    ['src' => '/images/become-a-member/VU SA 24-25-18.webp', 'alt' => 'Student representation initiatives', 'heightClass' => 'h-40'],
                    ['src' => '/images/become-a-member/VU SA 24-25-23.webp', 'alt' => 'Student leadership development', 'heightClass' => 'h-52'],
                    ['src' => '/images/become-a-member/VU SA 24-25-11.webp', 'alt' => 'VU SA student representation activities', 'heightClass' => 'h-52'],
                    ['src' => '/images/become-a-member/VU SA 24-25-16.webp', 'alt' => 'Student engagement activities', 'heightClass' => 'h-40', 'decorations' => [['type' => 'square', 'position' => 'bottom-left', 'size' => 'sm', 'color' => 'vusa-red', 'opacity' => 40, 'rotation' => true]]],
                    ['src' => '/images/become-a-member/VU SA 24-25-19.webp', 'alt' => 'Student community building', 'heightClass' => 'h-52'],
                    ['src' => '/images/become-a-member/Varsuva.webp', 'alt' => 'Academic ethics research', 'heightClass' => 'h-40'],
                    ['src' => '/images/become-a-member/mokymai2025-3.webp', 'alt' => 'Student training workshop', 'heightClass' => 'h-36'],
                    ['src' => '/images/become-a-member/20250510_VUSA-88.webp', 'alt' => 'VU SA community activities', 'heightClass' => 'h-36'],
                ],
                'options' => [
                    'title' => $lt('Mūsų veikla kadruose', 'Our activities in frames'),
                    'subtitle' => $lt('Pažvelk į mūsų bendruomenės kasdienybę ir renginius', 'Take a look at our community\'s daily life and events'),
                    'background' => 'none', 'padding' => 'md',
                    'columns' => '4', 'gap' => 'medium', 'showLightbox' => true,
                ],
            ],

            // 7. Hero — centered, the call-to-action section.
            [
                'type' => 'hero',
                'json_content' => [
                    'title' => $lt(
                        'Vilniaus universiteto Studentų atstovybė laukia tavęs – veikime, kurkime ir tobulėkime kartu!',
                        'Vilnius University Student Representation is waiting for you - let\'s work, create and grow together!',
                    ),
                    'description' => '', 'eyebrow' => '', 'imageSrc' => '', 'imageAlt' => '',
                    'buttons' => [
                        ['text' => $lt('Tapk nariu (-e)', 'Become a member'), 'link' => $registrationHref, 'variant' => 'default', 'color' => 'zinc', 'icon' => 'user'],
                    ],
                ],
                'options' => ['variant' => 'centered', 'background' => 'none', 'padding' => 'lg'],
            ],

            // 8. FAQ — accordion (8 of the source's 16 questions). Item 7 keeps the
            // source's embedded link, to demonstrate tiptap link marks round-tripping.
            [
                'type' => 'shadcn-accordion',
                'json_content' => [
                    ['label' => $lt('Kaip galiu tapti VU SA nariu (-e)?', 'How can I become a VU SA member?'), 'content' => $this->tiptapParagraphs([$lt(
                        'Tapti VU SA nariu (-e) galėsi užpildydamas (-a) narystės registracijos formą mūsų svetainėje arba apsilankydamas (-a) VU SA padalinyje. Narystė atvira visiems įstojusiems Vilniaus universiteto studentams (-ėms)!',
                        'You can become a VU SA member by filling out the membership registration form on our website or visiting a VU SA unit at one of the University divisions. Membership is open to all enrolled Vilnius University students!',
                    )])],
                    ['label' => $lt('Koks skirtumas tarp VU SA nario (-ės) ir studentų atstovo (-ės)?', 'What is the difference between a VU SA member and a student representative?'), 'content' => $this->tiptapParagraphs([$lt(
                        'VU SA nariai (-ės) kviečiami (-os) dalyvauti visuose renginiuose ir veiklose. Studentų atstovas (-ė) – tai narys (-ė), kurį (-ią) bendruomenė išrenka atstovauti studentams (-ėms) valdymo organuose.',
                        'VU SA members are invited to participate in all events and activities. A student representative is a member elected by the community to represent students in governance bodies.',
                    )])],
                    ['label' => $lt('Ar narystė mokama?', 'Is membership paid?'), 'content' => $this->tiptapParagraphs([$lt(
                        'Ne, narystė visiškai nemokama! VU SA finansuojama iš universiteto biudžeto ir kitų šaltinių, todėl nereikia mokėti jokių mokesčių ar įnašų.',
                        'No, membership is completely free! VU SA is funded from the university budget and other sources, so you don\'t need to pay any fees or contributions.',
                    )])],
                    ['label' => $lt('Kiek savo laiko turėčiau skirti VU SA veiklai?', 'How much of my time should I dedicate to VU SA activities?'), 'content' => $this->tiptapParagraphs([$lt(
                        'Viskas priklauso nuo tavęs - gali dalyvauti tik renginiuose arba aktyviai įsitraukti į projektus. Niekas tavęs nespaudžia – pats pasirenki pagal savo galimybes ir norą.',
                        'It all depends on you – you can just participate in events or actively engage in projects. No one is pressuring you – you choose according to your capabilities and desire.',
                    )])],
                    ['label' => $lt('Kokias kompetencijas galėčiau įgyti?', 'What competencies could I develop?'), 'content' => $this->tiptapParagraphs([$lt(
                        'VU SA veikloje lavinsi lyderystės, komandinio darbo, komunikacijos, projektų valdymo, viešojo kalbėjimo ir derybų įgūdžius.',
                        'In VU SA activities you\'ll develop leadership, teamwork, communication, project management, public speaking and negotiation skills.',
                    )])],
                    ['label' => $lt('Ką daro studentų atstovai (-ės)?', 'What do student representatives do?'), 'content' => $this->tiptapParagraphs([$lt(
                        'Studentų atstovai (-ės) dalyvauja universiteto valdymo organuose, gina studentų (-čių) interesus ir formuoja studijų politiką.',
                        'Student representatives participate in university governance bodies, defend student interests and shape study policies.',
                    )])],
                    ['label' => $lt('Ar turėsiu žodį sprendimų priėmime?', 'Will I have a say in decision-making?'), 'content' => $this->tiptapParagraphWithLink(
                        $lt(
                            'Taip! VU SA sprendimai priimami demokratiškai – per balsavimus Taryboje, Parlamente ir ataskaitinėse-rinkiminėse konferencijose. ',
                            'Yes! VU SA decisions are made democratically through voting in the Council, Parliament and annual conferences. ',
                        ),
                        $lt('Sužinok daugiau apie VU SA struktūrą', 'Learn more about VU SA structure'),
                        '/vu-sa-struktura',
                        '.',
                    )],
                    ['label' => $lt('Ką daryti, jei turiu klausimų ar problemų?', 'What to do if I have questions or problems?'), 'content' => $this->tiptapParagraphs([$lt(
                        'Drąsiai kreipkis! Gali rašyti bet kuriam (-iai) VU SA nariui (-ei), siųsti el. laišką savo padalinio komandai arba ateiti į mūsų biurą universiteto miestelyje.',
                        'Feel free to reach out! You can contact any VU SA member, send an email to your unit\'s team, or come to our office in the university town.',
                    )])],
                ],
                'options' => ['title' => $lt('Dažnai užduodami klausimai', 'What do you ask us?'), 'background' => 'muted', 'padding' => 'lg'],
            ],

            // 9. Hero — banner, the closing slogan (no button, matching the source).
            [
                'type' => 'hero',
                'json_content' => [
                    'title' => $lt('Vieningai Už Studentų ir Studenčių Ateitį!', 'United for the Future of Students!'),
                    'description' => '', 'eyebrow' => '', 'imageSrc' => '', 'imageAlt' => '', 'buttons' => [],
                ],
                'options' => ['variant' => 'banner', 'background' => 'none', 'padding' => 'md'],
            ],
        ];
    }

    /**
     * The summer camps page, block for block. `event-list`'s `year` is pinned to the
     * most recent year with `freshmen-camps` events in this database — the block has
     * no equivalent of the live page's "no events this year → redirect to latest"
     * fallback, so defaulting to the *current* calendar year could show an empty
     * state if next year's camps aren't scheduled yet.
     */
    private function summerCampsParts(string $locale): array
    {
        $lt = fn (string $lt, string $en) => $locale === 'lt' ? $lt : $en;

        $latestYear = (int) (Calendar::query()
            ->whereHas('category', fn ($q) => $q->withTrashed()->where('alias', 'freshmen-camps'))
            ->selectRaw('MAX(YEAR(date)) as yr')
            ->value('yr') ?? now()->year);

        $archiveYears = Calendar::query()
            ->whereHas('category', fn ($q) => $q->withTrashed()->where('alias', 'freshmen-camps'))
            ->selectRaw('DISTINCT YEAR(date) as yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->all();

        return [
            // 1. Hero — panel, matching the SummerCamps.vue header exactly.
            [
                'type' => 'hero',
                'json_content' => [
                    'title' => $lt('Pirmakursių stovyklos', 'Freshmen camps'),
                    'description' => $lt(
                        'Įstojai į Vilniaus universitetą? Nepraleisk pirmojo studentiško nuotykio – pirmakursių stovyklos!',
                        'Enrolled at Vilnius University? Don\'t miss your first student adventure – the freshmen camps!',
                    ),
                    'eyebrow' => $lt('Vilniaus universiteto Studentų atstovybės organizuojamos', 'Organized by Vilnius University Student Representation'),
                    'imageSrc' => '/images/photos/stovykla.jpg', 'imageAlt' => '', 'buttons' => [],
                ],
                'options' => ['variant' => 'panel'],
            ],

            // 2. Event list — "Stovyklos pagal padalinius", grouped by tenant (the
            // generalization of SummerCampCard/campsByTenant), pinned to $latestYear.
            [
                'type' => 'event-list',
                'json_content' => [],
                'options' => [
                    'title' => $lt('Stovyklos pagal padalinius', 'Camps by unit'),
                    'mode' => 'year', 'year' => $latestYear,
                    'categoryAlias' => 'freshmen-camps', 'tenantScope' => 'all',
                    'groupBy' => 'tenant', 'style' => 'cards', 'limit' => 24,
                    // 'faculty' derives "VU <nominative faculty>" (e.g.
                    // "VU Filologijos fakultetas") from the locative tenant fullname —
                    // see EventListResolver::facultyLabel. The previous 'short' style
                    // showed the abbreviated shortname_vu ("VU EVAF").
                    'tenantLabelStyle' => 'faculty',
                    'emptyMessage' => $lt('Šiais metais stovyklų informacija dar neskelbiama.', 'Camp information for this year has not been announced yet.'),
                    'background' => 'none', 'padding' => 'md',
                ],
            ],

            // 3. Content grid — "Daugiau apie stovyklas": 60/40 text + merch/info card,
            // matching the source's `lg:grid-cols-5` (col-span-3 / col-span-2 of 5 ≈ 60/40).
            [
                'type' => 'content-grid',
                'json_content' => [[
                    'columns' => [
                        [
                            'width' => 'col-span-7',
                            'content' => ['type' => 'tiptap', 'value' => $this->tiptapParagraphs([
                                $lt(
                                    'Pirmakursių stovyklos - tai ilgametes tradicijas turintis Vilniaus universiteto studentų atstovybės organizuojamas renginys VU pirmakursiams (-ėms), kuris vyksta kiekvienais metais.',
                                    'Freshmen camps are a long-standing tradition organized by the Vilnius University Student Representation for VU freshmen, held every year.',
                                ),
                                $lt(
                                    'Dar prieš tai, Vilniaus universiteto Studentų atstovybė (VU SA) kviečia Tave susipažinti su tais (-omis), kurie (-ios) per visus mokslo metus lydės daugiausiai – tai Tavo padalinio kuratoriai (-ės).',
                                    'Even before that, the Vilnius University Student Representation (VU SA) invites you to get to know those who will accompany you the most throughout the academic year – your unit\'s curators.',
                                ),
                                $lt(
                                    'Tai puiki galimybė ne tik praplėsti pažinčių ratą, bet ir gauti atsakymus į visus rūpimus klausimus, susijusius su studijomis ar studentišku gyvenimu – iš pirmų lūpų, o ne iš nuogirdų.',
                                    'This is a great opportunity to not only expand your circle of acquaintances, but also get answers to all your questions about studies or student life – firsthand, not from hearsay.',
                                ),
                            ])],
                        ],
                        [
                            'width' => 'col-span-5',
                            'content' => [
                                'type' => 'card',
                                'value' => [
                                    'image' => '/images/photos/atributika_banner3.jpg',
                                    'imageAlt' => 'VU merchandise and accessories banner',
                                    'title' => $lt('Bilietų prekyba ir tikslesnė informacija', 'Ticket sales and more information'),
                                    'description' => $lt(
                                        'Bilietų prekyba ir tikslesnė informacija bus paskelbta vėliau! Į kainą įskaičiuotas transportas į ir iš stovyklos.',
                                        'Ticket sales and more detailed information will be announced later! Transport to and from the camp is included in the price.',
                                    ),
                                    'href' => 'https://vu.lt/parduotuve/',
                                ],
                            ],
                        ],
                    ],
                ]],
                'options' => [
                    'title' => $lt('Daugiau apie stovyklas', 'More about the camps'),
                    'background' => 'none', 'padding' => 'md', 'gap' => 'gap-8', 'mobileStacking' => true, 'equalHeight' => false,
                ],
            ],

            // 4. Link list — "Kitų metų stovyklos" (the year archive), manual links to
            // every year that actually has freshmen-camps events in this database.
            [
                'type' => 'link-list',
                'json_content' => [
                    'links' => collect($archiveYears)->map(fn (int $year) => [
                        'title' => $lt("{$year} m. pirmakursių stovyklos", "Freshmen camps {$year}"),
                        'url' => route('pirmakursiuStovyklos', ['lang' => $locale, 'year' => $year]),
                    ])->all(),
                ],
                'options' => [
                    'title' => $lt('Kitų metų stovyklos', 'Other years\' camps'),
                    'source' => 'manual', 'style' => 'compact',
                    'background' => 'none', 'padding' => 'md',
                ],
            ],
        ];
    }
}
