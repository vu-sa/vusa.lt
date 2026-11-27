import { useForm } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import type { MockForm } from '@/tests/helpers/createMockForm';

import EditFormSettings from '../EditFormSettings.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('registration form settings', () => {
  it('preserves string ids and allows clearing both registration forms', async () => {
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
          FormPage: { emits: ['submit'], template: `<form @submit.prevent="$emit('submit')"><slot /></form>` },
          TypeTreeMultiSelect: true,
        },
      },
    });
    const form = vi.mocked(useForm).mock.results.at(-1)!.value as unknown as MockForm;
    const submit = vi.fn(() => [form.member_registration_form_id, form.student_rep_registration_form_id]);
    form.post.mockImplementation(submit);

    expect(wrapper.get('label[for="member_registration_form_id"]').text()).not.toContain('*');
    expect(wrapper.get('select#member_registration_form_id option').attributes('disabled')).toBeUndefined();
    const memberSelect = wrapper.get('select#member_registration_form_id');
    const representativeSelect = wrapper.get('select#student_rep_registration_form_id');
    await wrapper.get('form').trigger('submit');
    await memberSelect.setValue('');
    await representativeSelect.setValue('');
    await wrapper.get('form').trigger('submit');

    expect(form.post).toHaveBeenNthCalledWith(2, '/mocked-route/settings.forms.update');
    expect(submit).toHaveNthReturnedWith(1, ['form-1', 'form-1']);
    expect(submit).toHaveNthReturnedWith(2, [null, null]);
    wrapper.unmount();
  });
});
