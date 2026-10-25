import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import InactiveInstitutionBadge from '../InactiveInstitutionBadge.vue';

import { commonStubs } from '@/tests/stubs';

describe('InactiveInstitutionBadge', () => {
  it.each([false, 0])('labels an explicitly inactive institution (%s)', (active) => {
    const wrapper = mount(InactiveInstitutionBadge, { props: { active }, global: { stubs: commonStubs } });
    expect(wrapper.text()).toContain('Neaktyvi institucija');
    expect(wrapper.find('[data-status-role="neutral"]').exists()).toBe(true);
  });

  it.each([true, 1, undefined])('does not label active or unspecified institutions (%s)', (active) => {
    const wrapper = mount(InactiveInstitutionBadge, { props: { active }, global: { stubs: commonStubs } });
    expect(wrapper.text()).toBe('');
  });
});
