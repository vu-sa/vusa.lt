import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { commonStubs } from '@/tests/stubs';
import DutyDetailPreview from '../DutyDetailPreview.vue';

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
  mount(DutyDetailPreview, {
    props: { duty: baseDuty, ...props },
    global: { stubs: { ...commonStubs } },
  });

describe('DutyDetailPreview', () => {
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
});
