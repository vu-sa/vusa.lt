import { useForm } from '@inertiajs/vue3';
import type * as Inertia from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

import TagSheetForm from '../TagSheetForm.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountForm = (tag: InstanceType<typeof TagSheetForm>['$props']['tag'] = null) => mount(TagSheetForm, {
  props: { open: true, tag },
  global: {
    stubs: {
      SheetForm: {
        props: ['open', 'title'],
        emits: ['submit'],
        template: '<section v-if="open"><h1>{{ title }}</h1><slot /><button data-testid="save" @click="$emit(\'submit\')">save</button></section>',
      },
      TiptapEditor: {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template: '<textarea data-testid="description" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
      },
    },
  },
});

describe('TagSheetForm', () => {
  beforeEach(async () => {
    vi.clearAllMocks();
    const inertia = await vi.importActual<typeof Inertia>('@inertiajs/vue3');
    vi.mocked(useForm).mockImplementation(inertia.useForm);
  });

  it('opens a new tag with an editable description', async () => {
    const wrapper = mountForm();
    const form = vi.mocked(useForm).mock.results[0].value;
    const post = vi.spyOn(form, 'post').mockImplementation(() => {});

    expect(wrapper.text()).toContain('Nauja žyma');
    await wrapper.get('[data-testid="description"]').setValue('Naujas aprašymas');
    await wrapper.get('[data-testid="save"]').trigger('click');

    expect(form.description).toEqual({ lt: 'Naujas aprašymas', en: '' });
    expect(post).toHaveBeenCalledWith(route('tags.store'), expect.any(Object));
    wrapper.unmount();
  });

  it.each([null, { lt: '<p>Tema</p>', en: '<p>Topic</p>' }])('opens and edits a reactive tag with description %j', async (description) => {
    const tag = reactive({
      id: 71,
      name: { lt: 'Stipendijos', en: 'Scholarships' },
      description,
      alias: 'stipendijos',
      is_topic: true,
    });
    const wrapper = mountForm(tag);
    const form = vi.mocked(useForm).mock.results[0].value;
    const patch = vi.spyOn(form, 'patch').mockImplementation(() => {});

    expect(wrapper.text()).toContain('Redaguoti žymą');
    expect(wrapper.get<HTMLInputElement>('#tag-alias').element.value).toBe('stipendijos');
    expect(wrapper.get<HTMLTextAreaElement>('[data-testid="description"]').element.value).toBe(description?.lt ?? '');
    await wrapper.get('[data-testid="description"]').setValue('Pakeistas aprašymas');
    await wrapper.get('[data-testid="save"]').trigger('click');

    expect(form.description).toEqual({ lt: 'Pakeistas aprašymas', en: description?.en ?? '' });
    expect(tag.description).toEqual(description);
    expect(patch).toHaveBeenCalledWith(route('tags.update', tag.id), expect.any(Object));

    await wrapper.setProps({ tag: null });
    expect(wrapper.get<HTMLTextAreaElement>('[data-testid="description"]').element.value).toBe('');
    expect(form.name).toEqual({ lt: '', en: '' });
    wrapper.unmount();
  });
});
