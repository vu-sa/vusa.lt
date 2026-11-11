<?php

namespace Database\Seeders;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\GlossaryTerm;
use App\Models\InstitutionType;
use App\Models\LearningSection;
use App\Models\LearningStep;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningPathPilotSeeder extends Seeder
{
    private const string SPK_TYPE = 'spk';

    private const string SPK_REGULATIONS_EN = 'https://www.kf.vu.lt/dokumentai/documents/2024/STUDIJ%C5%B2_PROGRAMOS_KOMITETO_NUOSTATAI_-_EN_Translation_STUDIJU%CC%A8_PROGRAMOS_KOMITETO_NUOSTATAI_SENATUI_po_StK.pdf';

    public function run(): void
    {
        DB::transaction(function (): void {
            $section = LearningSection::query()->firstOrCreate(['title->lt' => 'Įvadiniai mokymai'], [
                'title' => ['lt' => 'Įvadiniai mokymai', 'en' => 'Introductory training'], 'order' => 0,
            ]);
            $steps = [...$this->foundationSteps(), ...$this->spkSteps()];
            foreach ($steps as $definition) {
                $step = $this->upsertStep($definition);
                if ($step !== null && in_array($step->key, ['pilot_vusa_statute', 'pilot_vusa_rules_of_procedure'], true)) {
                    $step->update(['learning_section_id' => $section->id]);
                }
            }
            $this->seedUnitRegulations();
            $this->seedGlossary();
        });
    }

    /** @return list<array<string, mixed>> */
    private function foundationSteps(): array
    {
        return [
            [
                'key' => 'pilot_vusa_statute',
                'kind' => LearningStepKind::Knowledge, 'audience' => LearningAudienceType::Purpose,
                'stage' => null, 'importance' => LearningImportance::Essential, 'order' => 10,
                'title' => ['lt' => 'VU SA įstatai', 'en' => 'VU SR statute'],
                'description' => [
                    'lt' => 'Pagrindinis VU SA dokumentas: kas yra Studentų atstovybė, kokie jos organai ir kaip jie renkami. Iš čia kyla ir tavo, kaip atstovo, įgaliojimai.',
                    'en' => 'The core VU SR document: what the Students\' Representation is, its bodies and how they are elected. Your mandate as a representative comes from here.',
                ],
                'document' => 'VU SA Įstatai (nuo 2025 m.)',
            ],
            [
                'key' => 'pilot_vusa_rules_of_procedure',
                'kind' => LearningStepKind::Knowledge, 'audience' => LearningAudienceType::Purpose,
                'stage' => null, 'importance' => LearningImportance::Recommended, 'order' => 20,
                'title' => ['lt' => 'VU SA darbo reglamentas', 'en' => 'VU SR rules of procedure'],
                'description' => [
                    'lt' => 'Kaip VU SA priima sprendimus, rengia posėdžius ir deleguoja atstovus. Verta žinoti, kai teiksi klausimą ar ieškosi, kas už ką atsakingas.',
                    'en' => 'How VU SR makes decisions, holds meetings and delegates representatives. Useful when you raise an issue or look for who is responsible for what.',
                ],
                'document' => 'VU SA Darbo reglamentas (nuo 2025 m.)',
            ],
            [
                'key' => 'pilot_vu_statute',
                'kind' => LearningStepKind::Knowledge, 'audience' => LearningAudienceType::Purpose,
                'stage' => null, 'importance' => LearningImportance::Recommended, 'order' => 30,
                'title' => ['lt' => 'Vilniaus universiteto statutas', 'en' => 'Statute of Vilnius University'],
                'description' => [
                    'lt' => 'Kaip valdomas universitetas: Senatas, Taryba, rektorius, padaliniai ir kur juose dalyvauja studentų atstovai. Padeda suprasti, kur priimamas sprendimas, apie kurį kalbi.',
                    'en' => 'How the university is governed: the Senate, the Council, the Rector, units, and where student representatives take part. Helps you see where a decision is actually made.',
                ],
                'url' => 'https://e-seimas.lrs.lt/portal/legalAct/lt/TAD/877eb330dcd111e3b272e0e81c552d38',
            ],
            [
                'key' => 'pilot_law_on_higher_education',
                'kind' => LearningStepKind::Knowledge, 'audience' => LearningAudienceType::Purpose,
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
                ...$type, 'key' => 'pilot_spk_regulations',
                'kind' => LearningStepKind::Knowledge, 'stage' => null,
                'importance' => LearningImportance::Essential, 'order' => 10,
                'title' => ['lt' => 'Studijų programos komiteto nuostatai', 'en' => 'Regulations of the study programme committee'],
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
                ...$type, 'key' => 'pilot_spk_activity_plan',
                'kind' => LearningStepKind::Knowledge, 'stage' => null,
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
    private function upsertStep(array $definition): ?LearningStep
    {
        $institutionTypeId = null;
        if (isset($definition['institution_type'])) {
            $institutionTypeId = InstitutionType::query()->where('slug', $definition['institution_type'])->value('id');
            if ($institutionTypeId === null) {
                return null;
            }
        }

        /** @var LearningStep $step */
        $step = LearningStep::query()->firstOrNew(['key' => $definition['key']]);

        $step->fill([
            'kind' => $definition['kind'],
            'order' => $definition['order'],
            'title' => $definition['title'],
            'description' => array_map(fn (string $text): string => '<p>'.nl2br(e($text), false).'</p>', $definition['description']),
        ]);
        $step->save();
        $step->audiences()->delete();
        $sources = $definition['audience'] === LearningAudienceType::Purpose
            ? [ResponsibilityPurpose::StudentRepresentation->value, ResponsibilityPurpose::StudentRepCoordination->value]
            : [$institutionTypeId === null ? null : (string) $institutionTypeId];
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
        foreach ($sources as $locale => $source) {
            $step->sources()->updateOrCreate(['locale' => $locale], ['document_id' => $source['document_id'] ?? null, 'url' => $source['url'] ?? null]);
        }

        return $step;
    }

    private function seedUnitRegulations(): void
    {
        foreach (Tenant::query()->where('type', 'padalinys')->get() as $tenant) {
            $this->upsertStep([
                'key' => "pilot_unit_regulations_{$tenant->alias}",
                'kind' => LearningStepKind::Knowledge, 'audience' => LearningAudienceType::Everyone, 'tenant_id' => $tenant->id,
                'stage' => null, 'importance' => LearningImportance::Essential, 'order' => 10,
                'title' => ['lt' => "{$tenant->shortname} nuostatai", 'en' => "{$tenant->shortname} regulations"],
                'description' => [
                    'lt' => 'Tavo padalinio taisyklės: kas jame renkama, kaip veikia jo valdymo organas ir kaip skiriami atstovai fakultete.',
                    'en' => 'Your unit\'s rules: who is elected, how its governing body works and how representatives are appointed in the faculty.',
                ],
                'document' => "{$tenant->shortname} nuostatai",
            ]);
        }
    }

    private function seedGlossary(): void
    {
        $links = [
            'pilot_spk_regulations' => ['Studijų programos komitetas', 'Kvorumas', 'Socialiniai partneriai', 'Studijų rezultatai', 'Kamieninis akademinis padalinys'],
            'pilot_spk_programme' => ['Studijų programos aprašas', 'Dalyko (modulio) aprašas', 'Kreditas', 'Studijų rezultatai'],
            'pilot_vu_statute' => ['Senatas', 'Taryba', 'Kamieninis akademinis padalinys'],
            'pilot_law_on_higher_education' => ['Akreditacija', 'Studijų kokybės vertinimo centras'],
            'pilot_vusa_statute' => ['Kadencija'],
        ];

        foreach ($this->glossaryTerms() as $definition) {
            $term = GlossaryTerm::query()->where('term->lt', $definition['term']['lt'])->first()
                ?? GlossaryTerm::query()->create($definition);
            foreach ($links as $key => $terms) {
                if (in_array($definition['term']['lt'], $terms, true)) {
                    /** @var LearningStep|null $step */
                    $step = LearningStep::query()->where('key', $key)->first();
                    $step?->glossaryTerms()->syncWithoutDetaching([$term->id]);
                }
            }
        }
    }

    /** @return list<array{term: array{lt: string, en: string}, abbreviation?: array{lt?: string, en?: string}, definition: array{lt: string, en: string}}> */
    private function glossaryTerms(): array
    {
        return [
            ['term' => ['lt' => 'Studijų programos komitetas', 'en' => 'Study programme committee'], 'abbreviation' => ['lt' => 'SPK', 'en' => 'SPC'], 'definition' => ['lt' => 'Akademinis darinys, kuris koordinuoja studijų programą ir prižiūri jos vykdymą. Sutrumpinimas – SPK.', 'en' => 'The academic body that coordinates a study programme and oversees how it is run. Abbreviated SPK.']],
            ['term' => ['lt' => 'Studijų programos aprašas', 'en' => 'Study programme description'], 'definition' => ['lt' => 'Dokumentas, kuriame aprašyti programos tikslai, studijų rezultatai, struktūra ir dalykai.', 'en' => 'The document describing a programme\'s aims, learning outcomes, structure and courses.']],
            ['term' => ['lt' => 'Dalyko (modulio) aprašas', 'en' => 'Course unit (module) description'], 'definition' => ['lt' => 'Vieno dalyko turinys, studijų rezultatai, vertinimo tvarka ir krūvis.', 'en' => 'The content, learning outcomes, assessment and workload of one course.']],
            ['term' => ['lt' => 'Studijų rezultatai', 'en' => 'Learning outcomes'], 'definition' => ['lt' => 'Ką studentas turi žinoti ir gebėti baigęs dalyką ar programą.', 'en' => 'What a student should know and be able to do after a course or programme.']],
            ['term' => ['lt' => 'Kreditas', 'en' => 'Credit'], 'definition' => ['lt' => 'Studijų apimties vienetas (ECTS); vienas kreditas atitinka apie 25–30 valandų studento darbo.', 'en' => 'A unit of study workload (ECTS); one credit is about 25–30 hours of student work.']],
            ['term' => ['lt' => 'Akreditacija', 'en' => 'Accreditation'], 'definition' => ['lt' => 'Išorinio vertinimo rezultatas: leidimas vykdyti studijų kryptį tam tikram laikotarpiui.', 'en' => 'The outcome of external evaluation: permission to run a study field for a set period.']],
            ['term' => ['lt' => 'Studijų kokybės vertinimo centras', 'en' => 'Centre for Quality Assessment in Higher Education'], 'abbreviation' => ['lt' => 'SKVC'], 'definition' => ['lt' => 'Institucija, kuri organizuoja studijų krypčių išorinį vertinimą ir akreditaciją.', 'en' => 'Organises external evaluation and accreditation of study fields.']],
            ['term' => ['lt' => 'Senatas', 'en' => 'Senate'], 'definition' => ['lt' => 'Universiteto akademinių reikalų valdymo organas: tvirtina studijų tvarką, programas ir kitus akademinius sprendimus.', 'en' => 'The university body governing academic matters: approves study rules, programmes and other academic decisions.']],
            ['term' => ['lt' => 'Taryba', 'en' => 'Council'], 'definition' => ['lt' => 'Universiteto strateginio valdymo organas: strategija, biudžetas, rektoriaus rinkimai.', 'en' => 'The university\'s strategic governing body: strategy, budget, election of the Rector.']],
            ['term' => ['lt' => 'Kamieninis akademinis padalinys', 'en' => 'Core academic unit'], 'definition' => ['lt' => 'Fakultetas, institutas ar centras, vykdantis studijas. Sutrumpinimas – KAP.', 'en' => 'A faculty, institute or centre that runs studies. Abbreviated KAP.']],
            ['term' => ['lt' => 'Socialiniai partneriai', 'en' => 'Social partners'], 'definition' => ['lt' => 'Darbdavių ir profesinių organizacijų atstovai, padedantys tobulinti programą.', 'en' => 'Representatives of employers and professional organisations who help improve a programme.']],
            ['term' => ['lt' => 'Kvorumas', 'en' => 'Quorum'], 'definition' => ['lt' => 'Mažiausias dalyvaujančių narių skaičius, kad posėdžio sprendimai galiotų (komitete – ne mažiau kaip pusė narių).', 'en' => 'The minimum number of members present for decisions to be valid (in a committee, at least half).']],
            ['term' => ['lt' => 'Kadencija', 'en' => 'Term of office'], 'definition' => ['lt' => 'Laikotarpis, kuriam esi išrinktas ar deleguotas eiti pareigas.', 'en' => 'The period for which you are elected or delegated to a role.']],
        ];
    }
}
