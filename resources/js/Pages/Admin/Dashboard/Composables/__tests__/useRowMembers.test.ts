import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises } from '@vue/test-utils';

import { useRowMembers } from '../useRowMembers';
import type { AtstovavimasInstitution } from '../../types';

const rows = Array.from({ length: 50 }, (_, i) => `inst-${String(i).padStart(2, '0')}`);
const urls: string[] = [];
let failNext = false;

function respond(url: string): Response {
  const ids = new URL(url, 'http://localhost').searchParams.getAll('institution_ids[]');
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
          periods: [{ user_id: 'u1', duty_id: 'd1', start_date: '2025-09-01', end_date: null }],
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
      return respond(String(url));
    }));
  });

  afterEach(() => {
    vi.stubGlobal('fetch', undefined);
  });

  it('loads the batch on screen first, then its neighbours, two at a time', async () => {
    const members = useRowMembers(() => ['1']);

    members.setRows(rows, 22, 30);
    expect(urls.map(url => idsOf(url)[0])).toEqual(['inst-20', 'inst-00']);

    await flushPromises();
    expect(urls.map(url => idsOf(url)[0])).toEqual(['inst-20', 'inst-00', 'inst-40']);
    expect(new URL(urls[0]!, 'http://localhost').searchParams.getAll('tenant_ids[]')).toEqual(['1']);
  });

  it('returns only rows with arrived history, in the shape the Gantt helpers read, and lists the rest as pending', async () => {
    const members = useRowMembers(() => ['1']);
    members.setRows(rows.slice(0, 5), 0, 4);
    await flushPromises();

    const rowsIn = [{ id: 'inst-00', name: 'A' } as AtstovavimasInstitution, { id: 'other', name: 'B' } as AtstovavimasInstitution];
    const withHistory = members.withHistory(rowsIn);
    expect(withHistory).toHaveLength(1);
    expect(members.pendingIds(rowsIn)).toEqual(['other']);
    const [row] = withHistory;
    expect(row!.membership_periods).toEqual([{ user: { id: 'u1', name: 'Lina', profile_photo_path: null }, duty_id: 'd1', start_date: '2025-09-01', end_date: null }]);
    expect(row!.member_activity_categories).toEqual({ u1: 'week' });
  });

  it('does not ask twice for loaded rows and retries a failed batch on the next report', async () => {
    const members = useRowMembers(() => ['1']);
    failNext = true;
    members.setRows(rows.slice(0, 5), 0, 4);
    await flushPromises();
    expect(urls).toHaveLength(1);

    members.setRows(rows.slice(0, 5), 0, 4);
    await flushPromises();
    members.setRows(rows.slice(0, 5), 0, 4);
    await flushPromises();

    expect(urls).toHaveLength(2);
  });
});
