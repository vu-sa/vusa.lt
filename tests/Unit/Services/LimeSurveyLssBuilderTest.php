<?php

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Services\LimeSurveyLssBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->builder = new LimeSurveyLssBuilder;
});

/**
 * Build a survey with one question of each supported type and return the parsed document.
 */
function buildDocument(?Survey $survey = null): SimpleXMLElement
{
    $survey ??= Survey::factory()->create([
        'name' => ['lt' => 'Studijų kokybė', 'en' => 'Study quality'],
        'description' => ['lt' => 'Aprašymas', 'en' => 'Description'],
        'welcome_text' => ['lt' => 'Sveiki', 'en' => 'Welcome'],
    ]);

    $order = 0;

    foreach (SurveyQuestionType::cases() as $type) {
        SurveyQuestion::factory()->ofType($type)->create([
            'survey_id' => $survey->id,
            'group_name' => ['lt' => 'Bendra', 'en' => 'General'],
            'title' => 'Q'.($order + 1),
            'order' => $order++,
        ]);
    }

    $xml = (new LimeSurveyLssBuilder)->build($survey->fresh('questions'));

    return new SimpleXMLElement($xml);
}

describe('document shape', function (): void {
    test('produces a Survey document with the sections the LimeSurvey importer reads', function (): void {
        $doc = buildDocument();

        expect((string) $doc->LimeSurveyDocType)->toBe('Survey');
        expect((int) $doc->DBVersion)->toBeGreaterThan(156);

        foreach (['surveys', 'surveys_languagesettings', 'groups', 'group_l10ns', 'questions', 'question_l10ns', 'answers', 'answer_l10ns'] as $section) {
            expect(isset($doc->{$section}))->toBeTrue("missing section: {$section}");
        }
    });

    test('declares both locales with lt as the base language', function (): void {
        $doc = buildDocument();

        $languages = [];
        foreach ($doc->languages->language as $language) {
            $languages[] = (string) $language;
        }

        expect($languages)->toBe(['lt', 'en']);
        expect((string) $doc->surveys->rows->row[0]->language)->toBe('lt');
        expect((string) $doc->surveys->rows->row[0]->additional_languages)->toBe('en');
    });

    test('every section lists its fieldnames and every row matches them', function (): void {
        $doc = buildDocument();

        foreach (['surveys', 'groups', 'questions', 'answers'] as $section) {
            $fields = [];
            foreach ($doc->{$section}->fields->fieldname as $fieldname) {
                $fields[] = (string) $fieldname;
            }

            expect($fields)->not->toBeEmpty();

            foreach ($doc->{$section}->rows->row as $row) {
                $keys = array_keys((array) $row);
                expect($keys)->toEqualCanonicalizing($fields, "row keys diverge in {$section}");
            }
        }
    });
});

describe('questions', function (): void {
    test('emits one question row per question with the LimeSurvey type code', function (): void {
        $doc = buildDocument();

        $types = [];
        foreach ($doc->questions->rows->row as $row) {
            $types[] = (string) $row->type;
        }

        expect($types)->toBe(['S', 'T', 'L', 'M', '5']);
    });

    test('puts question text in question_l10ns, once per language', function (): void {
        $doc = buildDocument();

        $byLanguage = [];
        foreach ($doc->question_l10ns->rows->row as $row) {
            $byLanguage[(string) $row->language][] = (string) $row->qid;
        }

        expect(array_keys($byLanguage))->toEqualCanonicalizing(['lt', 'en']);
        expect($byLanguage['lt'])->toHaveCount(5);
        expect($byLanguage['en'])->toHaveCount(5);
    });

    test('only option-bearing types produce answer rows', function (): void {
        $doc = buildDocument();

        // List and MultipleChoice carry two options each; the other three carry none.
        expect($doc->answers->rows->row->count())->toBe(4);

        $qids = [];
        foreach ($doc->answers->rows->row as $row) {
            $qids[] = (string) $row->qid;
        }

        expect(array_unique($qids))->toHaveCount(2);
    });

    test('links every answer_l10n to an answer that exists', function (): void {
        $doc = buildDocument();

        $aids = [];
        foreach ($doc->answers->rows->row as $row) {
            $aids[] = (string) $row->aid;
        }

        foreach ($doc->answer_l10ns->rows->row as $row) {
            expect($aids)->toContain((string) $row->aid);
        }
    });

    test('marks required questions mandatory', function (): void {
        $survey = Survey::factory()->create();
        SurveyQuestion::factory()->create(['survey_id' => $survey->id, 'is_required' => true, 'title' => 'Q1', 'order' => 0]);
        SurveyQuestion::factory()->create(['survey_id' => $survey->id, 'is_required' => false, 'title' => 'Q2', 'order' => 1]);

        $doc = new SimpleXMLElement((new LimeSurveyLssBuilder)->build($survey->fresh('questions')));

        $mandatory = [];
        foreach ($doc->questions->rows->row as $row) {
            $mandatory[(string) $row->title] = (string) $row->mandatory;
        }

        expect($mandatory)->toBe(['Q1' => 'Y', 'Q2' => 'N']);
    });
});

describe('grouping', function (): void {
    test('splits questions into groups by group name and keeps order', function (): void {
        $survey = Survey::factory()->create();

        SurveyQuestion::factory()->create(['survey_id' => $survey->id, 'group_name' => ['lt' => 'Pirma', 'en' => 'First'], 'title' => 'Q1', 'order' => 0]);
        SurveyQuestion::factory()->create(['survey_id' => $survey->id, 'group_name' => ['lt' => 'Antra', 'en' => 'Second'], 'title' => 'Q2', 'order' => 1]);
        SurveyQuestion::factory()->create(['survey_id' => $survey->id, 'group_name' => ['lt' => 'Pirma', 'en' => 'First'], 'title' => 'Q3', 'order' => 2]);

        $doc = new SimpleXMLElement((new LimeSurveyLssBuilder)->build($survey->fresh('questions')));

        expect($doc->groups->rows->row->count())->toBe(2);

        $gids = [];
        foreach ($doc->questions->rows->row as $row) {
            $gids[(string) $row->title] = (string) $row->gid;
        }

        expect($gids['Q1'])->toBe($gids['Q3']);
        expect($gids['Q2'])->not->toBe($gids['Q1']);
    });
});

describe('escaping', function (): void {
    test('a CDATA terminator in author text cannot break the document', function (): void {
        $survey = Survey::factory()->create();

        SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'title' => 'Q1',
            'question' => ['lt' => 'Ar patinka ]]> tai?', 'en' => 'Do you like ]]> this?'],
            'order' => 0,
        ]);

        $xml = (new LimeSurveyLssBuilder)->build($survey->fresh('questions'));

        // Parses at all — the real assertion, since a broken CDATA makes this throw.
        $doc = new SimpleXMLElement($xml);

        $text = (string) $doc->question_l10ns->rows->row[0]->question;
        expect($text)->toBe('Ar patinka ]]> tai?');
    });

    test('survives HTML in question text', function (): void {
        $survey = Survey::factory()->create();

        SurveyQuestion::factory()->create([
            'survey_id' => $survey->id,
            'title' => 'Q1',
            'question' => ['lt' => '<strong>Kaip</strong> & kodėl?', 'en' => '<strong>How</strong> & why?'],
            'order' => 0,
        ]);

        $doc = new SimpleXMLElement((new LimeSurveyLssBuilder)->build($survey->fresh('questions')));

        expect((string) $doc->question_l10ns->rows->row[0]->question)->toBe('<strong>Kaip</strong> & kodėl?');
    });
});

describe('survey settings', function (): void {
    test('carries anonymity and the response window into the surveys row', function (): void {
        $survey = Survey::factory()->create([
            'is_anonymous' => true,
            'starts_at' => '2026-09-01 08:00:00',
            'ends_at' => '2026-09-30 20:00:00',
        ]);
        SurveyQuestion::factory()->create(['survey_id' => $survey->id, 'title' => 'Q1', 'order' => 0]);

        $doc = new SimpleXMLElement((new LimeSurveyLssBuilder)->build($survey->fresh('questions')));
        $row = $doc->surveys->rows->row[0];

        expect((string) $row->anonymized)->toBe('Y');
        expect((string) $row->active)->toBe('N');
        expect((string) $row->startdate)->toBe('2026-09-01 08:00:00');
        expect((string) $row->expires)->toBe('2026-09-30 20:00:00');
    });

    test('a non-anonymous survey is datestamped', function (): void {
        $survey = Survey::factory()->create(['is_anonymous' => false]);
        SurveyQuestion::factory()->create(['survey_id' => $survey->id, 'title' => 'Q1', 'order' => 0]);

        $doc = new SimpleXMLElement((new LimeSurveyLssBuilder)->build($survey->fresh('questions')));

        expect((string) $doc->surveys->rows->row[0]->anonymized)->toBe('N');
        expect((string) $doc->surveys->rows->row[0]->datestamp)->toBe('Y');
    });

    test('titles the survey per language', function (): void {
        $doc = buildDocument();

        $titles = [];
        foreach ($doc->surveys_languagesettings->rows->row as $row) {
            $titles[(string) $row->surveyls_language] = (string) $row->surveyls_title;
        }

        expect($titles)->toBe(['lt' => 'Studijų kokybė', 'en' => 'Study quality']);
    });
});
