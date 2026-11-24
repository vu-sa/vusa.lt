import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { useRowMembers } from '../useRowMembers';
import type { AtstovavimasInstitution } from '../../types';

const rows = Array.from({ length: 360 }, (_, i) => `inst-${String(i).padStart(2, '0')}`);
const urls: string[] = [];
let failNext = false;

function respond(ids = rows): Response {
  if (failNext) {
    failNext = false;
    return { ok: false, json: async () => ({}) } as Response;
  }
  return {
    ok: true,
    json: async () => ({
      success: true,
      data: {
        users: { u1: { name: 'Lina', profile_photo_path: null } },
        institutions: Object.fromEntries(ids.map(id => [id, {
          periods: id === 'inst-01' ? [] : [{ user_id: 'u1', duty_id: 'd1', start_date: '2025-09-01', end_date: null }],
          activity: { u1: 'week' },
        }])),
      },
    }),
  } as Response;
}

const idsOf = (url: string) => new URL(url, 'http://localhost').searchParams.getAll('institution_ids[]');

describe('useRowMembers', () => {
  beforeEach(() => {
    urls.splice(0);
    failNext = false;
    vi.stubGlobal('fetch', vi.fn(async (url: string) => {
      urls.push(String(url));
      return respond();
    }));
  });

  afterEach(() => {
    vi.stubGlobal('fetch', undefined);
  });

  it('loads the selection in one request and preserves pending and empty history', async () => {
    const members = useRowMembers();
    await members.load(['1', '2']);
    expect(urls).toHaveLength(1);
    expect(idsOf(urls[0]!)).toEqual([]);
    expect(new URL(urls[0]!, 'http://localhost').searchParams.getAll('tenant_ids[]')).toEqual(['1', '2']);
    expect(members.pendingIds(rows.map(id => ({ id }) as AtstovavimasInstitution))).toEqual([]);

    const rowsIn = ['inst-00', 'inst-01', 'other'].map(id => ({ id }) as AtstovavimasInstitution);
    const withHistory = members.withHistory(rowsIn);
    expect(withHistory).toHaveLength(2);
    expect(withHistory[1]!.membership_periods).toEqual([]);
    expect(members.pendingIds(rowsIn)).toEqual(['other']);
    const [row] = withHistory;
    expect(row!.membership_periods).toEqual([{ user: { id: 'u1', name: 'Lina', profile_photo_path: null }, duty_id: 'd1', start_date: '2025-09-01', end_date: null }]);
    expect(row!.member_activity_categories).toEqual({ u1: 'week' });
  });

  it('retries a failure and passes the refresh flag after an edit', async () => {
    const members = useRowMembers();
    failNext = true;
    await members.load(['1']);
    expect(members.failed.value).toBe(true);

    await members.load(['1'], true);
    expect(members.failed.value).toBe(false);
    expect(urls).toHaveLength(2);
    expect(urls[1]).toContain('refresh=1');
  });

  it('ignores a response completed after the selection changes', async () => {
    const members = useRowMembers();
    let resolveOld!: (response: Response) => void;
    vi.mocked(fetch).mockImplementationOnce(() => new Promise(resolve => { resolveOld = resolve; }));
    const obsolete = members.load(['1']);
    await members.load(['2']);
    resolveOld(respond([]));
    await obsolete;

    expect(members.pendingIds([{ id: 'inst-00' } as AtstovavimasInstitution])).toEqual([]);
  });
});
