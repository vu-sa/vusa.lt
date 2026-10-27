import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import ModelTimestamps from '../ModelTimestamps.vue';

describe('ModelTimestamps', () => {
  it('shows both saved timestamps with the time in Vilnius', () => {
    const wrapper = mount(ModelTimestamps, {
      props: { createdAt: '2026-10-01T10:15:00Z', updatedAt: '2026-10-27T14:30:00Z' },
    });

    expect(wrapper.text()).toContain('Sukurta');
    expect(wrapper.text()).toContain('Atnaujinta');
    expect(wrapper.text()).toContain('2026-10-01 13:15');
    expect(wrapper.text()).toContain('2026-10-27 16:30');
    expect(wrapper.findAll('time').map(time => time.attributes('datetime'))).toEqual([
      '2026-10-01T10:15:00Z', '2026-10-27T14:30:00Z',
    ]);
  });

  it('hides absent timestamps for a new record', () => {
    const wrapper = mount(ModelTimestamps);

    expect(wrapper.find('dl').exists()).toBe(false);
  });

  it('shows only the available timestamp', () => {
    const wrapper = mount(ModelTimestamps, { props: { updatedAt: '2026-10-27T14:30:00Z' } });

    expect(wrapper.findAll('dt').map(label => label.text())).toEqual(['Atnaujinta']);
    expect(wrapper.findAll('time')).toHaveLength(1);
  });
});
