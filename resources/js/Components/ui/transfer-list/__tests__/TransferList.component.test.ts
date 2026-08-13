import { describe, it, expect, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';

import { TransferList } from '@/Components/ui/transfer-list';

const options = [
  { value: 'user-a', label: 'Ona Onaitė' },
  { value: 'user-b', label: 'Petras Petraitis' },
];

const lockedOptions = [
  { value: 'user-ex', label: 'Jonas Jonaitis' },
];

const mountList = (props: Record<string, unknown> = {}) =>
  mount(TransferList, {
    props: { modelValue: [], options, ...props },
  });

describe('TransferList — locked options', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('renders locked entries in the target panel', () => {
    wrapper = mountList({ lockedOptions });

    const locked = wrapper.findAll('[data-testid="transfer-list-locked-item"]');

    expect(locked).toHaveLength(1);
    expect(locked[0].text()).toContain('Jonas Jonaitis');
  });

  it('gives locked entries no remove button, unlike selected ones', () => {
    wrapper = mountList({ modelValue: ['user-a'], lockedOptions });

    const locked = wrapper.find('[data-testid="transfer-list-locked-item"]');
    const selected = wrapper.find('[data-testid="transfer-list-selected-item"]');

    expect(locked.find('button').exists()).toBe(false);
    // The selected row keeps its remove button — the difference is the point.
    expect(selected.find('button').exists()).toBe(true);
  });

  it('counts locked entries in the footer total', () => {
    wrapper = mountList({ modelValue: ['user-a'], lockedOptions });

    expect(wrapper.text()).toContain('2 / 3 pasirinkta');
  });

  it('leaves the count untouched when nothing is locked', () => {
    wrapper = mountList({ modelValue: ['user-a'] });

    expect(wrapper.text()).toContain('1 / 2 pasirinkta');
  });

  it('shows the empty state only when neither locked nor selected entries exist', () => {
    wrapper = mountList({ lockedOptions });
    expect(wrapper.text()).not.toContain('Nėra pasirinktų elementų');

    wrapper.unmount();

    wrapper = mountList();
    expect(wrapper.text()).toContain('Nėra pasirinktų elementų');
  });
});
