import type { MultiSearchResults } from '../types';

/**
 * Return a fully-populated empty MultiSearchResults object.
 *
 * This is the single source of truth for the default shape so that every
 * consumer (command palette, search page, tests, etc.) stays in sync when
 * a new collection is added to MultiSearchResults.
 */
export function createEmptyMultiSearchResults(): MultiSearchResults {
  return {
    meetings: [],
    agendaItems: [],
    news: [],
    pages: [],
    calendar: [],
    institutions: [],
    documents: [],
    resources: [],
    duties: [],
    users: [],
    counts: {
      meetings: 0,
      agendaItems: 0,
      news: 0,
      pages: 0,
      calendar: 0,
      institutions: 0,
      documents: 0,
      resources: 0,
      duties: 0,
      users: 0,
    },
  };
}
