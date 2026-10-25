import { describe, expect, it } from 'vitest';

import { newsStatus } from '../newsStatus';

import { contentStatuses } from '@/Constants/statuses';
import type { NewsSearchResult } from '@/Shared/Search/types';

const news = (overrides: Partial<NewsSearchResult>): NewsSearchResult => ({
  id: '1',
  title: 'Naujiena',
  lang: 'lt',
  draft: false,
  ...overrides,
});

describe('newsStatus', () => {
  const now = Date.UTC(2026, 8, 23);

  it('shows drafts even when their publish time is in the future', () => {
    expect(newsStatus(news({ draft: true, publish_time: (now + 86_400_000) / 1000 }), now)).toBe(contentStatuses.draft);
  });

  it('shows published articles with a future publish time as scheduled', () => {
    expect(newsStatus(news({ publish_time: (now + 86_400_000) / 1000 }), now)).toBe(contentStatuses.scheduled);
  });

  it('shows published articles once their publish time arrives', () => {
    expect(newsStatus(news({ publish_time: now / 1000 }), now)).toBe(contentStatuses.published);
  });
});
