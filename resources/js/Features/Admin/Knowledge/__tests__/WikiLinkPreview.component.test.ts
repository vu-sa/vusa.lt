import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { h } from 'vue';

import WikiLinkPreview from '../WikiLinkPreview.vue';
import WikiPageList from '../WikiPageList.vue';
import { KnowledgeItemKind } from '@/Types/enums';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const page = { id: 'skvc', title: 'SKVC', kind: KnowledgeItemKind.Term, abbreviation: '', summary: 'Vertina studijas.', summaryHtml: 'Vertina studijas.', url: '/mano/knowledge/items/skvc', thumbnailUrl: null };

function render() {
  return mount(WikiLinkPreview, {
    props: { pages: { skvc: page } },
    slots: { default: () => h(WikiPageList, { pages: [page], dense: true, preview: true }) },
    global: { stubs: {
      Popover: { props: ['open'], template: '<div :data-open="open"><slot /></div>' },
      PopoverAnchor: true,
      PopoverContent: { template: '<div data-testid="preview"><slot /></div>' },
    } },
  });
}

beforeEach(() => {
  vi.clearAllMocks();
  vi.useFakeTimers();
});
afterEach(() => vi.useRealTimers());

it('opens a related article preview on mouse hover and closes after leaving', async () => {
  const wrapper = render();
  const link = wrapper.get('[data-slot="wiki-page-list"] a');
  expect(wrapper.text()).not.toContain(page.summary);
  expect(link.attributes('aria-haspopup')).toBe('dialog');

  await link.trigger('pointerover', { pointerType: 'mouse' });
  await vi.advanceTimersByTimeAsync(350);
  expect(wrapper.get('[data-open]').attributes('data-open')).toBe('true');
  expect(wrapper.get('[data-testid="preview"]').text()).toContain(page.summary);
  expect(router.visit).not.toHaveBeenCalled();

  await link.trigger('pointerout', { pointerType: 'mouse' });
  await vi.advanceTimersByTimeAsync(200);
  expect(wrapper.get('[data-open]').attributes('data-open')).toBe('false');
  wrapper.unmount();
});

it('previews on the first touch and navigates once on the second', async () => {
  const wrapper = render();
  const link = wrapper.get('[data-slot="wiki-page-list"] a');
  await link.trigger('pointerdown', { pointerType: 'touch' });
  await link.trigger('click', { button: 0 });
  expect(wrapper.get('[data-testid="preview"]').text()).toContain(page.summary);
  expect(router.visit).not.toHaveBeenCalled();

  await link.trigger('click', { button: 0 });
  expect(router.visit).toHaveBeenCalledTimes(1);
  expect(router.visit).toHaveBeenCalledWith(expect.stringContaining(page.url));
  wrapper.unmount();
});

it('navigates once on a mouse click', async () => {
  const wrapper = render();
  await wrapper.get('[data-slot="wiki-page-list"] a').trigger('click', { button: 0 });
  expect(router.visit).toHaveBeenCalledTimes(1);
  wrapper.unmount();
});
