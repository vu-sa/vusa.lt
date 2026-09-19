import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useActionWindowCatalog } from '@/Composables/useActionWindowCatalog';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const action = (key: string, target: { kind: 'route'; routeName: string } | { kind: 'screen'; screen: string }) => ({
  key,
  label: `shell.actions.${key}.title`,
  description: null,
  entityType: null,
  target,
});

const catalog = (actions: ReturnType<typeof action>[]) => ({
  workspaces: [{ key: 'test', label: 'shell.workspaces.pradzia.title', description: '', sections: [], createActions: actions }],
});

const resolveCatalog = () => {
  let resolved!: ReturnType<typeof useActionWindowCatalog>;
  mount(defineComponent({ setup: () => { resolved = useActionWindowCatalog(); return () => h('div'); } }));
  return resolved;
};

describe('useActionWindowCatalog', () => {
  it('has no actions when the server supplies no create actions', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: { workspaces: [] } }));
    expect(resolveCatalog().personas.value).toEqual([]);
  });

  it('uses the server catalog to choose actions and preserves their targets', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ adminNavigation: catalog([
      action('new_meeting', { kind: 'screen', screen: 'meeting.institution' }),
      action('new_problem', { kind: 'route', routeName: 'problems.create' }),
      action('new_news', { kind: 'route', routeName: 'news.create' }),
      action('duty_periods', { kind: 'route', routeName: 'dutiables.timeline' }),
    ]) }));

    const resolved = resolveCatalog();
    expect(resolved.personas.value.map(persona => persona.key)).toEqual(['representative', 'member', 'coordinator']);
    expect(resolved.findPersona('representative')?.actions.map(item => item.key)).toEqual(['new_meeting', 'new_problem']);
    expect(resolved.findPersona('coordinator')?.actions.map(item => item.key)).toEqual(['new_news', 'duty_periods']);
    expect(resolved.findPersona('coordinator')?.actions[1]?.target).toEqual({ kind: 'route', route: 'dutiables.timeline' });
  });
});
