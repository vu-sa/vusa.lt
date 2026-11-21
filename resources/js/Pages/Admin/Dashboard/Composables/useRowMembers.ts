/**
 * Member history (coverage bars, activity rings) for the Padaliniai Gantt rows, fetched per batch of
 * rows in display order: the rows on screen first, then outward. The rows themselves arrive without it.
 */
import { ref, shallowRef } from 'vue';

import type { RepresentativeActivityCategory } from '@/Components/Graphs/types';

import type { AtstovavimasInstitution, AtstovavimasMembershipPeriod } from '../types';

/** Matches the meetings store, so one scroll position asks both endpoints for the same rows. */
const BATCH_SIZE = 20;
const CONCURRENCY = 2;

interface MembersResponse {
  success?: boolean;
  data?: {
    users: Record<string, { name: string; profile_photo_path: string | null }>;
    institutions: Record<string, {
      periods: Array<{ user_id: string; duty_id: string; start_date: string; end_date: string | null }>;
      activity?: Record<string, RepresentativeActivityCategory>;
    }>;
  };
}

interface RowMembers {
  periods: AtstovavimasMembershipPeriod[];
  activity?: Record<string, RepresentativeActivityCategory>;
}

export function useRowMembers(tenantIds: () => Array<string | number>) {
  const byInstitution = shallowRef(new Map<string, RowMembers>());
  const inFlight = ref(0);
  /** Loaded or being fetched; a failed batch is removed so the next report retries it. */
  const requested = new Set<string>();

  let rows: string[] = [];
  let visible = { first: 0, last: -1 };
  let queue: string[][] = [];
  let generation = 0;

  function plan(): string[][] {
    const batches: string[][] = [];
    for (let i = 0; i < rows.length; i += BATCH_SIZE) batches.push(rows.slice(i, i + BATCH_SIZE));
    const first = Math.floor(visible.first / BATCH_SIZE);
    const last = Math.floor(visible.last / BATCH_SIZE);
    const distance = (i: number) => (i < first ? first - i : i > last ? i - last : 0);

    return batches
      .map((batch, index) => ({ index, ids: batch.filter(id => !requested.has(id)) }))
      .filter(batch => batch.ids.length > 0)
      .sort((a, b) => distance(a.index) - distance(b.index) || a.index - b.index)
      .map(batch => batch.ids);
  }

  async function fetchBatch(ids: string[]): Promise<void> {
    const tenants = tenantIds().map(String);
    if (tenants.length === 0) return;

    const params = new URLSearchParams();
    tenants.forEach(id => params.append('tenant_ids[]', id));
    ids.forEach(id => params.append('institution_ids[]', id));
    const generationAtStart = generation;

    const response = await fetch(`${route('api.v1.admin.visak.gantt.members')}?${params}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    const json = response.ok ? await response.json() as MembersResponse : null;
    if (!json?.success || !json.data) throw new Error('members');
    if (generationAtStart !== generation) return;

    const { users, institutions } = json.data;
    const map = new Map(byInstitution.value);
    for (const id of ids) {
      const entry = institutions[id];
      map.set(id, {
        periods: (entry?.periods ?? []).map(period => ({
          user: { id: period.user_id, name: users[period.user_id]?.name ?? '', profile_photo_path: users[period.user_id]?.profile_photo_path ?? null },
          duty_id: period.duty_id,
          start_date: period.start_date,
          end_date: period.end_date,
        })),
        activity: entry?.activity,
      });
    }
    byInstitution.value = map;
  }

  function pump(): void {
    while (inFlight.value < CONCURRENCY && queue.length > 0) {
      const ids = queue.shift()!;
      ids.forEach(id => requested.add(id));
      inFlight.value += 1;
      fetchBatch(ids)
        .catch(() => ids.forEach(id => requested.delete(id)))
        .finally(() => {
          inFlight.value -= 1;
          pump();
        });
    }
  }

  /** Same signal as the meetings store: institution ids in display order and the slice on screen. */
  function setRows(orderedIds: Array<string | number>, firstVisible: number, lastVisible: number): void {
    rows = orderedIds.map(String);
    visible = { first: firstVisible, last: lastVisible };
    queue = plan();
    pump();
  }

  /** Only the rows whose member history has arrived, in the shape the Gantt helpers read. */
  function withHistory<T extends AtstovavimasInstitution>(institutions: T[]): T[] {
    const loaded = byInstitution.value;
    return institutions.flatMap((institution) => {
      const members = loaded.get(String(institution.id));
      return members ? [{ ...institution, membership_periods: members.periods, member_activity_categories: members.activity }] : [];
    });
  }

  /** Rows still waiting for their history; the chart must not read their empty member list as "nobody". */
  function pendingIds(institutions: AtstovavimasInstitution[]): string[] {
    const loaded = byInstitution.value;
    return institutions.map(institution => String(institution.id)).filter(id => !loaded.has(id));
  }

  function reset(): void {
    generation += 1;
    queue = [];
    rows = [];
    visible = { first: 0, last: -1 };
    requested.clear();
    byInstitution.value = new Map();
  }

  /** After an edit: forget what was loaded and load the current rows again, on-screen first. */
  function refresh(): void {
    const keptRows = rows;
    const keptVisible = visible;
    reset();
    rows = keptRows;
    visible = keptVisible;
    queue = plan();
    pump();
  }

  return { byInstitution, inFlight, setRows, withHistory, pendingIds, reset, refresh };
}
