import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DutyAppointmentCard from '../DutyAppointmentCard.vue';

describe('DutyAppointmentCard', () => {
  it('renders the resolved method label', () => {
    const wrapper = mount(DutyAppointmentCard, {
      props: { appointment: { selection_method: 'elected', appointed_by: null, term_length: null } },
    });
    // 'elected' → METHOD_LABELS.elected ('Renkama') → $t returns the key
    expect(wrapper.text()).toContain('Renkama');
    expect(wrapper.text()).toContain('Būdas');
  });

  it('hides rows that have no value', () => {
    const wrapper = mount(DutyAppointmentCard, {
      props: { appointment: { selection_method: null, appointed_by: null, term_length: '1 metų kadencija' } },
    });
    expect(wrapper.text()).toContain('Kadencija');
    expect(wrapper.text()).toContain('1 metų kadencija');
    expect(wrapper.text()).not.toContain('Būdas');
    expect(wrapper.text()).not.toContain('Skiria');
  });

  it('renders the appointed-by value', () => {
    const wrapper = mount(DutyAppointmentCard, {
      props: { appointment: { selection_method: null, appointed_by: 'VU Senatas', term_length: null } },
    });
    expect(wrapper.text()).toContain('Skiria');
    expect(wrapper.text()).toContain('VU Senatas');
  });
});
