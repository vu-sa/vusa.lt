import { ref } from 'vue';
import { mount, flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ChecklistSummary from '../ChecklistSummary.vue';
import { checklistFixture } from './fixtures';

import { useApiMutation } from '@/Composables/useApi';

vi.mock('@/Composables/useApi', () => ({ useApiMutation: vi.fn() }));
vi.mock('@/Composables/useFeatureSpotlight', () => ({ useFeatureSpotlight: () => ({ isDismissed: ref(true), dismiss: vi.fn() }) }));

beforeEach(() => vi.clearAllMocks());

const mountSummary = (checklist = checklistFixture()) => mount(ChecklistSummary, {
  props: { checklist }, global: { stubs: { SpotlightPopover: { template: '<div><slot /></div>' } } },
});

describe('ChecklistSummary', () => {
  it('shows the next unfinished step and its checklist link', () => {
    const wrapper = mountSummary(checklistFixture({ access: 'done' }));
    expect(wrapper.text()).toContain('onboarding.items.contacts.title');
    expect(wrapper.find('a').attributes('href')).toBe('/mano/onboarding?item=contacts');
  });

  it('does not render a hidden or completed checklist', () => {
    expect(mountSummary({ ...checklistFixture(), hidden: true }).find('[data-slot="onboarding-summary"]').exists()).toBe(false);
    expect(mountSummary({ ...checklistFixture(), complete: true }).find('[data-slot="onboarding-summary"]').exists()).toBe(false);
  });

  it('hides the summary only once the server confirms its dismissal', async () => {
    vi.mocked(useApiMutation).mockReturnValue({
      execute: vi.fn().mockResolvedValue(undefined), isSuccess: ref(true), data: ref({ ...checklistFixture(), hidden: true }),
    } as never);
    const wrapper = mountSummary();
    await wrapper.findAll('button').at(-1)!.trigger('click');
    await flushPromises();
    expect(wrapper.find('[data-slot="onboarding-summary"]').exists()).toBe(false);
  });
});
