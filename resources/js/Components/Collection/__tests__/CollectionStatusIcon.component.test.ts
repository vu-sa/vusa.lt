import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CollectionStatusIcon from '../CollectionStatusIcon.vue';
import CollectionRow from '../CollectionRow.vue';

import { contentStatuses } from '@/Constants/statuses';

describe('compact collection row status', () => {
  it('shows an accessible green eye without badge chrome for published content', async () => {
    const wrapper = mount(CollectionStatusIcon, { props: { status: contentStatuses.published } });
    expect(wrapper.get('[role="img"]').attributes('aria-label')).toBe('Paskelbta');
    expect(wrapper.get('svg').classes()).toContain('text-status-success');
    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
    await wrapper.setProps({ status: contentStatuses.draft });
    expect(wrapper.get('[role="img"]').attributes('aria-label')).toBe('Juodraštis');
    expect(wrapper.get('svg').classes()).toContain('text-status-neutral');
  });

  it('centers the lead, content and action group vertically', () => {
    const wrapper = mount(CollectionRow, { slots: { lead: '<span>status</span>', default: '<p>Title</p><p>Date</p>' } });
    expect(wrapper.get('article > div').classes()).toContain('items-center');
  });
});
