import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SearchResultListItem from '../SearchResultListItem.vue';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

const hit = { id: 'r-1', recordId: '1', collection: 'resources', title: 'Alpha', icon: {}, raw: {} } as unknown as NormalizedSearchHit;

const stubs = { SearchHitRow: { template: '<span class="hit-row" />' } };

function mountItem(props: Record<string, unknown>) {
  return mount(SearchResultListItem, { props: { hit, ...props }, global: { stubs } });
}

describe('SearchResultListItem selection mode', () => {
  it('navigation mode renders a button and emits select on click', async () => {
    const wrapper = mountItem({});
    expect(wrapper.find('button').exists()).toBe(true);
    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted('select')).toHaveLength(1);
  });

  it('multiple mode renders a checkbox that emits toggle', async () => {
    const wrapper = mountItem({ selectable: true, multiple: true });
    const checkbox = wrapper.find('[role="checkbox"]');
    expect(checkbox.exists()).toBe(true);
    expect(checkbox.attributes('aria-label')).toBe('Alpha');
    await checkbox.trigger('click');
    expect(wrapper.emitted('toggle')).toHaveLength(1);
  });

  it('single mode renders a radio control that emits toggle', async () => {
    const wrapper = mountItem({ selectable: true, multiple: false });
    const radio = wrapper.get('[role="radio"]');
    expect(radio.attributes('aria-label')).toBe('Alpha');
    expect(radio.attributes('aria-checked')).toBe('false');
    await radio.trigger('click');
    expect(wrapper.emitted('toggle')).toHaveLength(1);
  });

  it('reports the selection on the control in both modes', () => {
    expect(mountItem({ selectable: true, multiple: true, checked: true }).get('[role="checkbox"]').attributes('aria-checked')).toBe('true');
    expect(mountItem({ selectable: true, multiple: false, checked: true }).get('[role="radio"]').attributes('aria-checked')).toBe('true');
  });

  it('multi-select row body click highlights for detail without toggling', async () => {
    const wrapper = mountItem({ selectable: true, multiple: true });
    await wrapper.get('[role="button"]').trigger('click');
    expect(wrapper.emitted('select')).toHaveLength(1);
    expect(wrapper.emitted('toggle')).toBeUndefined();
  });

  it('single-select row body click both selects and highlights', async () => {
    const wrapper = mountItem({ selectable: true, multiple: false });
    await wrapper.get('[role="button"]').trigger('click');
    expect(wrapper.emitted('select')).toHaveLength(1);
    expect(wrapper.emitted('toggle')).toHaveLength(1);
  });

  it('disabled rows can still be highlighted for detail but not toggled', async () => {
    const wrapper = mountItem({ selectable: true, multiple: false, disabled: true });
    await wrapper.get('[role="button"]').trigger('click');
    // Detail viewing stays available; selection is blocked.
    expect(wrapper.emitted('select')).toHaveLength(1);
    expect(wrapper.emitted('toggle')).toBeUndefined();
  });
});
