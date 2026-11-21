import { useDebounceFn } from '@vueuse/core';

import { trackEvent } from '@/Plugins/umami';

/**
 * How long typing must settle before a term is reported. Searches run behind a much shorter
 * debounce so results feel live; reporting on that cadence would record every keystroke
 * fragment ("v", "vu", "vusa") and bury the terms people meant.
 */
const REPORT_SETTLE_MS = 2000;

/** Search terms are free text, so they are capped before leaving the browser. */
const MAX_REPORTED_TERM_LENGTH = 60;

/** Which search surface the term was typed into — `/paieska` or `/dokumentai`. */
export type SearchReportSource = 'global' | 'documents';

/**
 * Reports settled searches as `search_submitted` so we learn what people look for — and, when
 * `results` is 0, what they fail to find. Only the term, result count and source are sent; see
 * the privacy note on the `/privatumas` page.
 */
export const createSearchReporter = (source: SearchReportSource) => {
  // Re-running the same term (a filter toggle) would otherwise inflate its apparent popularity.
  let lastReportedTerm = '';

  const debouncedReport = useDebounceFn((term: string, results: number): void => {
    if (term === lastReportedTerm) {
      return;
    }

    lastReportedTerm = term;
    trackEvent('search_submitted', { term, results, source });
  }, REPORT_SETTLE_MS);

  return {
    report: (term: string, results: number): void => {
      void debouncedReport(term.trim().toLowerCase().slice(0, MAX_REPORTED_TERM_LENGTH), results);
    },
    /** Sends a pending report now — submitting is an explicit signal that needs no settling. */
    flush: (): void => debouncedReport.flush(),
    /** Lets the next search report the same term again, e.g. after the box was cleared. */
    reset: (): void => {
      lastReportedTerm = '';
    },
  };
};
