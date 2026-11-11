import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import LearningGlossarySteps from '../LearningGlossarySteps.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('Glossary step relations', () => {
  it('shows where the term appears with one-click document and task links', () => {
    const wrapper = mount(LearningGlossarySteps, {
      props: { steps: [
        { id: 'document', title: 'Senato reglamentas', url: route('knowledgeItems.show', 'document') },
        { id: 'task', title: 'Pasiruošk posėdžiui', url: route('knowledgeItems.show', 'task') },
      ] },
    });
    expect(wrapper.text()).toContain('learning.glossary.where_used');
    expect(wrapper.findAll('a').map(link => link.attributes('href'))).toEqual([
      route('knowledgeItems.show', 'document'), route('knowledgeItems.show', 'task'),
    ]);
    wrapper.unmount();
  });
});
