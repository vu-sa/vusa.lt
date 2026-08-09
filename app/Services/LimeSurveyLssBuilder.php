<?php

namespace App\Services;

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use XMLWriter;

/**
 * Renders a Survey as a LimeSurvey .lss document.
 *
 * This exists because RemoteControl 2 has no add_question() and no add_answer(): surveys
 * can be created empty, but questions and answer options can only enter LimeSurvey through
 * an XML import. Since vusa.lt owns the question bank *and* must support freely authored
 * questions, generating the XML is the only route that supports both.
 *
 * The document is built with XMLWriter rather than string concatenation so that CDATA
 * escaping is handled in exactly one place — every value here is author-supplied text that
 * could otherwise close a CDATA section and corrupt the document.
 *
 * Structure follows the modern (LimeSurvey 4+) layout the LS7 importer expects: question
 * and group text live in *_l10ns sections keyed by language, not inline on the row. The
 * importer reads these sections in order and is tolerant of columns it does not know, but
 * not of missing linkage ids.
 */
class LimeSurveyLssBuilder
{
    /**
     * Only needs to exceed the importer's legacy-mapping thresholds (it branches on
     * < 145 and < 156 to rename old columns). Any modern value behaves identically.
     */
    private const int DB_VERSION = 620;

    /**
     * Ids are local to the document — LimeSurvey remaps them all on import. They only have
     * to be internally consistent.
     */
    private int $nextQid = 1;

    private int $nextAid = 1;

    /**
     * @return string The .lss XML document.
     */
    public function build(Survey $survey): string
    {
        $this->nextQid = 1;
        $this->nextAid = 1;

        $languages = $this->languages($survey);
        $baseLanguage = $languages[0];
        $groups = $this->groupQuestions($survey);

        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString('  ');
        $writer->startDocument('1.0', 'UTF-8');

        $writer->startElement('document');
        $writer->writeElement('LimeSurveyDocType', 'Survey');
        $writer->writeElement('DBVersion', (string) self::DB_VERSION);

        $writer->startElement('languages');
        foreach ($languages as $language) {
            $writer->writeElement('language', $language);
        }
        $writer->endElement();

        $this->writeSurveys($writer, $survey, $languages, $baseLanguage);
        $this->writeLanguageSettings($writer, $survey, $languages);
        $this->writeGroups($writer, $groups, $languages, $baseLanguage);
        $this->writeQuestions($writer, $groups, $languages, $baseLanguage);

        $writer->endElement(); // document
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * Locales the survey is emitted in: the app's base locale first, then any other locale
     * for which at least one question has been translated.
     *
     * @return list<string>
     */
    private function languages(Survey $survey): array
    {
        $base = config('app.locale', 'lt');
        $languages = [$base];

        foreach (config('app.locales', ['lt', 'en']) as $locale) {
            if ($locale !== $base && $this->hasContentIn($survey, $locale)) {
                $languages[] = $locale;
            }
        }

        return $languages;
    }

    private function hasContentIn(Survey $survey, string $locale): bool
    {
        if (filled($survey->getTranslation('name', $locale, false))) {
            return true;
        }

        return $survey->questions->contains(
            fn (SurveyQuestion $question): bool => filled($question->getTranslation('question', $locale, false))
        );
    }

    /**
     * Questions bucketed by their group name in the base locale, preserving order.
     *
     * @return array<string, list<SurveyQuestion>>
     */
    private function groupQuestions(Survey $survey): array
    {
        $groups = [];

        foreach ($survey->questions->sortBy('order') as $question) {
            $name = (string) ($question->group_name ?: __('surveys.default_group'));
            $groups[$name][] = $question;
        }

        return $groups;
    }

    /**
     * @param  list<string>  $languages
     */
    private function writeSurveys(XMLWriter $writer, Survey $survey, array $languages, string $baseLanguage): void
    {
        $additional = array_slice($languages, 1);

        $this->writeSection($writer, 'surveys', [[
            'sid' => 1,
            'admin' => config('app.name'),
            'active' => 'N',
            'language' => $baseLanguage,
            'additional_languages' => implode(' ', $additional),
            // 'G' renders one question group per page.
            'format' => 'G',
            'anonymized' => $survey->is_anonymous ? 'Y' : 'N',
            // Anonymous surveys must not keep per-response timing/token linkage either.
            'savetimings' => 'N',
            'datestamp' => $survey->is_anonymous ? 'N' : 'Y',
            'ipaddr' => 'N',
            'refurl' => 'N',
            'startdate' => $survey->starts_at?->format('Y-m-d H:i:s'),
            'expires' => $survey->ends_at?->format('Y-m-d H:i:s'),
            'showwelcome' => 'Y',
            'allowregister' => 'N',
            'listpublic' => 'N',
        ]]);
    }

    /**
     * @param  list<string>  $languages
     */
    private function writeLanguageSettings(XMLWriter $writer, Survey $survey, array $languages): void
    {
        $rows = [];

        foreach ($languages as $language) {
            $rows[] = [
                'surveyls_survey_id' => 1,
                'surveyls_language' => $language,
                'surveyls_title' => $survey->getTranslation('name', $language, false)
                    ?: $survey->getTranslation('name', $languages[0], false),
                'surveyls_description' => $survey->getTranslation('description', $language, false),
                'surveyls_welcometext' => $survey->getTranslation('welcome_text', $language, false),
            ];
        }

        $this->writeSection($writer, 'surveys_languagesettings', $rows);
    }

    /**
     * @param  array<string, list<SurveyQuestion>>  $groups
     * @param  list<string>  $languages
     */
    private function writeGroups(XMLWriter $writer, array $groups, array $languages, string $baseLanguage): void
    {
        $groupRows = [];
        $l10nRows = [];
        $gid = 1;

        foreach ($groups as $name => $questions) {
            $groupRows[] = [
                'gid' => $gid,
                'sid' => 1,
                'group_order' => $gid,
                'randomization_group' => '',
                'grelevance' => '1',
            ];

            foreach ($languages as $language) {
                $l10nRows[] = [
                    'id' => count($l10nRows) + 1,
                    'gid' => $gid,
                    'group_name' => $this->translatedGroupName($questions, $language, $name),
                    'description' => '',
                    'language' => $language,
                ];
            }

            $gid++;
        }

        $this->writeSection($writer, 'groups', $groupRows);
        $this->writeSection($writer, 'group_l10ns', $l10nRows);
    }

    /**
     * @param  list<SurveyQuestion>  $questions
     */
    private function translatedGroupName(array $questions, string $language, string $fallback): string
    {
        foreach ($questions as $question) {
            $translated = $question->getTranslation('group_name', $language, false);

            if (filled($translated)) {
                return (string) $translated;
            }
        }

        return $fallback;
    }

    /**
     * @param  array<string, list<SurveyQuestion>>  $groups
     * @param  list<string>  $languages
     */
    private function writeQuestions(XMLWriter $writer, array $groups, array $languages, string $baseLanguage): void
    {
        $questionRows = [];
        $questionL10ns = [];
        $answerRows = [];
        $answerL10ns = [];
        $attributeRows = [];

        $gid = 1;

        foreach ($groups as $questions) {
            $order = 1;

            foreach ($questions as $question) {
                $qid = $this->nextQid++;

                $questionRows[] = [
                    'qid' => $qid,
                    'parent_qid' => 0,
                    'sid' => 1,
                    'gid' => $gid,
                    'type' => $question->type->value,
                    'title' => $question->title,
                    'preg' => '',
                    'other' => 'N',
                    'mandatory' => $question->is_required ? 'Y' : 'N',
                    'question_order' => $order,
                    'scale_id' => 0,
                    'same_default' => 0,
                    'relevance' => '1',
                    'question_theme_name' => $question->type->themeName(),
                ];

                foreach ($languages as $language) {
                    $questionL10ns[] = [
                        'id' => count($questionL10ns) + 1,
                        'qid' => $qid,
                        'question' => $question->getTranslation('question', $language, false)
                            ?: $question->getTranslation('question', $baseLanguage, false),
                        'help' => $question->getTranslation('help', $language, false),
                        'script' => '',
                        'language' => $language,
                    ];
                }

                // Long free text gets a usable box instead of LimeSurvey's default height.
                if ($question->type === SurveyQuestionType::LongText) {
                    $attributeRows[] = [
                        'qid' => $qid,
                        'attribute' => 'display_rows',
                        'value' => '5',
                    ];
                }

                $sortOrder = 0;

                foreach ($question->normalizedOptions() as $option) {
                    $aid = $this->nextAid++;

                    $answerRows[] = [
                        'aid' => $aid,
                        'qid' => $qid,
                        'code' => $option['code'],
                        'sortorder' => $sortOrder++,
                        'assessment_value' => 0,
                        'scale_id' => 0,
                    ];

                    foreach ($languages as $language) {
                        $answerL10ns[] = [
                            'id' => count($answerL10ns) + 1,
                            'aid' => $aid,
                            'answer' => $option['label'][$language] ?? $option['label'][$baseLanguage] ?? $option['code'],
                            'language' => $language,
                        ];
                    }
                }

                $order++;
            }

            $gid++;
        }

        $this->writeSection($writer, 'questions', $questionRows);
        $this->writeSection($writer, 'question_l10ns', $questionL10ns);

        if ($answerRows !== []) {
            $this->writeSection($writer, 'answers', $answerRows);
            $this->writeSection($writer, 'answer_l10ns', $answerL10ns);
        }

        if ($attributeRows !== []) {
            $this->writeSection($writer, 'question_attributes', $attributeRows);
        }
    }

    /**
     * Write one <section><fields/><rows/></section> block.
     *
     * The <fields> list is taken from the first row, so every row in a section must carry
     * the same keys — LimeSurvey's importer maps them positionally by name.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeSection(XMLWriter $writer, string $section, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $writer->startElement($section);

        $writer->startElement('fields');
        foreach (array_keys($rows[0]) as $field) {
            $writer->writeElement('fieldname', $field);
        }
        $writer->endElement();

        $writer->startElement('rows');
        foreach ($rows as $row) {
            $writer->startElement('row');
            foreach ($row as $field => $value) {
                $writer->startElement($field);
                if ($value !== null && $value !== '') {
                    $writer->writeCdata($this->safeCdata((string) $value));
                }
                $writer->endElement();
            }
            $writer->endElement();
        }
        $writer->endElement();

        $writer->endElement();
    }

    /**
     * Neutralise the one sequence that can break out of a CDATA section.
     *
     * XMLWriter::writeCdata does not escape this for us, and question text is author input
     * — a stray "]]>" would otherwise produce a document LimeSurvey rejects, or worse,
     * silently misparses.
     */
    private function safeCdata(string $value): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $value);
    }
}
