import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import MemberSearchField from '@/Features/Admin/Occupancy/MemberSearchField.vue';

const data = ref<unknown[] | null>(null);
const execute = vi.fn();
const urls: string[] = [];

vi.mock('@/Composables/useApi', () => ({
  useApi: (url: { value: string }) => {
    urls.push(String(url.value));

    return {
      data,
      isFetching: ref(false),
      execute: () => {
        urls.push(url.value);
        execute();
      },
    };
  },
}));

const hits = [
  { id: 'u1', name: 'Jonas Jonaitis', email: 'jonas@stud.vu.lt', tenants: ['MIF'] },
  { id: 'u2', name: 'Ona Onaitė', email: 'ona@stud.vu.lt', tenants: [] },
];

const mountField = (props: Record<string, unknown> = {}) =>
  mount(MemberSearchField, {
    props: { modelValue: null, ...props },
    global: { stubs: { UserAvatar: { template: '<span class="avatar" />' } } },
  });

describe('MemberSearchField.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    data.value = null;
    execute.mockClear();
    urls.length = 0;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('does not search for fewer than two characters', async () => {
    const wrapper = mountField();

    await wrapper.find('input').setValue('j');
    await vi.advanceTimersByTimeAsync(400);

    expect(execute).not.toHaveBeenCalled();
    expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
  });

  it('sends the permission the endpoint requires, across all units', async () => {
    const wrapper = mountField();

    await wrapper.find('input').setValue('jon');
    await vi.advanceTimersByTimeAsync(400);

    expect(execute).toHaveBeenCalledTimes(1);
    const url = urls.at(-1) ?? '';
    expect(url).toContain('search=jon');
    expect(url).toContain('permission=duties.update.padalinys');
    expect(url).toContain('scope=all');
  });

  it('selects the arrowed-to result with Enter and never submits the surrounding form', async () => {
    const onSubmit = vi.fn();
    const wrapper = mount({
      components: { MemberSearchField },
      setup: () => ({ onSubmit }),
      template: '<form @submit.prevent="onSubmit"><MemberSearchField :model-value="null" @update:model-value="$emit(\'picked\', $event)" /></form>',
    }, { global: { stubs: { UserAvatar: true } }, attachTo: document.body });

    await wrapper.find('input').setValue('jo');
    await vi.advanceTimersByTimeAsync(400);
    data.value = hits;
    await wrapper.vm.$nextTick();

    const input = wrapper.find('input');
    await input.trigger('keydown', { key: 'ArrowDown' });
    await input.trigger('keydown', { key: 'Enter' });

    expect(onSubmit).not.toHaveBeenCalled();
    expect(wrapper.emitted('picked')?.[0]).toEqual([hits[0]]);
    wrapper.unmount();
  });

  it('does not submit the form when Enter is pressed with nothing highlighted', async () => {
    const onSubmit = vi.fn();
    const wrapper = mount({
      components: { MemberSearchField },
      setup: () => ({ onSubmit }),
      template: '<form @submit.prevent="onSubmit"><MemberSearchField :model-value="null" /></form>',
    }, { global: { stubs: { UserAvatar: true } }, attachTo: document.body });

    await wrapper.find('input').trigger('keydown', { key: 'Enter' });

    expect(onSubmit).not.toHaveBeenCalled();
    wrapper.unmount();
  });

  it('offers a member who already holds the duty but does not let them be picked', async () => {
    const wrapper = mountField({ takenIds: ['u1'] });

    await wrapper.find('input').setValue('jo');
    await vi.advanceTimersByTimeAsync(400);
    data.value = hits;
    await wrapper.vm.$nextTick();

    const options = wrapper.findAll('[role="option"]');
    expect(options[0].attributes('aria-disabled')).toBe('true');
    expect(options[0].text()).toContain('Jau eina šias pareigas');

    await options[0].trigger('click');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();

    await options[1].trigger('click');
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([hits[1]]);
  });

  it('shows the chosen member with a way to change them', async () => {
    const wrapper = mountField({ modelValue: hits[0] });

    expect(wrapper.find('[data-testid="member-search-selected"]').text()).toContain('Jonas Jonaitis');

    await wrapper.find('[data-testid="member-search-selected"] button').trigger('click');
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([null]);
  });

  it('renders the validation error under the field', () => {
    const wrapper = mountField({ error: 'Šis narys jau eina šias pareigas.' });

    expect(wrapper.text()).toContain('Šis narys jau eina šias pareigas.');
  });
});
