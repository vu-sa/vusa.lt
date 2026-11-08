import { describe, expect, it } from 'vitest';

import { contentStatuses } from '@/Constants/statusIcons';
import type { PageSearchResult } from '@/Shared/Search/types';

import { pageStatus } from '../pageStatus';

const page = (overrides: Partial<PageSearchResult>): PageSearchResult => ({
  id: '1',
  title: 'Puslapis',
  lang: 'lt',
  is_active: true,
  ...overrides,
});

describe('pageStatus', () => {
  it('reads a page as published or draft from is_active alone', () => {
    expect(pageStatus(page({ is_active: true }))).toBe(contentStatuses.published);
    expect(pageStatus(page({ is_active: false }))).toBe(contentStatuses.draft);
  });
});
