import { describe, test, expect } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import ActivityChangeRow from '../ActivityChangeRow.vue';

import type { ActivityChange } from '@/Types/activityLog';

function makeChange(overrides: Partial<ActivityChange> = {}): ActivityChange {
  return {
    key: 'status',
    label: 'status',
    type: 'text',
    old: 'open',
    new: 'resolved',
    old_display: 'open',
    new_display: 'resolved',
    ...overrides,
  };
}

describe('ActivityChangeRow', () => {
  test('renders the field label', () => {
    const wrapper = mount(ActivityChangeRow, { props: { change: makeChange({ label: 'būsena' }) } });
    expect(wrapper.text()).toContain('būsena');
  });

  test('enum type renders old and new as badges', () => {
    const wrapper = mount(ActivityChangeRow, {
      props: { change: makeChange({ type: 'enum', old_display: 'Atvira', new_display: 'Išspręsta' }) },
    });

    const badges = wrapper.findAllComponents({ name: 'Badge' });
    expect(badges).toHaveLength(2);
    expect(wrapper.text()).toContain('Atvira');
    expect(wrapper.text()).toContain('Išspręsta');
  });

  test('boolean type renders old and new as badges', () => {
    const wrapper = mount(ActivityChangeRow, {
      props: { change: makeChange({ type: 'boolean', old_display: 'Ne', new_display: 'Taip' }) },
    });

    expect(wrapper.findAllComponents({ name: 'Badge' })).toHaveLength(2);
  });

  test('rich type renders the "content updated" placeholder and no raw value', () => {
    const wrapper = mount(ActivityChangeRow, {
      props: { change: makeChange({ type: 'rich', old_display: null, new_display: null }) },
    });

    expect(wrapper.text()).toContain('activity.rich_updated');
    expect(wrapper.text()).not.toContain('open');
    expect(wrapper.text()).not.toContain('resolved');
  });

  test('diff type renders a word-level diff via ActivityTextDiff, not the flat arrow layout', async () => {
    // ActivityChangeRow lazy-loads ActivityTextDiff via defineAsyncComponent
    // (see its own comment for why). Pre-warming the dynamic import here
    // means the loader promise is already settled by the time flushPromises()
    // runs below -- without this, the async component is still resolving its
    // module fetch and the diff content isn't in the DOM yet.
    await import('../ActivityTextDiff.vue');

    const wrapper = mount(ActivityChangeRow, {
      props: {
        change: makeChange({
          type: 'diff',
          old_display: 'the quick fox jumps',
          new_display: 'the quick dog jumps',
        }),
      },
    });
    await flushPromises();

    expect(wrapper.find('del').text()).toContain('fox');
    expect(wrapper.find('ins').text()).toContain('dog');
    // Not the badge/arrow layout used by other types.
    expect(wrapper.findComponent({ name: 'Badge' }).exists()).toBe(false);
  });

  test('diff type with a null old_display renders the new value as an insertion, not a struck-through em dash', async () => {
    await import('../ActivityTextDiff.vue');

    const wrapper = mount(ActivityChangeRow, {
      props: {
        change: makeChange({ type: 'diff', old_display: null, new_display: 'Brand new content' }),
      },
    });
    await flushPromises();

    expect(wrapper.find('del').exists()).toBe(false);
    expect(wrapper.find('ins').text()).toContain('Brand new content');
  });

  test('relation type renders resolved display names, not raw ids', () => {
    const wrapper = mount(ActivityChangeRow, {
      props: {
        change: makeChange({
          type: 'relation',
          key: 'responsible_user_id',
          old: '01H000000000000000000000',
          new: '01J000000000000000000000',
          old_display: null,
          new_display: 'Jonas Jonaitis',
        }),
      },
    });

    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.text()).not.toContain('01J000000000000000000000');
  });

  test('a missing old value renders the empty-value placeholder', () => {
    const wrapper = mount(ActivityChangeRow, {
      props: { change: makeChange({ old_display: null, new_display: 'Nauja reikšmė' }) },
    });

    expect(wrapper.text()).toContain('activity.empty_value');
    expect(wrapper.text()).toContain('Nauja reikšmė');
  });

  test('date type renders the server-formatted display verbatim', () => {
    const wrapper = mount(ActivityChangeRow, {
      props: {
        change: makeChange({
          type: 'date',
          old_display: '2026-01-01',
          new_display: '2026-02-15',
        }),
      },
    });

    // No client-side re-formatting (e.g. toLocaleDateString) happens here --
    // the string from the server is rendered as-is. See ActivityChangeFormatter.
    expect(wrapper.text()).toContain('2026-01-01');
    expect(wrapper.text()).toContain('2026-02-15');
  });
});
