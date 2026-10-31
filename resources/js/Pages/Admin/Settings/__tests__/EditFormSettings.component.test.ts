import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import EditFormSettings from '../EditFormSettings.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('registration form settings', () => {
  it('preserves string ids and allows clearing only the optional registration form', async () => {
    const wrapper = mount(EditFormSettings, {
      props: {
        canEdit: true,
        member_registration_form_id: 'form-1',
        student_rep_registration_form_id: 'form-1',
        student_rep_institution_type_ids: [],
        forms: [{ id: 'form-1', name: 'Registration' }] as App.Entities.Form[],
        institution_types: [],
        memberRegistration: { responsibility: null, gaps: [] },
      },
      global: {
        stubs: {
          FormPage: { template: '<form @submit.prevent><slot /></form>' },
          TypeTreeMultiSelect: true,
        },
      },
    });
    const { form } = wrapper.vm as unknown as {
      form: { student_rep_registration_form_id: string | null };
    };

    expect(wrapper.get('label[for="member_registration_form_id"]').text()).toContain('*');
    expect(wrapper.get('select#member_registration_form_id option').attributes('disabled')).toBeDefined();
    await wrapper.get('select#student_rep_registration_form_id').setValue('');
    expect(form.student_rep_registration_form_id).toBeNull();
    await wrapper.get('select#student_rep_registration_form_id').setValue('form-1');
    expect(form.student_rep_registration_form_id).toBe('form-1');
    wrapper.unmount();
  });
});
