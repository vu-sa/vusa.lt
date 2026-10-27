import { afterEach, describe, it, expect } from 'vitest';
import { defineComponent } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';

import ImageSelector from '../ImageSelector.vue';

import { commonStubs } from '@/tests/stubs';

// The real file manager needs a backend; this only has to hand back a path.
const FileSelectorStub = defineComponent({
  name: 'FileSelectorStub',
  emits: ['submit'],
  template: '<button type="button" data-testid="pick" @click="$emit(\'submit\', \'public/files/test.png\')">pick</button>',
});

const wrappers: ReturnType<typeof mount>[] = [];

afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
});

function findByText(wrapper: ReturnType<typeof mount>, text: string) {
  return wrapper.findAll('button').find(button => button.text().includes(text));
}

/**
 * Submits through the form element rather than the Insert button: the button sits
 * outside the form and is wired to it with `form="image-details-form"`, an
 * association jsdom does not implement, so clicking it never dispatches submit.
 */
async function submitDetails(wrapper: ReturnType<typeof mount>) {
  await wrapper.find('form').trigger('submit');
  await flushPromises();
}

async function mountAtDetailsStep() {
  const wrapper = mount(ImageSelector, {
    props: { showModal: true },
    attachTo: document.body,
    global: { stubs: { ...commonStubs, FileSelector: FileSelectorStub } },
  });
  wrappers.push(wrapper);

  await wrapper.find('[data-testid="pick"]').trigger('click');
  await findByText(wrapper, 'common.next')!.trigger('click');

  return wrapper;
}

describe('ImageSelector submission', () => {
  it('still refuses an image with no alt text', async () => {
    const wrapper = await mountAtDetailsStep();

    await submitDetails(wrapper);

    expect(wrapper.emitted('submit')).toBeUndefined();
    expect(wrapper.text()).toContain('accessibility.alt_text_required');
  });

  it('rejects whitespace alt and trims submitted descriptions', async () => {
    const wrapper = await mountAtDetailsStep();
    await wrapper.find('#image-alt').setValue('   ');
    await submitDetails(wrapper);
    expect(wrapper.emitted('submit')).toBeUndefined();
    await wrapper.find('#image-alt').setValue('  Students  ');
    await wrapper.find('#image-title').setValue('  Event  ');
    await submitDetails(wrapper);
    expect(wrapper.emitted('submit')?.[0]).toEqual([{ src: '/uploads/files/test.png', alt: 'Students', title: 'Event' }]);
  });

  it.each([['#image-alt', 126], ['#image-title', 201]])('enforces the length limit for %s', async (selector, length) => {
    const wrapper = await mountAtDetailsStep();
    await wrapper.find('#image-alt').setValue('Valid alt');
    await wrapper.find(selector).setValue('x'.repeat(length));
    await submitDetails(wrapper);
    expect(wrapper.emitted('submit')).toBeUndefined();
    expect(wrapper.text()).toContain('validation.max.string');
  });

  it('clears details and errors when reopened', async () => {
    const wrapper = await mountAtDetailsStep();
    await submitDetails(wrapper);
    await wrapper.setProps({ showModal: false });
    await wrapper.setProps({ showModal: true });
    await wrapper.find('[data-testid="pick"]').trigger('click');
    await findByText(wrapper, 'common.next')!.trigger('click');
    expect((wrapper.find('#image-alt').element as HTMLInputElement).value).toBe('');
    expect(wrapper.find('#image-alt-error').exists()).toBe(false);
  });

  it('lets a decorative image through with an empty alt', async () => {
    const wrapper = await mountAtDetailsStep();

    await wrapper.find('[role="checkbox"]').trigger('click');
    await submitDetails(wrapper);

    expect(wrapper.emitted('submit')?.[0]).toEqual([
      { src: '/uploads/files/test.png', alt: '', title: '' },
    ]);
  });
});
