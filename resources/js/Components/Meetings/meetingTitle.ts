/** Generated titles open with the date; only a custom one tells a reader something the date column does not. */
export const titleOmitsDate = (title: string | null | undefined): boolean => Boolean(title) && !/\d{4}-\d{2}-\d{2}/.test(title ?? '');
