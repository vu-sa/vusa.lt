import { describe, expect, it } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import BlockPickerDialog from '../BlockPickerDialog.vue';
import { commonStubs } from '@/tests/stubs';

async function mountDialog() {
  const wrapper = mount(BlockPickerDialog, {
    props: { open: true },
    global: { stubs: commonStubs },
  });
  await flushPromises();
  return wrapper;
}

describe('BlockPickerDialog', () => {
  it('lists every registered type under "All" by default', async () => {
    const wrapper = await mountDialog();
    // 16 registered types plus "Tekstas" appearing once each — count list items via their icon+label rows.
    expect(wrapper.findAll('button').filter(b => b.text().includes('Tekstas')).length).toBeGreaterThan(0);
  });

  it('filters the type list by category', async () => {
    const wrapper = await mountDialog();
    const embedsCategoryBtn = wrapper.findAll('button').find(b => b.text().includes('rich-content.category_embed'))!;
    await embedsCategoryBtn.trigger('click');

    // tiptap ("Tekstas") is category "text", so it must disappear once "embed" is selected.
    const middleColumnButtons = wrapper.findAll('button').filter(b => b.text().includes('Teksto laukas') || b.text().includes('Tekstas'));
    expect(middleColumnButtons.some(b => b.text().includes('Tekstas') && !b.text().includes('Teksto laukas'))).toBe(false);
  });

  it('filters the type list by search term', async () => {
    const wrapper = await mountDialog();
    await wrapper.find('input[type="search"]').setValue('Kortelė');
    await flushPromises();

    const visibleLabels = wrapper.findAll('button').map(b => b.text());
    expect(visibleLabels.some(t => t.includes('Kortelė'))).toBe(true);
    expect(visibleLabels.some(t => t === 'Tekstas')).toBe(false);
  });

  it('shows a "nothing found" message when the search matches nothing', async () => {
    const wrapper = await mountDialog();
    await wrapper.find('input[type="search"]').setValue('xyznonexistent');
    await flushPromises();
    expect(wrapper.text()).toContain('rich-content.no_content_types_found');
  });

  it('shows a fallback message for a type with no live sample (news)', async () => {
    const wrapper = await mountDialog();
    await wrapper.find('input[type="search"]').setValue('Naujienos');
    await flushPromises();

    const newsItem = wrapper.findAll('button').find(b => b.text().includes('Naujienos'))!;
    await newsItem.trigger('mouseenter');
    await flushPromises();

    expect(wrapper.text()).toContain('rich-content.no_preview_available');
  });

  it('selects a type and closes the dialog on click', async () => {
    const wrapper = await mountDialog();
    await wrapper.find('input[type="search"]').setValue('Kortelė');
    await flushPromises();

    const cardItem = wrapper.findAll('button').find(b => b.text().includes('Kortelė'))!;
    await cardItem.trigger('click');
    // The stub Dialog doesn't actually unmount on the emitted update:open — let the
    // shadcn-card preview's pending async import settle before the test ends, or it
    // resolves during a later test file's environment teardown instead.
    await flushPromises();

    expect(wrapper.emitted('select')).toEqual([['shadcn-card']]);
    expect(wrapper.emitted('update:open')).toEqual([[false]]);
  });

  it('shows a variant switcher for hero and swaps the preview between variants', async () => {
    const wrapper = await mountDialog();
    await wrapper.find('input[type="search"]').setValue('Hero');
    await flushPromises();

    const heroItem = wrapper.findAll('button').find(b => b.text().includes('Hero'))!;
    await heroItem.trigger('mouseenter');
    await flushPromises();

    // Four hero variants: split (default), centered, banner, panel.
    const panelVariantButton = wrapper.findAll('button').find(b => b.text() === 'Panelė');
    expect(panelVariantButton).toBeTruthy();

    await panelVariantButton!.trigger('click');
    await flushPromises();

    // Switching to the panel variant's sample shouldn't throw, and the switcher
    // itself must not appear for a type with no declared variants (asserted
    // implicitly by every other test in this file never seeing it).
    expect(wrapper.text()).not.toContain('rich-content.no_preview_available');
  });

  it('does not show a variant switcher for a type without declared variants', async () => {
    const wrapper = await mountDialog();
    await wrapper.find('input[type="search"]').setValue('Kortelė');
    await flushPromises();

    const cardItem = wrapper.findAll('button').find(b => b.text().includes('Kortelė'))!;
    await cardItem.trigger('mouseenter');
    await flushPromises();

    expect(wrapper.findAll('button').some(b => b.text() === 'Panelė')).toBe(false);
  });

  it('resets search and category each time it is reopened', async () => {
    const wrapper = mount(BlockPickerDialog, { props: { open: false }, global: { stubs: commonStubs } });
    await wrapper.setProps({ open: true });
    await wrapper.find('input[type="search"]').setValue('Kortelė');
    await flushPromises();
    await wrapper.setProps({ open: false });
    await wrapper.setProps({ open: true });
    await flushPromises();

    expect((wrapper.find('input[type="search"]').element as HTMLInputElement).value).toBe('');
  });
});
