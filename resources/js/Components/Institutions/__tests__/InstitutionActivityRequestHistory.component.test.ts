import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

import InstitutionActivityRequestHistory from '../InstitutionActivityRequestHistory.vue';
import type { ActivityRequestHistory } from '../InstitutionActivityRequestHistory.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const batch = (id: string): ActivityRequestHistory['data'][number] => ({ id, campaigns: { activity_confirmation: [] } });

describe('institution request history', () => {
  it('loads only the next history page and appends batches without duplicating them', async () => {
    const wrapper = mount(InstitutionActivityRequestHistory, {
      props: { history: { data: [batch('first')], next_page: 2 } },
      global: { stubs: commonStubs },
    });
    await wrapper.find('button').trigger('click');
    expect(router.reload).toHaveBeenCalledWith(expect.objectContaining({ only: ['activityRequests'], data: { activity_requests_page: 2 } }));
    await wrapper.setProps({ history: { data: [batch('second')], next_page: null } });
    expect(wrapper.findAll('h3.uppercase')).toHaveLength(2);
    await wrapper.setProps({ history: { data: [batch('second')], next_page: null } });
    expect(wrapper.findAll('h3.uppercase')).toHaveLength(2);
    expect(wrapper.find('button').exists()).toBe(false);
    await wrapper.setProps({ history: { data: [batch('refreshed')], next_page: 2 } });
    expect(wrapper.findAll('h3.uppercase')).toHaveLength(1);
  });
});
