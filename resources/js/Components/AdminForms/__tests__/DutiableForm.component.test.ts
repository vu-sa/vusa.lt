import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import DutiableForm from '@/Components/AdminForms/DutiableForm.vue';

// Use the real Inertia useForm (reactive) rather than the global plain-object mock —
// the live public-contact preview must react to field changes. Same pattern as
// DutyForm.component.test.ts / NewsForm.component.test.ts.
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({
      props: {
        app: { locale: 'lt', url: 'https://vusa.test' },
      },
    }),
  };
});

const stubs = {
  AdminForm: { template: '<form @submit.prevent><slot name="status-header" /><slot /></form>' },
  FormElement: { template: '<section><slot name="title" /><slot name="description" /><slot /></section>' },
  FormFieldWrapper: { template: '<div><slot /></div>' },
  Alert: { template: '<div><slot /></div>' },
  AlertTitle: { template: '<div><slot /></div>' },
  AlertDescription: { template: '<div><slot /></div>' },
  Badge: { template: '<span><slot /></span>' },
  DatePicker: { props: ['disabled'], template: '<input type="date" :disabled="disabled" />' },
  SingleSelect: { template: '<div />' },
  TiptapEditor: { template: '<div />' },
  ImageUpload: { template: '<div />' },
  Link: { template: '<a><slot /></a>' },
  Switch: {
    props: ['modelValue'],
    template: '<button type="button" role="switch" @click="$emit(\'update:modelValue\', !modelValue)" />',
  },
};

const makeDutiable = (overrides: Record<string, unknown> = {}) => ({
  id: 'dutiable-1',
  via_dutiable_id: null,
  start_date: '2024-01-01',
  end_date: null,
  additional_email: null,
  additional_photo: null,
  additional_photo_focal_point: null,
  description: { lt: '', en: '' },
  study_program_id: null,
  use_original_duty_name: false,
  duty: {
    id: 'duty-1',
    name: 'Komunikacijos koordinatorius',
    contacts_grouping: 'none',
  },
  dutiable_id: 'user-1',
  dutiable: {
    id: 'user-1',
    name: 'Ona Onaitė',
    pronouns: 'ji/jos',
    profile_photo_path: null,
    profile_photo_focal_point: null,
  },
  ...overrides,
});

const mountForm = (dutiable = makeDutiable(), studyPrograms: Record<string, unknown>[] = []) =>
  mount(DutiableForm, {
    props: { dutiable, studyPrograms },
    global: { stubs },
  });

describe('DutiableForm.vue — public-contact preview', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('shows the person and the genderized duty name in the preview', () => {
    wrapper = mountForm();

    expect(wrapper.text()).toContain('Ona Onaitė');
    // "ji/jos" pronouns -> feminine inflection of "Komunikacijos koordinatorius".
    expect(wrapper.text()).toContain('Komunikacijos koordinatorė');
  });

  it('switches the preview to the original duty name when the opt-out is toggled', async () => {
    wrapper = mountForm();

    expect(wrapper.text()).toContain('Komunikacijos koordinatorė');

    await wrapper.find('button[role="switch"]').trigger('click');

    expect(wrapper.text()).toContain('Komunikacijos koordinatorius');
    expect(wrapper.text()).not.toContain('Komunikacijos koordinatorė');
  });

  it('appends the study program in brackets when one is already selected', () => {
    wrapper = mountForm(
      makeDutiable({ study_program_id: 'sp-1' }),
      [{ id: 'sp-1', name: 'Informatikos bakalauras', degree: 'BA' }],
    );

    expect(wrapper.text()).toContain('(Informatikos bakalauras)');
  });

  it('shows no study program suffix when none is selected', () => {
    wrapper = mountForm();

    expect(wrapper.text()).not.toMatch(/\(.*\)/);
  });

  it('flags a missing study program when the duty groups contacts by it', () => {
    wrapper = mountForm(makeDutiable({
      study_program_id: null,
      duty: { id: 'duty-1', name: 'Kuratorius', contacts_grouping: 'study_program' },
    }));

    expect(wrapper.text()).toContain('forms.helpers.study_program_required_hint');
  });

  it('does not flag a missing study program for a duty that does not group by it', () => {
    wrapper = mountForm(makeDutiable({ study_program_id: null }));

    expect(wrapper.text()).not.toContain('forms.helpers.study_program_required_hint');
  });

  it('does not flag anything once a study program is set', () => {
    wrapper = mountForm(
      makeDutiable({
        study_program_id: 'sp-1',
        duty: { id: 'duty-1', name: 'Kuratorius', contacts_grouping: 'study_program' },
      }),
      [{ id: 'sp-1', name: 'Informatikos bakalauras', degree: 'BA' }],
    );

    expect(wrapper.text()).not.toContain('forms.helpers.study_program_required_hint');
  });

  it('renders the assignment description in the preview when one is set', () => {
    wrapper = mountForm(makeDutiable({ description: { lt: '<p>Atsakingas už komunikaciją</p>', en: '' } }));

    expect(wrapper.find('[data-testid="dutiable-description-preview"]').text()).toContain('Atsakingas už komunikaciją');
  });

  it('shows no description preview for an empty HTML wrapper', () => {
    wrapper = mountForm(makeDutiable({ description: { lt: '<p></p>', en: '' } }));

    expect(wrapper.find('[data-testid="dutiable-description-preview"]').exists()).toBe(false);
  });

  // Emptiness is measured through textContent rather than a tag-stripping regex,
  // which a single pass cannot do reliably for nested markup.
  it('treats nested markup with no text as an empty description', () => {
    wrapper = mountForm(makeDutiable({ description: { lt: '<p><strong><em>  </em></strong><br></p>', en: '' } }));

    expect(wrapper.find('[data-testid="dutiable-description-preview"]').exists()).toBe(false);
  });

  it('falls back to initials when there is no photo', () => {
    wrapper = mountForm();

    expect(wrapper.text()).toContain('OO');
  });
});

describe('DutiableForm.vue — ex-officio indication', () => {
  let wrapper: ReturnType<typeof mount>;

  const exOfficioDutiable = () => makeDutiable({
    via_dutiable_id: 'dutiable-source',
    via_dutiable: {
      id: 'dutiable-source',
      duty: { id: 'duty-source', name: { lt: 'Pirmininkas', en: 'Chairperson' } },
    },
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('badges the assignment as ex officio and explains where it came from', () => {
    wrapper = mountForm(exOfficioDutiable());

    expect(wrapper.find('[data-testid="ex-officio-badge"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="ex-officio-notice"]').text())
      .toContain('forms.fields.ex_officio_period_managed');
  });

  it('links back to the duty that granted the seat', () => {
    wrapper = mountForm(exOfficioDutiable());

    expect(wrapper.find('[data-testid="ex-officio-notice"]').text())
      .toContain('forms.fields.ex_officio_source_link');
  });

  it('locks the period fields, which the source duty owns', () => {
    wrapper = mountForm(exOfficioDutiable());

    const pickers = wrapper.findAll('input[type="date"]');

    expect(pickers).toHaveLength(2);
    pickers.forEach(picker => expect(picker.attributes('disabled')).toBeDefined());
  });

  it('shows nothing ex-officio for an ordinary assignment', () => {
    wrapper = mountForm();

    expect(wrapper.find('[data-testid="ex-officio-badge"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="ex-officio-notice"]').exists()).toBe(false);
  });
});
