import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DutyOtherDutiesCard from '@/Components/Duties/DutyOtherDutiesCard.vue';
import { commonStubs } from '@/tests/stubs';

describe('DutyOtherDutiesCard.vue', () => {
  it('delegates a gendered duty name to the animated inflection component', () => {
    const wrapper = mount(DutyOtherDutiesCard, {
      props: { duties: [{ id: 1, name: 'Koordinatorius' }] },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Koordinator');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('ius');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
  });

  it('shows the bare duty name when it has no detectable gendered ending', () => {
    const wrapper = mount(DutyOtherDutiesCard, {
      props: { duties: [{ id: 1, name: 'Grupė' }] },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Grupė');
  });
});
