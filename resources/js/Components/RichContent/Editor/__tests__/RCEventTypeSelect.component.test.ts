import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import RCEventTypeSelect from '../RCEventTypeSelect.vue';

describe('RCEventTypeSelect', () => {
  it('lists every event type from the shared page prop, plus a "no event type" option', () => {
    const wrapper = mount(RCEventTypeSelect, { props: { modelValue: undefined } });

    const values = wrapper.findAll('option').map(o => o.element.value);
    expect(values).toEqual(['', 'stovykla', 'konferencija']);
  });

  it('selecting an event type emits its slug', async () => {
    const wrapper = mount(RCEventTypeSelect, { props: { modelValue: undefined } });

    await wrapper.find('select').setValue('konferencija');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['konferencija']);
  });

  it('selecting "no event type" emits undefined', async () => {
    const wrapper = mount(RCEventTypeSelect, { props: { modelValue: 'konferencija' } });

    await wrapper.find('select').setValue('');

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([undefined]);
  });
});
