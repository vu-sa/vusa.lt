import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import { InstitutionScope } from '@/Types/enums';

import InstitutionScopeBadge from '../InstitutionScopeBadge.vue';

describe('InstitutionScopeBadge', () => {
  it('names an internal VU SA body', () => {
    const wrapper = mount(InstitutionScopeBadge, { props: { scope: InstitutionScope.Vusa } });

    expect(wrapper.text()).toContain('forms.options.governance_scope_vusa');
  });

  it.each([
    InstitutionScope.University,
    InstitutionScope.National,
    InstitutionScope.International,
  ])('names %s as its own external world', (scope) => {
    expect(mount(InstitutionScopeBadge, { props: { scope } }).text())
      .toContain(`forms.options.governance_scope_${scope}`);
  });
});
