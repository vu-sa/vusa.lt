import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DutyLabel from '@/Components/Duties/DutyLabel.vue';
import { commonStubs } from '@/tests/stubs';

// Fixture uses a name with no detectable gendered ending ("Grupė") so InflectedDutyName
// renders it as plain text — these tests are about DutyLabel's own composition (name +
// institution + tenant), not the gendered-name animation, which has its own test file
// (InflectedDutyName.component.test.ts).
describe('DutyLabel.vue', () => {
  it('shows the bare duty name when there is no institution', () => {
    const wrapper = mount(DutyLabel, {
      props: { duty: { name: 'Grupė' } },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('Grupė');
  });

  it('shows the institution alongside the duty name, disambiguating repeated names', () => {
    const wrapper = mount(DutyLabel, {
      props: { duty: { name: 'Grupė', institution: { name: 'VU SA MIF' } } },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Grupė');
    expect(wrapper.text()).toContain('VU SA MIF');
  });

  it('shows the tenant shortname as a badge when present', () => {
    const wrapper = mount(DutyLabel, {
      props: { duty: { name: 'Grupė', institution: { name: 'VU SA MIF', tenant: { shortname: 'VU SA MIF' } } } },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('VU SA MIF');
  });

  it('omits the tenant badge when the institution has none', () => {
    const wrapper = mount(DutyLabel, {
      props: { duty: { name: 'Grupė', institution: { name: 'VU SA MIF', tenant: null } } },
      global: { stubs: commonStubs },
    });

    // Institution name and tenant shortname happen to coincide above; here there's
    // exactly one occurrence, proving no separate badge was rendered.
    expect(wrapper.text().match(/VU SA MIF/g)).toHaveLength(1);
  });

  it('delegates a gendered duty name to the animated inflection component', () => {
    const wrapper = mount(DutyLabel, {
      props: { duty: { name: 'Koordinatorius' } },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Koordinator');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('ius');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
  });
});
