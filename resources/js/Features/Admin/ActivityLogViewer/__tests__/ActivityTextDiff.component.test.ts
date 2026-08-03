import { describe, test, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import ActivityTextDiff from '../ActivityTextDiff.vue';

describe('ActivityTextDiff', () => {
  test('renders unchanged words as plain text, without ins/del', () => {
    const wrapper = mount(ActivityTextDiff, { props: { old: 'the quick fox', new: 'the quick fox' } });

    expect(wrapper.find('ins').exists()).toBe(false);
    expect(wrapper.find('del').exists()).toBe(false);
    expect(wrapper.text()).toContain('the quick fox');
  });

  test('renders an added word inside <ins> and a removed word inside <del>', () => {
    const wrapper = mount(ActivityTextDiff, {
      props: { old: 'the quick fox jumps', new: 'the quick dog jumps' },
    });

    expect(wrapper.find('del').text()).toContain('fox');
    expect(wrapper.find('ins').text()).toContain('dog');
  });

  test('a null old renders the entire new value as an insertion, not a struck-through empty-value placeholder', () => {
    const wrapper = mount(ActivityTextDiff, { props: { old: null, new: 'Brand new content' } });

    expect(wrapper.find('del').exists()).toBe(false);
    expect(wrapper.find('ins').text()).toContain('Brand new content');
    expect(wrapper.text()).not.toContain('—');
  });

  test('both null renders the empty-value placeholder', () => {
    const wrapper = mount(ActivityTextDiff, { props: { old: null, new: null } });

    expect(wrapper.find('ins').exists()).toBe(false);
    expect(wrapper.find('del').exists()).toBe(false);
    expect(wrapper.text()).toContain('activity.empty_value');
  });

  test('never renders raw HTML -- interpolated text only', () => {
    const wrapper = mount(ActivityTextDiff, {
      props: { old: '<script>alert(1)</script> old', new: '<script>alert(1)</script> new' },
    });

    expect(wrapper.find('script').exists()).toBe(false);
    expect(wrapper.html()).not.toContain('<script>alert');
  });

  test('a long unchanged run collapses behind a toggle that expands on click', async () => {
    const commonRun = Array.from({ length: 40 }, (_, i) => `word${i}`).join(' ');
    const wrapper = mount(ActivityTextDiff, {
      props: { old: `${commonRun} old`, new: `${commonRun} new` },
    });

    expect(wrapper.find('button').exists()).toBe(true);
    // Middle words are hidden until expanded.
    expect(wrapper.text()).not.toContain('word20');

    await wrapper.find('button').trigger('click');

    expect(wrapper.text()).toContain('word20');
  });

  test('a short unchanged run is not collapsed', () => {
    const wrapper = mount(ActivityTextDiff, { props: { old: 'a short old run', new: 'a short new run' } });

    expect(wrapper.find('button').exists()).toBe(false);
  });
});
