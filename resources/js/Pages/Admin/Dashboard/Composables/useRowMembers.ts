import { getCurrentScope, onScopeDispose, ref, shallowRef } from 'vue';

import type { RepresentativeActivityCategory } from '@/Components/Graphs/types';

import type { AtstovavimasInstitution, AtstovavimasMembershipPeriod } from '../types';

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

export function useRowMembers() {
  const byInstitution = shallowRef(new Map<string, RowMembers>());
  const failed = ref(false);
  let refreshOnRetry = false;
  let controller: AbortController | undefined;

  async function load(tenantIds: string[], refresh = false): Promise<void> {
    reset();
    if (!tenantIds.length) return;

    refreshOnRetry = refresh;
    const request = new AbortController();
    controller = request;
    const params = new URLSearchParams();
    tenantIds.forEach(id => params.append('tenant_ids[]', id));
    if (refresh) params.set('refresh', '1');

    try {
      const response = await fetch(`${route('api.v1.admin.visak.gantt.members')}?${params}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal: request.signal,
      });
      const json = response.ok ? await response.json() as MembersResponse : null;
      if (!json?.success || !json.data) throw new Error('Unexpected member history response');
      if (request.signal.aborted) return;

      const { users, institutions } = json.data;
      const map = new Map<string, RowMembers>();
      for (const [id, entry] of Object.entries(institutions)) {
        map.set(id, {
          periods: entry.periods.map(period => ({
            user: { id: period.user_id, name: users[period.user_id]?.name ?? '', profile_photo_path: users[period.user_id]?.profile_photo_path ?? null },
            duty_id: period.duty_id,
            start_date: period.start_date,
            end_date: period.end_date,
          })),
          activity: entry.activity,
        });
      }
      byInstitution.value = map;
    }
    catch {
      if (!request.signal.aborted) failed.value = true;
    }
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

  function retry(tenantIds: string[]): void {
    if (failed.value) void load(tenantIds, refreshOnRetry);
  }

  function reset(): void {
    controller?.abort();
    controller = undefined;
    byInstitution.value = new Map();
    failed.value = false;
  }

  if (getCurrentScope()) onScopeDispose(reset);

  return { failed, load, retry, withHistory, pendingIds, reset };
}
