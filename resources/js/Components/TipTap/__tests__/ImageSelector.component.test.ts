import { afterEach, describe, it, expect } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';

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

describe('ImageSelector details step', () => {
  it('keeps the alt-text rationale collapsed until it is asked for', async () => {
    const wrapper = await mountAtDetailsStep();

    expect(wrapper.text()).toContain('accessibility.alt_text');
    expect(wrapper.text()).not.toContain('accessibility.alt_text_required_explanation');

    await findByText(wrapper, 'accessibility.why_alt_required')!.trigger('click');

    expect(wrapper.text()).toContain('accessibility.alt_text_required_explanation');
    expect(wrapper.text()).toContain('accessibility.alt_text_example');
  });

  it('disables the alt field once the image is marked decorative', async () => {
    const wrapper = await mountAtDetailsStep();

    expect(wrapper.findAll('input')[0].attributes('disabled')).toBeUndefined();

    await wrapper.find('[role="checkbox"]').trigger('click');

    expect(wrapper.findAll('input')[0].attributes('disabled')).toBeDefined();
  });

  it('shows the alt-text budget, matching the edit dialog', async () => {
    const wrapper = await mountAtDetailsStep();

    expect(wrapper.text()).toContain('0/125');
    expect(wrapper.find('input').attributes('maxlength')).toBe('125');

    await wrapper.find('input').setValue('Studentai');

    expect(wrapper.text()).toContain('9/125');
  });
});
