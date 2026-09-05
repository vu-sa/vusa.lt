import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCInsertAffordance from '../RCInsertAffordance.vue';
import { getContentType } from '../../Types';
import { commonStubs } from '@/tests/stubs';

describe('RCInsertAffordance', () => {
  const quickAddTypes = [getContentType('tiptap'), getContentType('shadcn-card')];

  function mountAffordance() {
    return mount(RCInsertAffordance, {
      props: { quickAddTypes },
      global: { stubs: commonStubs },
    });
  }

  it('emits insert with the chosen type', async () => {
    const wrapper = mountAffordance();
    const item = wrapper.findAll('button').find(b => b.text().includes(quickAddTypes[0]!.label));
    await item!.trigger('click');
    expect(wrapper.emitted('insert')).toEqual([[quickAddTypes[0]!.value]]);
  });

  it('emits more when "more content types" is clicked', async () => {
    const wrapper = mountAffordance();
    const item = wrapper.findAll('button').find(b => b.text().includes('rich-content.more_content_types'));
    await item!.trigger('click');
    expect(wrapper.emitted('more')).toHaveLength(1);
  });

  it('uses the larger hover zone by default, for the full-screen editor', () => {
    const wrapper = mountAffordance();
    expect(wrapper.classes()).toContain('h-12');
    expect(wrapper.classes()).not.toContain('h-5');
  });

  it('shrinks the hover zone when compact, so it stops overlapping RCBlockCard\'s header', () => {
    const wrapper = mount(RCInsertAffordance, {
      props: { quickAddTypes, compact: true },
      global: { stubs: commonStubs },
    });
    expect(wrapper.classes()).toContain('h-5');
    expect(wrapper.classes()).not.toContain('h-12');
  });
});
