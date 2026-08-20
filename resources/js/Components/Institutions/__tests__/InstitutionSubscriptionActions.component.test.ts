import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionSubscriptionActions from '../InstitutionSubscriptionActions.vue';

import { commonStubs } from '@/tests/stubs';

const mountActions = (props: Record<string, unknown> = {}) =>
  mount(InstitutionSubscriptionActions, {
    props: { followed: false, muted: false, ...props },
    global: { stubs: commonStubs },
  });

const buttons = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('button');

describe('InstitutionSubscriptionActions', () => {
  it('offers the mute button only while following', () => {
    expect(buttons(mountActions())).toHaveLength(1);
    expect(buttons(mountActions({ followed: true }))).toHaveLength(2);
  });

  it('emits toggle-follow rather than mutating anything itself', async () => {
    const wrapper = mountActions();

    await buttons(wrapper)[0].trigger('click');

    expect(wrapper.emitted('toggle-follow')).toHaveLength(1);
  });

  it('emits toggle-mute from the second button', async () => {
    const wrapper = mountActions({ followed: true });

    await buttons(wrapper)[1].trigger('click');

    expect(wrapper.emitted('toggle-mute')).toHaveLength(1);
  });

  it('disables the button that is mid-flight', () => {
    const wrapper = mountActions({ followed: true, muteLoading: true });

    expect(buttons(wrapper)[0].attributes('disabled')).toBeUndefined();
    expect(buttons(wrapper)[1].attributes('disabled')).toBeDefined();
  });

  it('disables both buttons for a duty-based subscription', () => {
    const wrapper = mountActions({ followed: true, dutyBased: true });

    expect(buttons(wrapper)[0].attributes('disabled')).toBeDefined();
    expect(buttons(wrapper)[1].attributes('disabled')).toBeDefined();
  });
});
