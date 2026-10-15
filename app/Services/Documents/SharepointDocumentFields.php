<?php

namespace App\Services\Documents;

use App\Enums\SharepointFieldEnum;
use App\Models\Document;
use App\Models\Institution;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Maps a SharePoint archive list item's fields onto a Document. The one place that knows the
 * archive's column names — import, per-document sync and discovery all go through it.
 */
class SharepointDocumentFields
{
    /**
     * Graph leaves empty columns out of `fields` entirely, and callers always pass the full set, so an
     * absent column is a cleared one: the mirror follows SharePoint, it does not remember old values.
     *
     * @param  array<string, mixed>  $fields  list item `fields` (Graph additionalData)
     * @param  (Closure(string): ?Institution)|null  $resolveInstitution  lets a caller reuse lookups across many files
     */
    public static function apply(Document $document, array $fields, ?Closure $resolveInstitution = null): void
    {
        $document->title = self::string($fields, SharepointFieldEnum::TITLE) ?? $document->name ?? $document->title;
        $document->language = self::string($fields, SharepointFieldEnum::LANGUAGE);
        $document->summary = self::string($fields, SharepointFieldEnum::SUMMARY);
        $document->content_type = self::label($fields, SharepointFieldEnum::TURINYS);

        $document->document_date = self::date($fields, SharepointFieldEnum::DATE);
        $document->effective_date = self::date($fields, SharepointFieldEnum::EFFECTIVE_DATE);
        $document->expiration_date = self::date($fields, SharepointFieldEnum::EXPIRATION_DATE);

        $institutionLabel = self::label($fields, SharepointFieldEnum::PADALINYS);

        if ($institutionLabel !== null) {
            $document->sharepoint_institution_label = $institutionLabel;
            $document->institution()->associate($resolveInstitution ? $resolveInstitution($institutionLabel) : self::institutionFor($institutionLabel));
        } elseif ($document->sharepoint_institution_label !== null) {
            // Cleared in SharePoint. An institution never set from SharePoint (the old meeting import's
            // fallback) has no label and stays, so those documents keep their padalinys.
            $document->sharepoint_institution_label = null;
            $document->institution()->dissociate();
        }
    }

    /**
     * Metadata a manager should fill in SharePoint before publishing.
     *
     * @return list<'institution'|'unknown_institution'|'content_type'|'document_date'|'language'>
     */
    public static function problems(Document $document): array
    {
        $problems = [];

        if ($document->institution_id === null) {
            $problems[] = $document->sharepoint_institution_label ? 'unknown_institution' : 'institution';
        }

        if (blank($document->content_type)) {
            $problems[] = 'content_type';
        }

        if ($document->document_date === null) {
            $problems[] = 'document_date';
        }

        if (blank($document->language)) {
            $problems[] = 'language';
        }

        return $problems;
    }

    public static function institutionFor(string $label): ?Institution
    {
        return Institution::query()
            ->where('name->lt', $label)
            ->orWhere('short_name->lt', $label)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private static function string(array $fields, SharepointFieldEnum $field): ?string
    {
        $value = $fields[$field->label()] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private static function label(array $fields, SharepointFieldEnum $field): ?string
    {
        $value = $fields[$field->label()]['Label'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * SharePoint stores dates as UTC midnight of the Vilnius day.
     *
     * @param  array<string, mixed>  $fields
     */
    private static function date(array $fields, SharepointFieldEnum $field): ?Carbon
    {
        $value = self::string($fields, $field);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parseFromLocale(time: $value, timezone: 'UTC')->setTimezone('Europe/Vilnius');
        } catch (InvalidFormatException) {
            // Shown to managers as a missing date, which is what it is to the public site.
            Log::warning('Unreadable SharePoint date', ['field' => $field->label(), 'value' => $value]);

            return null;
        }
    }
}
