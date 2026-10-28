import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import DocumentFileActions from '../DocumentFileActions.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const file = {
  public_url: 'https://vusa.lt/d/test',
  sharepoint_web_url: 'https://vusa.sharepoint.com/test',
  removed_from_sharepoint_at: null as string | null,
  can: { update: true },
};

describe('DocumentFileActions', () => {
  it('keeps both file destinations visible', () => {
    const wrapper = mount(DocumentFileActions, { props: { file } as never });
    expect(wrapper.get('a[href="https://vusa.lt/d/test"]').exists()).toBe(true);
    expect(wrapper.get('a[href="https://vusa.sharepoint.com/test"]').attributes('aria-label')).toBe('Atidaryti SharePoint');
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
