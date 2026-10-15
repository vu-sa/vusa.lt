import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import DutiableTimelineDirtyBar from '../DutiableTimelineDirtyBar.vue';

function mountBar(overrides: Record<string, unknown> = {}) {
  return mount(DutiableTimelineDirtyBar, {
    props: { dirtyCount: 0, isDirty: false, processing: false, syncPending: false, ...overrides },
  });
}

describe('DutiableTimelineDirtyBar', () => {
  it('says everything is saved and offers nothing to press while clean', () => {
    const wrapper = mountBar();

    expect(wrapper.text()).toContain('dutiables.timeline.staging.clean');
    expect(wrapper.findAll('button')).toHaveLength(0);
  });

  /** The toolbar's one brand-filled action, so the page never has two competing primaries. */
  it('offers save as the brand action, with preview and discard beside it', async () => {
    const wrapper = mountBar({ isDirty: true, dirtyCount: 3 });

    const buttons = wrapper.findAll('button');
    const save = buttons.find(button => button.text().includes('dutiables.timeline.staging.save'))!;

    expect(save.classes()).toContain('bg-brand-fill');
    expect(buttons.filter(button => button.classes().includes('bg-brand-fill'))).toHaveLength(1);

    await save.trigger('click');
    await buttons.find(button => button.text().includes('dutiables.timeline.staging.preview'))!.trigger('click');
    await buttons.find(button => button.text().includes('dutiables.timeline.staging.discard'))!.trigger('click');

    expect(wrapper.emitted('save')).toHaveLength(1);
    expect(wrapper.emitted('preview')).toHaveLength(1);
    expect(wrapper.emitted('discard')).toHaveLength(1);
  });

  it('locks every button while a save is running', () => {
    const wrapper = mountBar({ isDirty: true, dirtyCount: 1, processing: true });

    expect(wrapper.findAll('button').every(button => button.attributes('disabled') !== undefined)).toBe(true);
  });
});
