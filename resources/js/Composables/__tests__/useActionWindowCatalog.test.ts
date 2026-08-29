import { describe, it, expect, beforeEach, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useActionWindowCatalog } from '@/Composables/useActionWindowCatalog';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

/**
 * The catalogue is the authorization surface of the action window: an action the
 * user cannot perform must never be offered, because the whole point of the
 * window is that a first-time user can trust every button in it.
 */

/** createMockPage deep-merges, so every flag has to be stated explicitly. */
const ALL_DENIED = {
  create: { meeting: false, problem: false, reservation: false, duty: false },
  index: { duty: false },
};

const withPermissions = (can: Record<string, unknown>) => {
  vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { ...ALL_DENIED, ...can } } }));

  let catalog!: ReturnType<typeof useActionWindowCatalog>;

  mount(defineComponent({
    setup() {
      catalog = useActionWindowCatalog();
      return () => h('div');
    },
  }));

  return catalog;
};

const actionKeys = (catalog: ReturnType<typeof useActionWindowCatalog>, persona: string) =>
  catalog.personas.value.find(p => p.key === persona)?.actions.map(a => a.key) ?? [];

describe('useActionWindowCatalog', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReset();
  });

  it('offers nothing at all to a user with no create permissions', () => {
    const catalog = withPermissions({});

    expect(catalog.personas.value).toEqual([]);
    expect(catalog.hasAnyAction.value).toBe(false);
  });

  it('gives a meeting-capable user the three representative meeting actions', () => {
    const catalog = withPermissions({ create: { ...ALL_DENIED.create, meeting: true } });

    expect(catalog.personas.value.map(p => p.key)).toEqual(['representative']);
    expect(actionKeys(catalog, 'representative')).toEqual(['new_meeting', 'no_meeting', 'complete_meeting']);
  });

  it('offers reporting a problem to representatives and members alike', () => {
    const catalog = withPermissions({ create: { ...ALL_DENIED.create, meeting: true, problem: true } });

    expect(actionKeys(catalog, 'representative')).toContain('new_problem');
    expect(actionKeys(catalog, 'member')).toEqual(['new_problem']);
  });

  it('hides the coordinator persona from someone who can only create meetings', () => {
    const catalog = withPermissions({ create: { ...ALL_DENIED.create, meeting: true } });

    expect(catalog.findPersona('coordinator')).toBeUndefined();
  });

  /**
   * The duty-period timeline authorizes viewAny(Duty), which is `can.index.duty` —
   * not creating a duty, and not manage-settings, which almost nobody who needs the
   * page actually holds.
   */
  it('gates the duty-period timeline on viewAny(Duty), not on creating one', () => {
    const createOnly = withPermissions({ create: { ...ALL_DENIED.create, duty: true } });
    expect(actionKeys(createOnly, 'coordinator')).toEqual(['duty_update']);

    const canView = withPermissions({ create: { ...ALL_DENIED.create, duty: true }, index: { duty: true } });
    expect(actionKeys(canView, 'coordinator')).toEqual(['duty_update', 'cadences']);
  });

  it('offers the timeline on its own to someone who may read duties but not create them', () => {
    const catalog = withPermissions({ index: { duty: true } });

    expect(actionKeys(catalog, 'coordinator')).toEqual(['cadences']);
  });

  it('shows every persona to a user who can do everything', () => {
    const catalog = withPermissions({
      create: { meeting: true, problem: true, reservation: true, duty: true },
      index: { duty: true },
    });

    expect(catalog.personas.value.map(p => p.key)).toEqual(['representative', 'member', 'coordinator']);
    expect(catalog.hasAnyAction.value).toBe(true);
  });

  it('routes link-out actions to real admin pages', () => {
    const catalog = withPermissions({
      create: { ...ALL_DENIED.create, problem: true, reservation: true, duty: true },
      index: { duty: true },
    });

    const targets = catalog.personas.value
      .flatMap(persona => persona.actions)
      .filter(action => action.target.kind === 'route')
      .map(action => (action.target as { route: string }).route);

    expect(new Set(targets)).toEqual(new Set([
      'problems.create',
      'reservations.create',
      'duties.updateUsersWizard',
      'dutiables.timeline',
    ]));
  });
});
