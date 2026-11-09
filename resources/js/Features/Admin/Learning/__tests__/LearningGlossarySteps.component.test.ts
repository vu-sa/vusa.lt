import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import LearningGlossarySteps from '../LearningGlossarySteps.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('Glossary step relations', () => {
  it('shows where the term appears with one-click document and task links', () => {
    const wrapper = mount(LearningGlossarySteps, {
      props: { steps: [
        { id: 'document', title: 'Senato reglamentas', url: 'https://example.test/reglamentas', isDocument: true },
        { id: 'task', title: 'Pasiruošk posėdžiui', url: null, isDocument: false },
      ] },
    });
    expect(wrapper.text()).toContain('learning.glossary.where_used');
    expect(wrapper.get('a[target="_blank"]').text()).toBe('Senato reglamentas');
    expect(wrapper.get('a[target="_blank"]').attributes('href')).toBe('https://example.test/reglamentas');
    expect(wrapper.get('a[target="_blank"]').attributes('rel')).toBe('noopener noreferrer');
    expect(wrapper.findAll('a').some(link => link.attributes('href')?.includes('learningSteps.show'))).toBe(true);
    wrapper.unmount();
  });
});
