import { describe, expect, it } from 'vitest';

import {
  adminCollectionToKey,
  collectAllTabHits,
  COLLECTION_META,
  formatSearchDate,
  formatSearchDateTime,
  normalizeHit,
} from '../searchHitMappers';

import type { MultiSearchResults } from '@/Shared/Search/types';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';

describe('adminCollectionToKey', () => {
  it('maps snake_case agenda_items to the camelCase result key', () => {
    expect(adminCollectionToKey('agenda_items')).toBe('agendaItems');
  });

  it('passes through other collections unchanged', () => {
    expect(adminCollectionToKey('meetings')).toBe('meetings');
    expect(adminCollectionToKey('institutions')).toBe('institutions');
    expect(adminCollectionToKey('resources')).toBe('resources');
  });
});

describe('formatSearchDate', () => {
  it('returns undefined for missing timestamps', () => {
    expect(formatSearchDate(undefined)).toBeUndefined();
    expect(formatSearchDate(0)).toBeUndefined();
  });

  it('formats a unix (seconds) timestamp', () => {
    expect(typeof formatSearchDate(1_700_000_000)).toBe('string');
  });
});

describe('formatSearchDateTime', () => {
  it('returns undefined for missing timestamps', () => {
    expect(formatSearchDateTime(undefined)).toBeUndefined();
    expect(formatSearchDateTime(0)).toBeUndefined();
  });

  it('formats a unix (seconds) timestamp with time', () => {
    const formatted = formatSearchDateTime(1_700_000_000);
    expect(typeof formatted).toBe('string');
    expect(formatted).toContain(':');
  });
});

describe('normalizeHit', () => {
  it('normalizes a meeting hit', () => {
    const doc = {
      id: '7',
      title: 'Senato posėdis',
      institution_name_lt: 'VU Senatas',
      tenant_shortname: 'VU SA',
      start_time: 1_700_000_000,
      completion_status: 'complete',
    };
    const hit = normalizeHit('meetings', doc);

    expect(hit.id).toBe('meetings-7');
    expect(hit.recordId).toBe('7');
    expect(hit.collection).toBe('meetings');
    expect(hit.title).toBe('Senato posėdis');
    expect(hit.subtitle).toBe('VU Senatas');
    expect(hit.badge).toBe('VU SA');
    expect(hit.href).toContain('meetings.show');
    expect(hit.icon).toBe(COLLECTION_META.meetings.icon);
    expect(hit.raw).toBe(doc);
    expect(hit.statusBadge).toEqual({ label: 'Užbaigtas', tone: 'success' });
  });

  it('links an agenda item to its edit page and uses institution as subtitle', () => {
    const hit = normalizeHit('agendaItems', { id: '3', title: 'Punktas', meeting_title: 'M', meeting_id: '9', institution_name_lt: 'VU Senatas' });

    expect(hit.id).toBe('agendaItems-3');
    expect(hit.subtitle).toBe('VU Senatas');
    expect(hit.href).toContain('agendaItems.edit');
  });

  it('joins resource location and category in the subtitle', () => {
    const hit = normalizeHit('resources', { id: 'r1', name_lt: 'Projektorius', location: 'A101', category_name: 'Technika' });

    expect(hit.title).toBe('Projektorius');
    expect(hit.subtitle).toBe('A101 • Technika');
  });

  it('uses the anonymous_url for documents', () => {
    const hit = normalizeHit('documents', { id: 'd1', title: 'Doc', anonymous_url: 'https://files.example/d1.pdf' });

    expect(hit.href).toBe('https://files.example/d1.pdf');
  });

  it('falls back to a placeholder title when missing', () => {
    const hit = normalizeHit('institutions', { id: 'i1' });

    expect(hit.title).toBeTruthy();
  });

  describe('duties', () => {
    const baseDuty = {
      id: 'du1',
      name_lt: 'Pirmininkas',
      institution_name_lt: 'VU SA MIF',
      tenant_shortname: 'MIF',
      home_tenant_id: 2,
      type_titles: ['Koordinatorius'],
    };

    it('maps a duty hit with institution subtitle and type meta', () => {
      const hit = normalizeHit('duties', baseDuty);

      expect(hit.id).toBe('duties-du1');
      expect(hit.title).toBe('Pirmininkas');
      expect(hit.subtitle).toBe('VU SA MIF');
      expect(hit.meta).toBe('Koordinatorius');
      expect(hit.href).toContain('duties.show');
    });

    it('flags a cross-tenant duty and adds the subtle badge', () => {
      const hit = normalizeHit('duties', baseDuty, { ownTenantIds: [1], isSuperAdmin: false });

      expect(hit.isExternal).toBe(true);
      expect(hit.statusBadge).toEqual({ label: 'MIF', tone: 'info' });
    });

    it('does not flag a duty within the user tenants', () => {
      const hit = normalizeHit('duties', baseDuty, { ownTenantIds: [2], isSuperAdmin: false });

      expect(hit.isExternal).toBe(false);
      expect(hit.statusBadge).toBeUndefined();
    });

    it('never flags duties as external for super admins', () => {
      const hit = normalizeHit('duties', baseDuty, { ownTenantIds: [], isSuperAdmin: true });

      expect(hit.isExternal).toBe(false);
    });
  });
});

describe('collectAllTabHits', () => {
  /** Build a MultiSearchResults with only the keys we care about populated. */
  function makeResults(partial: Partial<Omit<MultiSearchResults, 'counts'>>): MultiSearchResults {
    return {
      ...createEmptyMultiSearchResults(),
      ...partial,
    };
  }

  // Real-world int64 scores: these are EQUAL when compared as JS numbers
  // (precision loss past Number.MAX_SAFE_INTEGER), so this guards the BigInt fix.
  const meetingScore = '1157451471441100834';
  const institutionScore = '1157451471441100922';
  const meeting = { id: 'm1', title: 'Studijų kolegijos posėdis', start_time: 1_700_000_000, _text_match: meetingScore } as never;
  const institution = { id: 'i1', name_lt: 'Studijų kolegija', created_at: 1_500_000_000, _text_match: institutionScore } as never;

  it('treats the two scores as a precision trap when compared as JS numbers', () => {
    // Sanity check: the bug this test guards against — naive number comparison ties.
    expect(Number(meetingScore)).toBe(Number(institutionScore));
    expect(BigInt(institutionScore) > BigInt(meetingScore)).toBe(true);
  });

  it('interleaves collections by relevance (text_match) when a query is present', () => {
    const results = makeResults({ meetings: [meeting], institutions: [institution] });

    const hits = collectAllTabHits(results, { query: 'Studijų kolegija' });

    // Institution scores higher (BigInt), so it must lead despite meetings ranking
    // first in the static order AND despite the meeting being far more recent.
    expect(hits[0].collection).toBe('institutions');
    expect(hits[1].collection).toBe('meetings');
  });

  it('falls back to recency as a tiebreak when relevance is equal', () => {
    const older = { id: 'm1', title: 'A', start_time: 1_600_000_000, _text_match: 80 } as never;
    const newer = { id: 'm2', title: 'B', start_time: 1_700_000_000, _text_match: 80 } as never;
    const results = makeResults({ meetings: [older, newer] });

    const hits = collectAllTabHits(results, { query: 'posėdis' });

    expect(hits[0].recordId).toBe('m2');
    expect(hits[1].recordId).toBe('m1');
  });

  it('uses the static collection order when there is no query (browse mode)', () => {
    const results = makeResults({ meetings: [meeting], institutions: [institution] });

    const hits = collectAllTabHits(results, { query: '' });

    // No query: meetings precede institutions regardless of relevance score.
    expect(hits[0].collection).toBe('meetings');
    expect(hits[1].collection).toBe('institutions');
  });
});
