import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import WikiProse from '../WikiProse.vue';
import { KnowledgeItemKind } from '@/Types/enums';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const stubs = {
  Popover: { props: ['open'], template: '<div :data-open="open"><slot /></div>' },
  PopoverAnchor: true,
  PopoverContent: { template: '<div data-testid="preview"><slot /></div>' },
};
const pages = { skvc: { id: 'skvc', title: 'Studijų kokybės vertinimo centras', kind: KnowledgeItemKind.Term, abbreviation: 'SKVC', summary: 'SKVC vertina studijas.', summaryHtml: '<strong>SKVC</strong> vertina studijas.', url: '/mano/knowledge/items/skvc' } };
const html = '<p>Žr. <a href="/old" target="_blank" data-knowledge-id="skvc">SKVC</a> ir <a href="/x" data-knowledge-id="gone">ištrintą</a>.</p>';

function render() {
  return mount(WikiProse, { props: { html, pages }, global: { stubs }, attachTo: document.body });
}

beforeEach(() => vi.clearAllMocks());

describe('WikiProse', () => {
  it('points links at the article and turns links to missing articles into text', () => {
    const wrapper = render();
    const links = wrapper.findAll('a');
    expect(links).toHaveLength(1);
    expect(links[0]!.attributes('href')).toBe('/mano/knowledge/items/skvc');
    expect(links[0]!.attributes('target')).toBeUndefined();
    expect(links[0]!.classes()).toContain('wiki-link');
    expect(wrapper.text()).toContain('ištrintą');
    wrapper.unmount();
  });

  it('previews on the first tap and opens the article on the second', async () => {
    const wrapper = render();
    const link = wrapper.get('a.wiki-link');
    await link.trigger('pointerdown', { pointerType: 'touch' });
    await link.trigger('click', { button: 0 });
    await nextTick();
    expect(router.visit).not.toHaveBeenCalled();
    expect(wrapper.get('[data-testid="preview"]').text()).toContain('SKVC vertina studijas.');
    await link.trigger('click', { button: 0 });
    expect(router.visit).toHaveBeenCalledWith(expect.stringContaining('/mano/knowledge/items/skvc'));
    wrapper.unmount();
  });

  it('opens the article straight away on a mouse click', async () => {
    const wrapper = render();
    const link = wrapper.get('a.wiki-link');
    await link.trigger('pointerdown', { pointerType: 'mouse' });
    await link.trigger('click', { button: 0 });
    expect(router.visit).toHaveBeenCalledTimes(1);
    wrapper.unmount();
  });
});
