import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import EventActions from '@/Components/Calendar/EventActions.vue';

/** The registration CTA is rendered inline with Facebook and Share actions. */
describe('Calendar/EventActions.vue', () => {
  function mountComponent(props: Record<string, unknown> = {}) {
    return mount(EventActions, {
      props: {
        shareTitle: 'Test Event',
        isPast: false,
        isLive: false,
        ...props,
      },
    });
  }

  it('renders the registration CTA inline with the hero actions', () => {
    const wrapper = mountComponent({
      registrationUrl: 'https://forms.example.com/register',
    });

    const cta = wrapper.find('a');
    expect(cta.exists()).toBe(true);
    expect(cta.text()).toContain('Registruotis');
    expect(cta.classes()).not.toContain('hidden');
  });

  it('shows the live CTA when the event is running', () => {
    const wrapper = mountComponent({
      registrationUrl: 'https://live.example.com',
      isLive: true,
    });

    const cta = wrapper.find('a');
    expect(cta.text()).toContain('Dalyvauk dabar');
  });

  it('does not render a registration CTA for past events', () => {
    const wrapper = mountComponent({
      registrationUrl: 'https://forms.example.com/register',
      isPast: true,
    });

    expect(wrapper.find('a').exists()).toBe(false);
  });

  it('renders Facebook and Share actions', () => {
    const wrapper = mountComponent({
      registrationUrl: 'https://forms.example.com/register',
      facebookUrl: 'https://facebook.com/event',
    });

    const actions = wrapper.findAll('a, button');
    expect(actions.some(action => action.text().includes('Facebook'))).toBe(true);
    expect(actions.some(action => action.text().includes('Dalinkis'))).toBe(true);
  });
});
