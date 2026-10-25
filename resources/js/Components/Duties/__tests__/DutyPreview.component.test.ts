import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DutyPreview from '../DutyPreview.vue';
import { dutyPreviewFromRecord, dutyPreviewFromSearch } from '../dutyPreview';

import { commonStubs } from '@/tests/stubs';

const baseDuty = {
  id: 'du1',
  name_lt: 'Pirmininkas',
  institution_name_lt: 'VU SA MIF',
  tenant_shortname: 'MIF',
  home_tenant_id: 2,
  type_titles: ['Koordinatorius'],
  current_user_names: ['Jonas Jonaitis', 'Petras Petraitis'],
  current_users_count: 2,
  previous_user_names: ['Ona Onaitė'],
};

const mountPreview = (props: Record<string, unknown>) =>
  mount(DutyPreview, {
    props: { duty: dutyPreviewFromSearch(baseDuty), ...props },
    global: { stubs: { ...commonStubs } },
  });

describe('DutyPreview', () => {
  it('renders current and previous member names', () => {
    const wrapper = mountPreview({});
    const text = wrapper.text();

    expect(text).toContain('Jonas Jonaitis');
    expect(text).toContain('Petras Petraitis');
    expect(text).toContain('Ona Onaitė');
  });

  it('shows the cross-tenant notice when external', () => {
    const wrapper = mountPreview({ isExternal: true });

    expect(wrapper.text()).toContain('Ši pareigybė priklauso kitam padaliniui');
  });

  it('hides the cross-tenant notice when not external', () => {
    const wrapper = mountPreview({ isExternal: false });

    expect(wrapper.text()).not.toContain('Ši pareigybė priklauso kitam padaliniui');
  });

  it('shows a database row\'s member count and flags an empty duty', () => {
    const wrapper = mountPreview({
      duty: dutyPreviewFromRecord({
        id: 7,
        name: { lt: 'Koordinatorius' },
        email: null,
        dutiables_count: 0,
        institution: { id: 3, name: { lt: 'VU SA' }, short_name: { lt: 'VU SA' }, is_active: true },
      }),
    });

    const members = wrapper.findAll('[data-slot="preview-fact"]').find(fact => fact.text().startsWith('Nariai'));
    expect(members?.find('dd').text()).toBe('Neužimta');
    expect(members?.find('dd').classes()).toContain('text-status-attention');
    expect(wrapper.text()).toContain('VU SA');
  });
});
