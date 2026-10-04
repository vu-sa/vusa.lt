import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { afterEach, expect, it, vi } from 'vitest';

import CollectionFacetOptions from '@/Components/Collection/CollectionFacetOptions.vue';
import { provideFacetSearch, type FacetSearch } from '../facets';

afterEach(() => vi.useRealTimers());

const options = Array.from({ length: 9 }, (_, index) => ({ value: `option-${index}`, label: `Option ${index}`, count: index, isSelected: index === 0 }));

function mountOptions(search: FacetSearch) {
  return mount(defineComponent({
    setup() {
      provideFacetSearch(search);
      return () => h(CollectionFacetOptions, { facet: { field: 'tag_names', label: 'Tags', type: 'checkbox', remote: true, values: options } });
    },
  }));
}

it('debounces remote option searches, keeps selections visible and does not change the URL', async () => {
  vi.useFakeTimers();
  const search = vi.fn().mockResolvedValue([{ value: 'outside-first-page', count: 3 }]);
  const wrapper = mountOptions(search);
  const url = window.location.href;
  await wrapper.find('input').setValue('outside');
  expect(search).not.toHaveBeenCalled();
  await vi.advanceTimersByTimeAsync(300);
  await flushPromises();
  expect(search).toHaveBeenCalledWith('tag_names', 'outside', expect.any(AbortSignal));
  expect(wrapper.findAll('button[role="checkbox"]').map(button => button.text().replace(/\s/g, ''))).toEqual(['Option00', 'outside-first-page3']);
  expect(window.location.href).toBe(url);
  wrapper.unmount();
});

it('aborts the previous option request and exposes failures without removing selected values', async () => {
  vi.useFakeTimers();
  let signal: AbortSignal | undefined;
  const search = vi.fn().mockImplementationOnce((_field, _query, abortSignal) => {
    signal = abortSignal;
    return new Promise(() => {});
  }).mockRejectedValueOnce(new Error('Unavailable'));
  const wrapper = mountOptions(search);
  await wrapper.find('input').setValue('first');
  await vi.advanceTimersByTimeAsync(300);
  await wrapper.find('input').setValue('second');
  expect(signal?.aborted).toBe(true);
  await vi.advanceTimersByTimeAsync(300);
  await flushPromises();
  expect(wrapper.find('[role="status"]').exists()).toBe(true);
  expect(wrapper.find('button[aria-checked="true"]').text()).toContain('Option 0');
  wrapper.unmount();
});
