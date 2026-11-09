import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { createMockPage } from '@/tests/helpers/createMockPage';
import LearningTabs from '../LearningTabs.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function render(canSeeReps: boolean, url = '/mano/learning', canManage = false) {
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url });
  return mount(LearningTabs, {
    props: { canSeeReps, canManage },
    global: { stubs: { Link: { props: ['href'], template: '<a :href="href"><slot /></a>' } } },
  });
}

describe('LearningTabs', () => {
  it('hides representatives from members and shows them to coordinators', () => {
    const member = render(false);
    expect(member.findAll('a')).toHaveLength(4);
    member.unmount();
    const coordinator = render(true);
    expect(coordinator.findAll('a')).toHaveLength(5);
    coordinator.unmount();
  });

  it('hides Manage from members and keeps Documents before Glossary', () => {
    const member = render(false);
    expect(member.text()).not.toContain('learning.tabs.library');
    expect(member.findAll('a').map(link => link.text())).toEqual(['learning.tabs.path', 'learning.tabs.documents', 'learning.tabs.glossary', 'learning.tabs.handover']);
    member.unmount();
    const curator = render(true, '/mano/learning/documents', true);
    expect(curator.findAll('a').map(link => link.text())).toEqual(['learning.tabs.path', 'learning.tabs.documents', 'learning.tabs.glossary', 'learning.tabs.handover', 'learning.tabs.reps', 'learning.tabs.library']);
    expect(curator.get('[aria-current="page"]').text()).toBe('learning.tabs.documents');
    curator.unmount();
  });

  it('keeps the library active on a step record', () => {
    const wrapper = render(false, '/mano/learning/steps/01STEP', true);
    expect(wrapper.get('[aria-current="page"]').attributes('href')).toContain('learningSteps.index');
    expect(wrapper.findAll('[aria-current="page"]')).toHaveLength(1);
    wrapper.unmount();
  });

  it('keeps representatives active on an individual representative path', () => {
    const wrapper = render(true, '/mano/learning/reps/01REP');
    expect(wrapper.get('[aria-current="page"]').attributes('href')).toContain('learningPath.reps');
    wrapper.unmount();
  });
});
