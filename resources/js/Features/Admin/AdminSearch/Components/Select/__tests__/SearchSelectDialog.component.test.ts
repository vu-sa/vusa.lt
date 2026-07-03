import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { h } from 'vue';

import SearchSelectDialog from '../SearchSelectDialog.vue';
import type { NormalizedSearchHit } from '../../../Utils/searchHitMappers';
import { commonStubs } from '@/tests/stubs';

const hitA = { id: 'r-1', recordId: '1', collection: 'resources', title: 'Alpha', icon: {}, raw: {} } as unknown as NormalizedSearchHit;
const hitB = { id: 'r-2', recordId: '2', collection: 'resources', title: 'Beta', icon: {}, raw: {} } as unknown as NormalizedSearchHit;

const stubs = {
  ...commonStubs,
  DialogTrigger: { template: '<div><slot /></div>' },
};

/**
 * Renders the dialog with a default slot that exposes the scoped `toggle` and
 * selection state as clickable buttons, so tests drive selection through the DOM.
 */
function mountDialog(props: Record<string, unknown> = {}) {
  return mount(SearchSelectDialog, {
    props: { open: true, multiple: true, title: 'Select', ...props },
    slots: {
      default: (slotProps: { toggle: (h: NormalizedSearchHit) => void; selectedIds: Set<string> }) => [
        h('button', { class: 'toggle-a', onClick: () => slotProps.toggle(hitA) }, 'A'),
        h('button', { class: 'toggle-b', onClick: () => slotProps.toggle(hitB) }, 'B'),
        h('span', { class: 'count' }, String(slotProps.selectedIds.size)),
      ],
    },
    global: { stubs },
  });
}

function confirmButton(wrapper: ReturnType<typeof mount>) {
  return wrapper.findAll('button').find(b => /Pridėti|Add selected/.test(b.text()))!;
}

describe('SearchSelectDialog', () => {
  it('accumulates selections in multiple mode and confirms them', async () => {
    const wrapper = mountDialog();

    await wrapper.find('.toggle-a').trigger('click');
    await wrapper.find('.toggle-b').trigger('click');
    expect(wrapper.find('.count').text()).toBe('2');

    await confirmButton(wrapper).trigger('click');

    const confirmEvents = wrapper.emitted('confirm');
    expect(confirmEvents).toHaveLength(1);
    expect((confirmEvents![0][0] as NormalizedSearchHit[]).map(h => h.recordId)).toEqual(['1', '2']);
  });

  it('toggling an already-selected hit removes it', async () => {
    const wrapper = mountDialog();

    await wrapper.find('.toggle-a').trigger('click');
    await wrapper.find('.toggle-a').trigger('click');
    expect(wrapper.find('.count').text()).toBe('0');
  });

  it('single mode replaces the selection', async () => {
    const wrapper = mountDialog({ multiple: false });

    await wrapper.find('.toggle-a').trigger('click');
    await wrapper.find('.toggle-b').trigger('click');
    expect(wrapper.find('.count').text()).toBe('1');

    await confirmButton(wrapper).trigger('click');
    const confirmEvents = wrapper.emitted('confirm');
    expect((confirmEvents![0][0] as NormalizedSearchHit[]).map(h => h.recordId)).toEqual(['2']);
  });

  it('cancel closes without confirming', async () => {
    const wrapper = mountDialog();
    await wrapper.find('.toggle-a').trigger('click');

    const cancel = wrapper.findAll('button').find(b => /Atšaukti|Cancel/.test(b.text()))!;
    await cancel.trigger('click');

    expect(wrapper.emitted('confirm')).toBeUndefined();
    expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false]);
  });

  it('resets the working selection when reopened', async () => {
    const wrapper = mountDialog();
    await wrapper.find('.toggle-a').trigger('click');
    expect(wrapper.find('.count').text()).toBe('1');

    await wrapper.setProps({ open: false });
    await wrapper.setProps({ open: true });
    expect(wrapper.find('.count').text()).toBe('0');
  });

  it('seeds the selection from initialHits (pre-checked)', async () => {
    const wrapper = mountDialog({ initialHits: [hitA] });
    // Pre-checked without any interaction.
    expect(wrapper.find('.count').text()).toBe('1');

    await confirmButton(wrapper).trigger('click');
    const confirmEvents = wrapper.emitted('confirm');
    expect((confirmEvents![0][0] as NormalizedSearchHit[]).map(h => h.recordId)).toEqual(['1']);
  });

  it('re-seeds initialHits on each open', async () => {
    const wrapper = mountDialog({ initialHits: [hitA] });
    await wrapper.find('.toggle-a').trigger('click'); // deselect
    expect(wrapper.find('.count').text()).toBe('0');

    await wrapper.setProps({ open: false });
    await wrapper.setProps({ open: true });
    expect(wrapper.find('.count').text()).toBe('1');
  });

  it('disables Confirm at zero unless allowEmpty', async () => {
    const wrapper = mountDialog();
    expect(confirmButton(wrapper).attributes('disabled')).toBeDefined();

    const allow = mountDialog({ allowEmpty: true });
    expect(confirmButton(allow).attributes('disabled')).toBeUndefined();
  });

  it('allowEmpty confirms an empty selection', async () => {
    const wrapper = mountDialog({ allowEmpty: true });
    await confirmButton(wrapper).trigger('click');

    const confirmEvents = wrapper.emitted('confirm');
    expect(confirmEvents).toHaveLength(1);
    expect(confirmEvents![0][0]).toEqual([]);
  });
});
