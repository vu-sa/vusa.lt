import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { createMockPage } from '@/tests/helpers/createMockPage';

import MemberTermRow from '../MemberTermRow.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('MemberTermRow', () => {
  const user = { id: 'member-1', name: 'Rūta Petraitė' };

  it.each([true, false])('renders the member name with user index access %s', (canReadUsers) => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: { path: 'mano/duties/1' },
      auth: { can: { index: { user: canReadUsers } } },
    }));

    const wrapper = mount(MemberTermRow, {
      props: { user, canManage: false },
      global: { stubs: { UserPopover: true } },
    });

    if (canReadUsers) {
      expect(wrapper.get('a[href^="/mocked-route/users.show"]').text()).toBe(user.name);
    }
    else {
      expect(wrapper.find('a').exists()).toBe(false);
      expect(wrapper.get('span.font-semibold').text()).toBe(user.name);
    }
  });
});
