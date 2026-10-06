import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import RuledGrid from '../RuledGrid.vue';

describe('RuledGrid', () => {
  it('keeps the caller’s semantic elements and applies one responsive border system', () => {
    const wrapper = mount(RuledGrid, {
      props: { as: 'dl', columns: { base: 1, sm: 2, lg: 3, xl: 5 } },
      slots: { default: '<div><dt>Name</dt><dd>Value</dd></div>' },
    });

    expect(wrapper.element.tagName).toBe('DL');
    expect(wrapper.find('dt').text()).toBe('Name');
    expect(wrapper.classes()).toEqual(expect.arrayContaining([
      '[&>*]:border-l', '[&>*]:border-b',
      '[&>*]:basis-full', 'sm:[&>*]:basis-1/2', 'lg:[&>*]:basis-1/3', 'xl:[&>*]:basis-1/5',
      'lg:max-xl:[&>*:is(:nth-child(3n),:last-child)]:border-r',
      'xl:[&>*:is(:nth-child(5n),:last-child)]:border-r',
    ]));
  });

  it('limits a content-width top rule to the first row', () => {
    const wrapper = mount(RuledGrid, {
      props: { as: 'ul', columns: { base: 2, lg: 4 }, topRule: 'cells' },
      slots: { default: '<li>One</li><li>Two</li><li>Three</li>' },
    });

    expect(wrapper.element.tagName).toBe('UL');
    expect(wrapper.classes()).not.toContain('border-t');
    expect(wrapper.classes()).toContain('max-sm:[&>*:nth-child(-n+2)]:border-t');
    expect(wrapper.classes()).toContain('lg:max-xl:[&>*:nth-child(-n+4)]:border-t');
  });
});
