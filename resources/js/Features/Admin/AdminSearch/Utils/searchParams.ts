/**
 * Helpers for building Typesense search parameters shared across admin search
 * composables.
 */

/**
 * Schema fields that have infix indexing enabled per collection, mirroring the
 * `infix` flags in `config/scout.php`. Keyed by the snake_case collection name.
 * Requesting infix on a field without an infix index errors, so any query_by
 * field not listed here must be sent as `off`.
 */
export const INFIX_FIELDS: Record<string, Set<string>> = {
  meetings: new Set(['title', 'description', 'institution_names']),
  agenda_items: new Set(['title', 'description', 'student_benefit', 'meeting_title']),
  news: new Set(['title', 'short']),
  pages: new Set(['title', 'meta_description']),
  calendar: new Set(['title', 'title_lt', 'title_en']),
  institutions: new Set(['name_lt', 'name_en', 'short_name_lt', 'short_name_en', 'alias']),
  documents: new Set(['title', 'summary', 'name', 'content_type', 'document_year', 'document_date_formatted']),
  resources: new Set(['name_lt', 'name_en', 'description_lt', 'description_en', 'location']),
  duties: new Set(['name_lt', 'name_en', 'institution_name_lt', 'institution_name_en']),
};

/**
 * Build the Typesense `infix` parameter for a `query_by` field list.
 *
 * Returns a comma-separated value per query_by field: `fallback` for fields with
 * an infix index (only used when a normal search returns no hits, so the cost is
 * negligible) and `off` for the rest. Returns an empty string for unknown
 * collections so callers can skip the param.
 */
export function buildInfix(queryBy: string, collection: string): string {
  const infixFields = INFIX_FIELDS[collection];
  if (!infixFields) {
    return '';
  }

  return queryBy
    .split(',')
    .map(field => (infixFields.has(field.trim()) ? 'fallback' : 'off'))
    .join(',');
}
