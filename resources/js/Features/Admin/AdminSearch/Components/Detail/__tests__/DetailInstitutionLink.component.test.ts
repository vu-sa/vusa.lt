import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DetailInstitutionLink from '../DetailInstitutionLink.vue';

import { commonStubs } from '@/tests/stubs';

describe('DetailInstitutionLink', () => {
  it('renders a Link when institutionId is provided', () => {
    const wrapper = mount(DetailInstitutionLink, {
      props: { name: 'VU SA MIF', institutionId: 'inst-1' },
      global: { stubs: commonStubs },
    });

    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    expect(link.text()).toBe('VU SA MIF');
    expect(link.attributes('href')).toContain('/mocked-route/institutions.show');
  });

  it('renders plain text when no institutionId is provided', () => {
    const wrapper = mount(DetailInstitutionLink, {
      props: { name: 'VU SA MIF' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.text()).toBe('VU SA MIF');
  });

  it('uses the fallback when name is missing', () => {
    const wrapper = mount(DetailInstitutionLink, {
      props: { fallback: '—' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('—');
  });

  it('uses the first id when institutionId is an array', () => {
    const wrapper = mount(DetailInstitutionLink, {
      props: { name: 'VU SA MIF', institutionId: ['inst-1', 'inst-2'] },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('a').exists()).toBe(true);
  });
});
