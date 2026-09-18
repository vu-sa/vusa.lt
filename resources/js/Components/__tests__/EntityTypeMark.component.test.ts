import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { ModelEnum } from '@/Types/enums';

describe('EntityTypeMark', () => {
  it('renders the registered icon, label and category for an enum value', () => {
    const wrapper = mount(EntityTypeMark, {
      props: { type: ModelEnum.MEETING },
    });

    expect(wrapper.text()).toBe('Posėdis');
    expect(wrapper.find('svg').exists()).toBe(true);
    expect(wrapper.attributes('data-entity-type')).toBe('meeting');
    expect(wrapper.attributes('data-entity-category')).toBe('1');
  });

  it('accepts the uppercase enum key used by legacy callers', () => {
    const wrapper = mount(EntityTypeMark, {
      props: { type: 'USER' },
    });

    expect(wrapper.text()).toBe('Narys');
    expect(wrapper.attributes('data-entity-category')).toBe('3');
  });

  it('supports a context-specific label and merged root classes', () => {
    const wrapper = mount(EntityTypeMark, {
      props: { type: ModelEnum.CALENDAR, label: 'Artėjantis renginys', class: 'uppercase' },
    });

    expect(wrapper.text()).toBe('Artėjantis renginys');
    expect(wrapper.classes()).toContain('uppercase');
  });

  it('renders nothing for an unknown entity type', () => {
    const wrapper = mount(EntityTypeMark, {
      props: { type: 'unknown' },
    });

    expect(wrapper.html()).toBe('<!--v-if-->');
  });
});
