import { describe, it, expect, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useCommandActions } from '@/Components/CommandPalette/useCommandActions';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

/**
 * Regression guard: the backend shares `auth.can.index` (viewAny), never `auth.can.read` —
 * gating on the latter meant every navigation entry below silently never appeared in the
 * palette, for any user, ever.
 */
function actionIds(can: Record<string, unknown>): string[] {
  vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can } }));

  let ids!: string[];

  mount(defineComponent({
    setup() {
      const { actions } = useCommandActions();
      ids = actions.value.map(action => action.id);
      return () => h('div');
    },
  }));

  return ids;
}

describe('useCommandActions', () => {
  it('gates every navigation entry on auth.can.index, not auth.can.read', () => {
    const ids = actionIds({
      index: { meeting: true, institution: true, user: true, duty: true, task: true, reservation: true, calendar: true, news: true },
    });

    expect(ids).toEqual(expect.arrayContaining([
      'nav-meetings', 'nav-institutions', 'nav-users', 'nav-duties',
      'nav-tasks', 'nav-reservations', 'nav-calendar', 'nav-news',
    ]));
  });

  it('hides every navigation entry when auth.can.index grants nothing', () => {
    const ids = actionIds({ index: {} });

    expect(ids).not.toEqual(expect.arrayContaining([
      'nav-meetings', 'nav-institutions', 'nav-users', 'nav-duties',
      'nav-tasks', 'nav-reservations', 'nav-calendar', 'nav-news',
    ]));
    // Always-available entries survive regardless.
    expect(ids).toEqual(expect.arrayContaining(['nav-dashboard', 'nav-representation', 'nav-search', 'nav-profile']));
  });

  it('a legacy auth.can.read map (the old, wrong shape) no longer has any effect', () => {
    const ids = actionIds({
      read: { meeting: true, institution: true, user: true, duty: true, task: true, reservation: true, calendar: true, news: true },
    });

    expect(ids).not.toEqual(expect.arrayContaining(['nav-meetings', 'nav-institutions']));
  });
});
