import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import UserMergeDetails from '../UserMergeDetails.vue';

import { commonStubs } from '@/tests/stubs';

const records = [
  { id: 'protected', email: 'protected@stud.vu.lt', can_update_identity: false },
  { id: 'other', email: 'other@stud.vu.lt', can_update_identity: true },
];

const render = (targetId = 'protected') => mount(UserMergeDetails, {
  props: { records, targetId },
  global: { stubs: { ...commonStubs, UserMergeDutyEmails: true } },
});

describe('UserMergeDetails', () => {
  it('offers only the existing email when the kept identity is protected', () => {
    const wrapper = render();

    expect(wrapper.findAll('label').map(label => label.text())).toEqual(['protected@stud.vu.lt']);
    expect(wrapper.text()).toContain('users.merge.login_email_locked');
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['protected@stud.vu.lt']);
  });

  it('offers the involved addresses when the kept identity can change', () => {
    const wrapper = render('other');

    expect(wrapper.findAll('label').map(label => label.text())).toEqual(['protected@stud.vu.lt', 'other@stud.vu.lt']);
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['other@stud.vu.lt']);
  });

  it('resets the email and available choices when the kept account changes', async () => {
    const wrapper = render('other');
    await wrapper.setProps({ modelValue: 'other@stud.vu.lt', targetId: 'protected' });

    expect(wrapper.findAll('label')).toHaveLength(1);
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['protected@stud.vu.lt']);
  });
});
