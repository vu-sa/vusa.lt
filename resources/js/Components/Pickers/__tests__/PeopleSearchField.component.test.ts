import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import PeopleSearchField from '../PeopleSearchField.vue';

const data = ref<unknown[] | null>(null);
const urls: string[] = [];

vi.mock('@/Composables/useApi', () => ({
  useApi: (url: { value: string }) => ({
    data,
    isFetching: ref(false),
    execute: () => urls.push(url.value),
  }),
}));

const jonas = { id: 'u1', name: 'Jonas Jonaitis', tenants: ['MIF'] };
const ona = { id: 'u2', name: 'Ona Onaitė', tenants: [] as string[] };
const hits = [jonas, ona];

const mountField = (modelValue: typeof hits = []) =>
  mount(PeopleSearchField, {
    props: { 'modelValue': modelValue, 'onUpdate:modelValue': () => {} },
    global: { stubs: { UserAvatar: { template: '<span class="avatar" />' } } },
  });

const search = async (wrapper: ReturnType<typeof mount>) => {
  await wrapper.find('input').setValue('jo');
  await vi.advanceTimersByTimeAsync(400);
  data.value = hits;
  await wrapper.vm.$nextTick();
};

describe('PeopleSearchField.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    data.value = null;
    urls.length = 0;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('searches as you type for people to involve, without a permission', async () => {
    const wrapper = mountField();

    await search(wrapper);

    expect(urls.at(-1)).toContain('purpose=involve');
    expect(urls.at(-1)).not.toContain('permission=');
  });

  it('adds a picked person to the list', async () => {
    const wrapper = mountField([ona]);

    await search(wrapper);
    await wrapper.find('[role="option"]').trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([[ona, jonas]]);
  });

  it('offers someone already picked but does not add them twice', async () => {
    const wrapper = mountField([jonas]);

    await search(wrapper);
    const first = wrapper.find('[role="option"]');

    expect(first.attributes('aria-disabled')).toBe('true');
    expect(first.text()).toContain('Jau pasirinktas');
    await first.trigger('click');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
  });

  it('removes a person through their chip', async () => {
    const wrapper = mountField(hits);

    await wrapper.find('[data-testid="people-search-chips"] button').trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([[ona]]);
  });
});
