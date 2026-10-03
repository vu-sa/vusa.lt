import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CoordinatorCard from '../CoordinatorCard.vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';

describe('CoordinatorCard compact', () => {
  const coordinator = { name: 'Jonas Jonaitis', email: 'jonas@vusa.lt', profile_photo_path: null, duty: 'Koordinatorius' };

  it('still names the person and offers to write to them', () => {
    const wrapper = mount(CoordinatorCard, { props: { coordinators: [coordinator], compact: true } });

    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.find('a[href="mailto:jonas@vusa.lt"]').exists()).toBe(true);
  });

  it('uses a smaller avatar in compact mode while keeping the home heading', () => {
    const compact = mount(CoordinatorCard, { props: { coordinators: [coordinator], compact: true } });
    const full = mount(CoordinatorCard, { props: { coordinators: [coordinator] } });

    expect(compact.get('h2').classes()).toContain('text-sm');
    expect(full.get('h2').classes()).toContain('text-sm');
    expect(compact.findComponent(UserAvatar).props('size')).toBe(32);
    expect(full.findComponent(UserAvatar).props('size')).toBe(40);
  });
});
