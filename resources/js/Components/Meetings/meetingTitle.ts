/** Generated titles open with the date; only a custom one tells a reader something the date column does not. */
export const titleOmitsDate = (title: string | null | undefined): boolean => Boolean(title) && !/\d{4}-\d{2}-\d{2}/.test(title ?? '');

// `App\Support\MeetingTitle`: "2026 balandžio 23 d. 15.00 val. posėdis" / "23 April 2026 15.00 sitting".
const GENERATED_DATE = /^(?:\d{4} \S+ \d{1,2} d\.(?: \d{2}\.\d{2} val\.)?|\d{1,2} \S+ \d{4}(?: \d{2}\.\d{2})?) /;

/** A generated title without its date, for a place that already shows it ("Posėdis"); a custom title as it is. */
export const titleWithoutGeneratedDate = (title: string | null | undefined): string | null => {
  if (!title?.trim()) {
    return null;
  }

  const rest = title.replace(GENERATED_DATE, '');

  return rest === title ? title : rest.charAt(0).toLocaleUpperCase('lt') + rest.slice(1);
};
