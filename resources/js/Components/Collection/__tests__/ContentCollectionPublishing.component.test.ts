import { mount, flushPromises } from '@vue/test-utils';
import { usePage, router } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';

import CollectionStatusMenu from '../CollectionStatusMenu.vue';

import IndexNews from '@/Pages/Admin/Content/IndexNews.vue';
import IndexPages from '@/Pages/Admin/Content/IndexPages.vue';
import IndexBanner from '@/Pages/Admin/Content/IndexBanner.vue';
import IndexCalendarEvents from '@/Pages/Admin/Calendar/IndexCalendarEvents.vue';
import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const collection = {
  name: 'CollectionPage',
  props: ['source'],
  template: `<div><slot name="cell" :item="source.items.value[0]" :column="{ key: 'status' }" /><slot name="cell" :item="source.items.value[0]" :column="{ key: 'is_active' }" /></div>`,
};
const meta = { total: 1, current_page: 1, per_page: 50, last_page: 1, from: 1, to: 1 };

describe('content collection table publishing', () => {
  it('shows the news excerpt as plain text in item rows', () => {
    const wrapper = mount(IndexNews, {
      props: { deletedCount: 0 },
      global: { stubs: { ...commonStubs, CollectionPage: {
        template: `<div><slot name="row" :item="{ id: 9, title: 'Content', short: '<p>News &amp; updates</p>', draft: false }" :actions="[]" view="rows" /></div>`,
      } } },
    });
    expect(wrapper.text()).toContain('News & updates');
    expect(wrapper.text()).not.toContain('<p>');
    expect(wrapper.text()).not.toContain('&amp;');
    wrapper.unmount();
  });

  it('omits the event type in calendar item rows', () => {
    const wrapper = mount(IndexCalendarEvents, {
      props: { calendar: { data: [], meta }, eventTypes: [], deletedCount: 0 },
      global: { stubs: { ...commonStubs, CollectionPage: {
        template: `<div><slot name="row" :item="{ id: 8, title: 'Event', event_type: { name: 'Conference' } }" :actions="[]" view="rows" /></div>`,
      } } },
    });
    expect(wrapper.text()).toContain('Event');
    expect(wrapper.text()).not.toContain('Conference');
    wrapper.unmount();
  });

  it.each([
    { component: IndexNews, permission: 'news', prefix: 'news' },
    { component: IndexPages, permission: 'page', prefix: 'pages' },
  ])('restores $prefix table publishing', async ({ component, permission, prefix }) => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { [permission]: true } } } }));
    const wrapper = mount(component, {
      props: { deletedCount: 0 },
      global: { stubs: { ...commonStubs, CollectionPage: { ...collection, template: `<div><slot name="cell" :item="{ id: 9, title: 'Content', draft: false, is_active: true }" :column="{ key: 'status' }" /></div>` }, CollectionStatusMenu: true } },
    });
    const menu = wrapper.getComponent(CollectionStatusMenu);
    expect(menu.props('editable')).toBe(true);
    menu.vm.$emit('update:modelValue', 'draft');
    await flushPromises();
    expect(router.patch).toHaveBeenCalledWith(route(`${prefix}.bulkStatus`), { ids: [9], published: false }, expect.any(Object));
    wrapper.unmount();
  });

  it('restores the banner active menu and saves through the status endpoint', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { banner: true } } } }));
    const wrapper = mount(IndexBanner, {
      props: { banners: { data: [{ id: 7, title: 'Banner', is_active: true } as App.Entities.Banner], meta } },
      global: { stubs: { ...commonStubs, CollectionPage: collection, CollectionStatusMenu: true } },
    });
    const menu = wrapper.getComponent(CollectionStatusMenu);
    expect(menu.props('editable')).toBe(true);
    menu.vm.$emit('update:modelValue', 'inactive');
    await flushPromises();
    expect(router.patch).toHaveBeenCalledWith(route('banners.updateStatus', 7), { is_active: false }, expect.objectContaining({ onError: expect.any(Function) }));
    await wrapper.setProps({ showDeleted: true });
    expect(wrapper.getComponent(CollectionStatusMenu).props('editable')).toBe(false);
    wrapper.unmount();
  });

  it('restores calendar draft editing', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { calendar: true } } } }));
    const wrapper = mount(IndexCalendarEvents, {
      props: { calendar: { data: [{ id: 8, title: 'Event', is_draft: false } as App.Entities.Calendar], meta }, eventTypes: [], deletedCount: 0 },
      global: { stubs: { ...commonStubs, CollectionPage: collection, CollectionStatusMenu: true } },
    });
    const menu = wrapper.getComponent(CollectionStatusMenu);
    expect(menu.props('editable')).toBe(true);
    menu.vm.$emit('update:modelValue', 'draft');
    await flushPromises();
    expect(router.patch).toHaveBeenCalledWith(route('calendar.updateIndex', 8), { is_draft: true }, expect.objectContaining({ onError: expect.any(Function) }));
    wrapper.unmount();
  });
});
