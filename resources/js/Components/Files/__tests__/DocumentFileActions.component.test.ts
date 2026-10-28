import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import DocumentFileActions from '../DocumentFileActions.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const file = {
  id: 1,
  public_url: 'https://vusa.lt/d/test',
  sharepoint_web_url: 'https://vusa.sharepoint.com/test',
  removed_from_sharepoint_at: null as string | null,
  can: { update: true },
};

describe('DocumentFileActions', () => {
  it.each([true, false])('offers editing only to managers and keeps SharePoint in the menu (%s)', (allowed) => {
    const wrapper = mount(DocumentFileActions, { props: { file: { ...file, can: { update: allowed } } } as never });
    expect(wrapper.find('[data-action="edit"]').exists()).toBe(allowed);
    const actions = wrapper.findComponent({ name: 'CollectionRowActions' }).props('actions');
    expect(actions.find(action => action.key === 'sharepoint')?.role).toBeUndefined();
    expect(actions.find(action => action.key === 'public')?.role).toBe('open');
    wrapper.unmount();
  });

  it('keeps the SharePoint destination as open when a public link is unavailable', () => {
    const wrapper = mount(DocumentFileActions, { props: { file: { ...file, public_url: null } } as never });
    const actions = wrapper.findComponent({ name: 'CollectionRowActions' }).props('actions');
    expect(actions.find(action => action.key === 'sharepoint')?.role).toBe('open');
    wrapper.unmount();
  });

  it('labels cleanup deletion and emits the file', async () => {
    const removed = { ...file, removed_from_sharepoint_at: '2026-10-28' };
    const wrapper = mount(DocumentFileActions, { props: { file: removed } as never });
    const button = wrapper.get('button[data-action="delete"]');
    expect(button.text()).toBe('Ištrinti');
    expect(wrapper.find('a[href="https://vusa.sharepoint.com/test"]').exists()).toBe(false);
    await button.trigger('click');
    expect(wrapper.emitted('delete')?.[0]).toEqual([removed]);
  });
});
