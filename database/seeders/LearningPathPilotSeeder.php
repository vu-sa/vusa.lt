<?php

namespace Database\Seeders;

use App\Enums\KnowledgeItemKind;
use App\Enums\KnowledgeSectionKind;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\LearningStep;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningPathPilotSeeder extends Seeder
{
    private const string SPK_TYPE = 'spk';

    private const string SPK_REGULATIONS_EN = 'https://www.kf.vu.lt/dokumentai/documents/2024/STUDIJ%C5%B2_PROGRAMOS_KOMITETO_NUOSTATAI_-_EN_Translation_STUDIJU%CC%A8_PROGRAMOS_KOMITETO_NUOSTATAI_SENATUI_po_StK.pdf';

    /** @var array<string, KnowledgeSection> */
    private array $sections = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedSections();
            foreach ([...$this->foundationSteps(), ...$this->spkSteps()] as $definition) {
                $this->upsertStep($definition);
            }
            $this->seedGlossary();
            $this->seedUnitRegulations();
        });
    }

    /** Topics group terms by field; collections group material. */
    private function seedSections(): void
    {
        $definitions = [
            'vusa' => [KnowledgeSectionKind::Topic, ['lt' => 'VU SA ir universitetas', 'en' => 'VU SR and the university'], ['lt' => 'Kaip valdoma VU SA ir universitetas ir kokie dokumentai tai nustato.', 'en' => 'How VU SR and the university are governed and which documents set it out.']],
            'studies' => [KnowledgeSectionKind::Topic, ['lt' => 'Studijos ir jų kokybė', 'en' => 'Studies and their quality'], ['lt' => 'Studijų programos, jų aprašai ir vertinimas.', 'en' => 'Study programmes, their descriptions and evaluation.']],
            'meetings' => [KnowledgeSectionKind::Topic, ['lt' => 'Posėdžiai ir sprendimai', 'en' => 'Meetings and decisions'], ['lt' => 'Kaip vyksta posėdžiai ir priimami sprendimai.', 'en' => 'How meetings run and decisions are made.']],
            'unit_regulations' => [KnowledgeSectionKind::Collection, ['lt' => 'Padalinių nuostatai', 'en' => 'Unit regulations'], ['lt' => 'Kiekvieno VU SA padalinio nuostatai.', 'en' => 'The regulations of each VU SR unit.']],
            'examples' => [KnowledgeSectionKind::Collection, ['lt' => 'Pavyzdžiai ir šablonai', 'en' => 'Examples and templates'], ['lt' => 'Pavyzdžiai, kuriais gali remtis savo darbe.', 'en' => 'Examples you can build on in your work.']],
        ];
        $order = 0;
        foreach ($definitions as $key => [$kind, $title, $description]) {
            $this->sections[$key] = KnowledgeSection::query()->updateOrCreate(['title->lt' => $title['lt']], [
                'kind' => $kind, 'title' => $title, 'description' => $description, 'order' => $order++,
            ]);
        }
    }

    /** @return list<array<string, mixed>> */
    private function foundationSteps(): array
    {
        return [
            [
                'key' => 'pilot_vusa_statute', 'section' => 'vusa',
                'kind' => 'knowledge', 'audience' => LearningAudienceType::Purpose,
                'stage' => null, 'importance' => LearningImportance::Essential, 'order' => 10,
                'title' => ['lt' => 'VU SA įstatai', 'en' => 'VU SR statute'],
                'description' => [
                    'lt' => 'Pagrindinis VU SA dokumentas: kas yra Studentų atstovybė, kokie jos organai ir kaip jie renkami. Iš čia kyla ir tavo, kaip atstovo, įgaliojimai.',
                    'en' => 'The core VU SR document: what the Students\' Representation is, its bodies and how they are elected. Your mandate as a representative comes from here.',
                ],
                'document' => 'VU SA Įstatai (nuo 2025 m.)',
            ],
            [
                'key' => 'pilot_vusa_rules_of_procedure', 'section' => 'vusa',
                'kind' => 'knowledge', 'audience' => LearningAudienceType::Purpose,
                'stage' => null, 'importance' => LearningImportance::Recommended, 'order' => 20,
                'title' => ['lt' => 'VU SA darbo reglamentas', 'en' => 'VU SR rules of procedure'],
                'description' => [
                    'lt' => 'Kaip VU SA priima sprendimus, rengia posėdžius ir deleguoja atstovus. Verta žinoti, kai teiksi klausimą ar ieškosi, kas už ką atsakingas.',
                    'en' => 'How VU SR makes decisions, holds meetings and delegates representatives. Useful when you raise an issue or look for who is responsible for what.',
                ],
                'document' => 'VU SA Darbo reglamentas (nuo 2025 m.)',
            ],
            [
                'key' => 'pilot_vu_statute', 'section' => 'vusa',
                'kind' => 'knowledge', 'audience' => LearningAudienceType::Purpose,
                'stage' => null, 'importance' => LearningImportance::Recommended, 'order' => 30,
                'title' => ['lt' => 'Vilniaus universiteto statutas', 'en' => 'Statute of Vilnius University'],
                'description' => [
                    'lt' => 'Kaip valdomas universitetas: Senatas, Taryba, rektorius, padaliniai ir kur juose dalyvauja studentų atstovai. Padeda suprasti, kur priimamas sprendimas, apie kurį kalbi.',
                    'en' => 'How the university is governed: the Senate, the Council, the Rector, units, and where student representatives take part. Helps you see where a decision is actually made.',
                ],
                'url' => 'https://e-seimas.lrs.lt/portal/legalAct/lt/TAD/877eb330dcd111e3b272e0e81c552d38',
            ],
            [
                'key' => 'pilot_law_on_higher_education', 'section' => 'vusa',
                'kind' => 'knowledge', 'audience' => LearningAudienceType::Purpose,
                'stage' => null, 'importance' => LearningImportance::Optional, 'order' => 40,
                'title' => ['lt' => 'Mokslo ir studijų įstatymas', 'en' => 'Law on Higher Education and Research'],
                'description' => [
                    'lt' => 'Valstybės lygio taisyklės: studentų teisės, studijų kokybė, atstovybių vaidmuo. Atsiversk, kai universiteto dokumentas remiasi įstatymu ar kai klausimas peržengia universiteto ribas.',
                    'en' => 'National rules: student rights, study quality and the role of student representations. Open it when a university document relies on the law or an issue goes beyond the university.',
                ],
                'url' => 'https://e-seimas.lrs.lt/portal/legalAct/lt/TAD/TAIS.343430',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function spkSteps(): array
    {
        $type = ['audience' => LearningAudienceType::InstitutionType, 'institution_type' => self::SPK_TYPE];

        return [
            [
                ...$type, 'key' => 'pilot_spk_regulations', 'section' => 'studies',
                'kind' => 'knowledge', 'stage' => null,
                'importance' => LearningImportance::Essential, 'order' => 10,
                'title' => ['lt' => 'Studijų programos komiteto nuostatai', 'en' => 'Regulations of the study programme committee'],
                'summary' => ['lt' => 'Ką daro studijų programos komitetas ir kaip jis dirba.', 'en' => 'What the study programme committee does and how it works.'],
                'description' => [
                    'lt' => 'Ką komitetas daro ir kaip dirba: bent vienas narys – VU SA deleguotas atstovas, posėdžiai vyksta ne rečiau kaip kartą per semestrą, medžiaga turi būti išsiųsta ne vėliau kaip prieš 3 darbo dienas, sprendimai priimami paprasta balsų dauguma, o slaptas balsavimas vyksta, jei to prašo bent vienas narys.',
                    'en' => 'What the committee does and how it works: at least one member is delegated by VU SR, it meets at least once a semester, materials must arrive at least 3 working days before, decisions pass by simple majority, and a secret vote is held if any member asks for it.',
                ],
                'sources' => ['en' => ['url' => self::SPK_REGULATIONS_EN]],
            ],
            [
                ...$type, 'key' => 'pilot_spk_programme',
                'kind' => LearningStepKind::Action, 'stage' => LearningStage::BeforeStart,
                'importance' => LearningImportance::Essential, 'order' => 20,
                'title' => ['lt' => 'Susipažink su savo studijų programa', 'en' => 'Get to know your study programme'],
                'description' => [
                    'lt' => 'Atsiversk programos aprašą ir studijų planą (fakulteto svetainėje ar VU studijų programų paieškoje): kokie studijų rezultatai, kokie dalykai, kiek kreditų. Komitetas svarsto būtent šiuos dalykus.',
                    'en' => 'Open the programme description and study plan (on the faculty website or the VU programme search): learning outcomes, courses and credits. This is what the committee discusses.',
                ],
            ],
            [
                ...$type, 'key' => 'pilot_spk_previous_minutes',
                'kind' => LearningStepKind::Action, 'stage' => LearningStage::FirstMonths,
                'importance' => LearningImportance::Recommended, 'order' => 30,
                'title' => ['lt' => 'Perskaityk praeitų posėdžių protokolus', 'en' => 'Read the minutes of past meetings'],
                'description' => [
                    'lt' => 'Platformoje atsiversk savo komiteto posėdžius ir peržiūrėk bent du paskutinius protokolus. Pamatysi, kokie klausimai liko neužbaigti ir kas pažadėta studentams.',
                    'en' => 'Open your committee\'s meetings on the platform and read at least the last two sets of minutes. You will see which issues are still open and what was promised to students.',
                ],
            ],
            [
                ...$type, 'key' => 'pilot_spk_student_feedback',
                'kind' => LearningStepKind::Action, 'stage' => LearningStage::WholeTerm,
                'importance' => LearningImportance::Recommended, 'order' => 40,
                'title' => ['lt' => 'Surink kursiokų nuomonę apie programą', 'en' => 'Collect your fellow students\' views on the programme'],
                'description' => [
                    'lt' => 'Komitetas analizuoja studentų pasitenkinimą ir krūvį. Kartą per semestrą paklausk kursiokų (pokalbis, trumpa apklausa), kas veikia ir kas ne – tai tavo stipriausias argumentas posėdyje.',
                    'en' => 'The committee looks at student satisfaction and workload. Once a semester ask your fellow students (a chat or a short survey) what works and what does not – it is your strongest argument in a meeting.',
                ],
            ],
            [
                ...$type, 'key' => 'pilot_spk_activity_plan', 'section' => 'examples', 'item_kind' => KnowledgeItemKind::Material,
                'subjects' => [['institution_type', self::SPK_TYPE]],
                'kind' => 'knowledge', 'stage' => null,
                'importance' => LearningImportance::Optional, 'order' => 50,
                'title' => ['lt' => 'Studentų atstovo komitete veiklos plano pavyzdys', 'en' => 'Example activity plan of a committee representative'],
                'description' => [
                    'lt' => 'Pavyzdys, kaip susiplanuoti metus komitete. Pasiimk, kas tinka, ir aptark su koordinatoriumi.',
                    'en' => 'An example of how to plan a year on the committee. Take what fits and discuss it with your coordinator.',
                ],
                'document' => 'studentų_atstovo_SPK_veiklos_planas.docx',
            ],
            [
                ...$type, 'key' => 'pilot_spk_prep_materials',
                'kind' => LearningStepKind::MeetingPrep, 'stage' => null,
                'importance' => LearningImportance::Essential, 'order' => 10,
                'title' => ['lt' => 'Perskaityk posėdžio medžiagą', 'en' => 'Read the meeting materials'],
                'description' => [
                    'lt' => 'Medžiaga turi ateiti ne vėliau kaip prieš 3 darbo dienas. Negavai – paprašyk komiteto pirmininko ar studijų skyriaus.',
                    'en' => 'Materials must arrive at least 3 working days before. If they have not, ask the committee chair or the studies office.',
                ],
            ],
            [
                ...$type, 'key' => 'pilot_spk_prep_students',
                'kind' => LearningStepKind::MeetingPrep, 'stage' => null,
                'importance' => LearningImportance::Recommended, 'order' => 20,
                'title' => ['lt' => 'Pasiklausk kursiokų apie svarstomus klausimus', 'en' => 'Ask fellow students about the agenda items'],
                'description' => [
                    'lt' => 'Jei darbotvarkėje yra studentams svarbus klausimas, trumpai pasiteirauk kursiokų nuomonės.',
                    'en' => 'If the agenda has an item that matters to students, briefly ask your fellow students what they think.',
                ],
            ],
            [
                ...$type, 'key' => 'pilot_spk_prep_coordinator',
                'kind' => LearningStepKind::MeetingPrep, 'stage' => null,
                'importance' => LearningImportance::Recommended, 'order' => 30,
                'title' => ['lt' => 'Sudėtingą klausimą aptark su koordinatoriumi', 'en' => 'Talk a difficult item through with your coordinator'],
                'description' => [
                    'lt' => 'Nežinai, kaip balsuoti, ar klausimas liečia daugiau programų – parašyk savo koordinatoriui prieš posėdį.',
                    'en' => 'Unsure how to vote, or the item affects several programmes – write to your coordinator before the meeting.',
                ],
            ],
            [
                ...$type, 'key' => 'pilot_spk_prep_record',
                'kind' => LearningStepKind::MeetingPrep, 'stage' => null,
                'importance' => LearningImportance::Essential, 'order' => 40,
                'title' => ['lt' => 'Po posėdžio užfiksuok jį platformoje', 'en' => 'Record the meeting on the platform afterwards'],
                'description' => [
                    'lt' => 'Darbotvarkės klausimai ir kaip balsavai – kad koordinatorius ir kitas atstovas matytų, kas nuspręsta.',
                    'en' => 'The agenda items and how you voted – so your coordinator and the next representative can see what was decided.',
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $definition */
    private function upsertStep(array $definition): LearningStep|KnowledgeItem|null
    {
        $institutionTypeId = null;
        if (isset($definition['institution_type'])) {
            $institutionTypeId = InstitutionType::query()->where('slug', $definition['institution_type'])->value('id');
            if ($institutionTypeId === null) {
                return null;
            }
        }

        $knowledge = $definition['kind'] === 'knowledge';
        $step = $knowledge
            ? KnowledgeItem::query()->firstOrNew(['key' => $definition['key']])
            : LearningStep::query()->firstOrNew(['key' => $definition['key']]);

        $html = array_map(fn (string $text): string => '<p>'.nl2br(e($text), false).'</p>', $definition['description']);
        $step->fill([
            ...($knowledge ? $this->articleText($definition) : ['kind' => $definition['kind'], 'description' => $html]),
            ...($knowledge ? [
                'kind' => $definition['item_kind'] ?? KnowledgeItemKind::Term,
                'knowledge_section_id' => isset($definition['section']) ? $this->sections[$definition['section']]->id : null,
                'tenant_id' => $definition['tenant_id'] ?? null,
                'counts_in_progress' => $definition['counts_in_progress'] ?? true,
            ] : []),
            'order' => $definition['order'],
            'title' => $definition['title'],
        ]);
        $step->save();
        $step->audiences()->delete();
        $sources = match (true) {
            $definition['audience'] === null => [],
            $definition['audience'] === LearningAudienceType::Purpose => [ResponsibilityPurpose::StudentRepresentation->value, ResponsibilityPurpose::StudentRepCoordination->value],
            default => [$institutionTypeId === null ? null : (string) $institutionTypeId],
        };
        foreach ($sources as $source) {
            $step->audiences()->create([
                'type' => $definition['audience'], 'source_id' => $source, 'tenant_id' => $definition['tenant_id'] ?? null,
                'stage' => $definition['stage'], 'importance' => $definition['importance'], 'order' => 0,
            ]);
        }

        $sources = $definition['sources'] ?? [];
        if (isset($definition['document'])) {
            $documentId = Document::query()->where('title', $definition['document'])->latest('document_date')->value('id');
            if ($documentId !== null) {
                $sources['lt'] = ['document_id' => $documentId];
            }
        }
        if (isset($definition['url'])) {
            $sources['lt'] = ['url' => $definition['url']];
        }
        foreach ($knowledge ? $sources : [] as $locale => $source) {
            $step->sources()->updateOrCreate(['locale' => $locale], ['document_id' => $source['document_id'] ?? null, 'url' => $source['url'] ?? null]);
        }
        if ($step instanceof KnowledgeItem) {
            foreach ($definition['subjects'] ?? [] as [$type, $id]) {
                $id = $type === 'institution_type' ? InstitutionType::query()->where('slug', $id)->value('id') : $id;
                if ($id !== null) {
                    $step->subjects()->firstOrCreate(['subject_type' => $type, 'subject_id' => (string) $id]);
                }
            }
        }

        return $step;
    }

    /**
     * An article's summary is one sentence; whatever follows it becomes the body.
     *
     * @param  array<string, mixed>  $definition
     * @return array{summary: array<string, string>, description: array<string, string>}
     */
    private function articleText(array $definition): array
    {
        $summary = [];
        $description = [];
        foreach ($definition['description'] as $locale => $text) {
            [$first, $rest] = array_pad(preg_split('/(?<=[.!?])\s+/u', $text, 2) ?: [$text], 2, '');
            $explicit = $definition['summary'][$locale] ?? null;
            $summary[$locale] = $this->boldSummary($definition['title'][$locale], $explicit ?? $first);
            $body = $explicit === null ? $rest : $text;
            $description[$locale] = $body === '' ? '' : '<p>'.e($body).'</p>';
        }

        return ['summary' => $summary, 'description' => $description];
    }

    /**
     * One glossary term explains unit regulations; each unit's own regulations are material about it,
     * on the path of that unit's reps.
     */
    private function seedUnitRegulations(): void
    {
        $term = $this->upsertStep([
            'key' => 'pilot_unit_regulations', 'section' => 'vusa',
            'kind' => 'knowledge', 'audience' => null, 'stage' => null, 'importance' => null, 'order' => 50,
            'title' => ['lt' => 'Padalinio nuostatai', 'en' => 'Unit regulations'],
            'summary' => ['lt' => 'Kiekvieno VU SA padalinio taisyklės.', 'en' => 'The rules of each VU SR unit.'],
            'description' => [
                'lt' => 'Juose nustatyta, kas padalinyje renkama, kaip veikia jo valdymo organas ir kaip skiriami atstovai fakultete. Nuostatai turi atitikti VU SA įstatus.',
                'en' => 'They set out who is elected in the unit, how its governing body works and how representatives are appointed in the faculty. They must follow the VU SR statute.',
            ],
        ]);
        if (! $term instanceof KnowledgeItem) {
            return;
        }
        foreach (Tenant::query()->where('type', 'padalinys')->orderBy('shortname')->get() as $index => $tenant) {
            $this->upsertStep([
                'key' => "pilot_unit_regulations_{$tenant->alias}", 'section' => 'unit_regulations', 'item_kind' => KnowledgeItemKind::Material, 'counts_in_progress' => false,
                'kind' => 'knowledge', 'audience' => LearningAudienceType::Everyone, 'tenant_id' => $tenant->id,
                'stage' => null, 'importance' => LearningImportance::Essential, 'order' => $index,
                'subjects' => [['knowledge_item', $term->id]],
                'title' => ['lt' => "{$tenant->shortname} nuostatai", 'en' => "{$tenant->shortname} regulations"],
                'summary' => ['lt' => 'Kas padalinyje renkama, kaip veikia jo valdymo organas ir kaip skiriami atstovai fakultete.', 'en' => 'Who is elected in the unit, how its governing body works and how representatives are appointed in the faculty.'],
                'description' => [
                    'lt' => 'Perskaityk juos pradėdamas – juose rasi savo padalinio taisykles.',
                    'en' => 'Read them when you start – they hold your unit\'s own rules.',
                ],
                'document' => "{$tenant->shortname} nuostatai",
            ]);
        }
    }

    /** Wikipedia-style: the summary opens with the article's own title in bold. */
    private function boldSummary(string $title, string $summary, ?string $abbreviation = null): string
    {
        if (str_contains($summary, '<strong>')) {
            return $summary;
        }
        $firstWord = strtok($summary, ' ') ?: '';
        $isAcronym = mb_strlen($firstWord) > 1 && mb_strtoupper($firstWord) === $firstWord;
        $rest = $isAcronym ? $summary : mb_strtolower(mb_substr($summary, 0, 1)).mb_substr($summary, 1);
        $name = '<strong>'.e($title).'</strong>'.($abbreviation === null ? '' : ' (<strong>'.e($abbreviation).'</strong>)');
        $bold = $name.' – '.e($rest);

        return mb_strlen(strip_tags($bold)) <= 200 ? $bold : e($summary);
    }

    /** Terms are short pages; the pilot documents and terms link to them in their bodies. */
    private function seedGlossary(): void
    {
        $pages = [];
        $bodies = [];
        foreach ($this->glossaryTerms() as $definition) {
            $page = KnowledgeItem::query()->firstOrNew(['key' => 'pilot_term_'.Str::slug($definition['term']['lt'], '_')]);
            $summary = [];
            foreach ($definition['summary'] as $locale => $text) {
                $abbreviation = $definition['abbreviation'][$locale] ?? $definition['abbreviation']['lt'] ?? null;
                $summary[$locale] = $this->boldSummary($definition['term'][$locale], $text, $abbreviation);
            }
            $section = $this->sections[$this->termSections()[$definition['term']['lt']] ?? 'vusa'];
            $page->fill([
                'kind' => KnowledgeItemKind::Term, 'knowledge_section_id' => $section->id, 'title' => $definition['term'],
                'abbreviation' => $definition['abbreviation'] ?? null, 'summary' => $summary, 'order' => count($pages) + 100,
            ]);
            $page->save();
            $pages[$definition['term']['lt']] = $page;
            $bodies[$definition['term']['lt']] = $definition['body'] ?? null;
        }

        $link = fn (string $title, string $locale): string => isset($pages[$title])
            ? '<a href="'.e(route('knowledgeItems.show', $pages[$title])).'" data-knowledge-id="'.$pages[$title]->id.'">'.e((string) $pages[$title]->getTranslation('title', $locale)).'</a>'
            : e($title);
        $paragraph = fn (array $titles, string $locale): string => '<p>'.($locale === 'lt' ? 'Susijusios sąvokos: ' : 'Related terms: ')
            .implode(', ', array_map(fn (string $title): string => $link($title, $locale), $titles)).'.</p>';

        foreach ($this->documentTerms() as $key => $titles) {
            $document = KnowledgeItem::query()->where('key', $key)->first();
            $document?->update(['description' => [
                'lt' => $document->getTranslation('description', 'lt', false).$paragraph($titles, 'lt'),
                'en' => $document->getTranslation('description', 'en', false).$paragraph($titles, 'en'),
            ]]);
        }
        foreach ($pages as $title => $page) {
            $body = $bodies[$title];
            $links = $this->termLinks()[$title] ?? [];
            $html = fn (string $locale): string => ($body === null ? '' : '<p>'.e($body[$locale]).'</p>').($links === [] ? '' : $paragraph($links, $locale));
            $page->update(['description' => ['lt' => $html('lt'), 'en' => $html('en')]]);
        }

        $spkType = InstitutionType::query()->where('slug', self::SPK_TYPE)->value('id');
        if ($spkType !== null && isset($pages['Studijų programos komitetas'])) {
            $pages['Studijų programos komitetas']->subjects()->firstOrCreate(['subject_type' => 'institution_type', 'subject_id' => (string) $spkType]);
        }
    }

    /** @return array<string, string> */
    private function termSections(): array
    {
        return [
            'Studijų programos komitetas' => 'studies', 'Studijų programos aprašas' => 'studies', 'Dalyko (modulio) aprašas' => 'studies',
            'Studijų rezultatai' => 'studies', 'Kreditas' => 'studies', 'Akreditacija' => 'studies',
            'Studijų kokybės vertinimo centras' => 'studies', 'Socialiniai partneriai' => 'studies', 'Kvorumas' => 'meetings',
        ];
    }

    /** @return array<string, list<string>> */
    private function documentTerms(): array
    {
        return [
            'pilot_spk_regulations' => ['Studijų programos komitetas', 'Kvorumas', 'Socialiniai partneriai', 'Studijų rezultatai', 'Kamieninis akademinis padalinys'],
            'pilot_vu_statute' => ['Senatas', 'Taryba', 'Kamieninis akademinis padalinys'],
            'pilot_law_on_higher_education' => ['Akreditacija', 'Studijų kokybės vertinimo centras'],
            'pilot_vusa_statute' => ['Kadencija'],
        ];
    }

    /** @return array<string, list<string>> */
    private function termLinks(): array
    {
        return [
            'Studijų programos komitetas' => ['Studijų programos aprašas', 'Studijų rezultatai', 'Kvorumas', 'Socialiniai partneriai'],
            'Studijų programos aprašas' => ['Studijų rezultatai', 'Kreditas', 'Dalyko (modulio) aprašas'],
            'Akreditacija' => ['Studijų kokybės vertinimo centras'],
            'Senatas' => ['Taryba', 'Kvorumas'],
        ];
    }

    /** @return list<array{term: array{lt: string, en: string}, abbreviation?: array{lt?: string, en?: string}, summary: array{lt: string, en: string}, body?: array{lt: string, en: string}}> */
    private function glossaryTerms(): array
    {
        return [
            ['term' => ['lt' => 'Studijų programos komitetas', 'en' => 'Study programme committee'], 'abbreviation' => ['lt' => 'SPK', 'en' => 'SPC'],
                'summary' => ['lt' => 'Akademinis darinys, koordinuojantis studijų programą.', 'en' => 'The academic body that coordinates a study programme.'],
                'body' => ['lt' => 'Komitetas prižiūri, kaip programa vykdoma, svarsto jos pakeitimus ir studentų atsiliepimus.', 'en' => 'It oversees how the programme is run and discusses changes to it and student feedback.']],
            ['term' => ['lt' => 'Studijų programos aprašas', 'en' => 'Study programme description'],
                'summary' => ['lt' => 'Dokumentas, aprašantis studijų programą.', 'en' => 'The document that describes a study programme.'],
                'body' => ['lt' => 'Jame surašyti programos tikslai, studijų rezultatai, struktūra ir dalykai.', 'en' => 'It sets out the programme\'s aims, learning outcomes, structure and courses.']],
            ['term' => ['lt' => 'Dalyko (modulio) aprašas', 'en' => 'Course unit (module) description'],
                'summary' => ['lt' => 'Vieno dalyko turinio ir vertinimo aprašas.', 'en' => 'The description of one course and how it is assessed.'],
                'body' => ['lt' => 'Jame nurodomi dalyko studijų rezultatai, vertinimo tvarka ir krūvis.', 'en' => 'It lists the course\'s learning outcomes, assessment and workload.']],
            ['term' => ['lt' => 'Studijų rezultatai', 'en' => 'Learning outcomes'],
                'summary' => ['lt' => 'Ką studentas turi žinoti ir gebėti baigęs dalyką ar programą.', 'en' => 'What a student should know and be able to do after a course or programme.']],
            ['term' => ['lt' => 'Kreditas', 'en' => 'Credit'], 'abbreviation' => ['lt' => 'ECTS'],
                'summary' => ['lt' => 'Studijų apimties vienetas.', 'en' => 'A unit of study workload.'],
                'body' => ['lt' => 'Vienas kreditas atitinka apie 25–30 valandų studento darbo.', 'en' => 'One credit is about 25–30 hours of student work.']],
            ['term' => ['lt' => 'Akreditacija', 'en' => 'Accreditation'],
                'summary' => ['lt' => 'Leidimas vykdyti studijų kryptį tam tikrą laikotarpį.', 'en' => 'Permission to run a study field for a set period.'],
                'body' => ['lt' => 'Ji suteikiama po išorinio vertinimo.', 'en' => 'It is granted after an external evaluation.']],
            ['term' => ['lt' => 'Studijų kokybės vertinimo centras', 'en' => 'Centre for Quality Assessment in Higher Education'], 'abbreviation' => ['lt' => 'SKVC'],
                'summary' => ['lt' => 'Institucija, vertinanti studijų kryptis.', 'en' => 'The agency that evaluates study fields.'],
                'body' => ['lt' => 'Centras organizuoja studijų krypčių išorinį vertinimą ir akreditaciją.', 'en' => 'It organises the external evaluation and accreditation of study fields.']],
            ['term' => ['lt' => 'Senatas', 'en' => 'Senate'],
                'summary' => ['lt' => 'Universiteto akademinių reikalų valdymo organas.', 'en' => 'The university body governing academic matters.'],
                'body' => ['lt' => 'Senatas tvirtina studijų tvarką, programas ir kitus akademinius sprendimus.', 'en' => 'It approves study rules, programmes and other academic decisions.']],
            ['term' => ['lt' => 'Taryba', 'en' => 'Council'],
                'summary' => ['lt' => 'Universiteto strateginio valdymo organas.', 'en' => 'The university\'s strategic governing body.'],
                'body' => ['lt' => 'Taryba tvirtina strategiją ir biudžetą bei renka rektorių.', 'en' => 'It approves the strategy and budget and elects the Rector.']],
            ['term' => ['lt' => 'Kamieninis akademinis padalinys', 'en' => 'Core academic unit'], 'abbreviation' => ['lt' => 'KAP'],
                'summary' => ['lt' => 'Fakultetas, institutas ar centras, vykdantis studijas.', 'en' => 'A faculty, institute or centre that runs studies.']],
            ['term' => ['lt' => 'Socialiniai partneriai', 'en' => 'Social partners'],
                'summary' => ['lt' => 'Darbdavių ir profesinių organizacijų atstovai.', 'en' => 'Representatives of employers and professional organisations.'],
                'body' => ['lt' => 'Jie padeda tobulinti studijų programą ir dalyvauja komiteto darbe.', 'en' => 'They help improve a study programme and take part in the committee\'s work.']],
            ['term' => ['lt' => 'Kvorumas', 'en' => 'Quorum'],
                'summary' => ['lt' => 'Mažiausias narių skaičius, kad posėdžio sprendimai galiotų.', 'en' => 'The minimum number of members for a meeting\'s decisions to be valid.'],
                'body' => ['lt' => 'Studijų programos komitete – ne mažiau kaip pusė narių.', 'en' => 'In a study programme committee, at least half of the members.']],
            ['term' => ['lt' => 'Kadencija', 'en' => 'Term of office'],
                'summary' => ['lt' => 'Laikotarpis, kuriam esi išrinktas ar deleguotas eiti pareigas.', 'en' => 'The period for which you are elected or delegated to a role.']],
        ];
    }
}
