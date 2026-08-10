import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref, nextTick } from 'vue';

import DutyForm from '@/Components/AdminForms/DutyForm.vue';
import type { DutySimilarityMatches } from '@/Components/AdminForms/DuplicateDutyWarning.vue';
import { commonStubs } from '@/tests/stubs';

// Use the real Inertia useForm (reactive) rather than the global plain-object mock —
// the genderization preview must react to typing, which only a reactive form gives us.
// Same pattern as NewsForm.component.test.ts.
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({
      props: {
        app: { locale: 'lt', url: 'https://vusa.test' },
        auth: { user: { isSuperAdmin: true } },
      },
    }),
  };
});

// The duplicate-check debounce/query logic has its own unit test
// (useDuplicateDutyCheck.test.ts) — here we only need to control what it returns.
const duplicateMatches = ref<DutySimilarityMatches>({
  same_institution: [],
  other_institution: [],
  other_institution_count: 0,
});
const useDuplicateDutyCheckMock = vi.fn(() => ({
  matches: duplicateMatches,
  isChecking: ref(false),
  check: vi.fn(),
}));
vi.mock('@/Composables/useDuplicateDutyCheck', () => ({
  useDuplicateDutyCheck: (...args: unknown[]) => useDuplicateDutyCheckMock(...args),
}));

const stubs = {
  ...commonStubs,
  AdminForm: { template: '<form @submit.prevent><slot name="status-header" /><slot /></form>' },
  FormElement: { template: '<section><slot name="title" /><slot name="description" /><slot /></section>' },
  FormFieldWrapper: { template: '<div><slot /></div>' },
  Alert: { template: '<div><slot /></div>' },
  AlertDescription: { template: '<div><slot /></div>' },
  MultiSelect: { template: '<div />' },
  SingleSelect: { template: '<div />' },
  NumberField: { template: '<input type="number" />' },
  Select: { template: '<div><slot /></div>' },
  SelectContent: { template: '<div><slot /></div>' },
  SelectItem: { template: '<div><slot /></div>' },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  InstitutionSelectDialog: { template: '<div><slot name="trigger" /></div>' },
  CollectionSelectDialog: { template: '<div><slot name="trigger" /></div>' },
  TransferList: { template: '<div />' },
  Accordion: { template: '<div><slot /></div>' },
  AccordionItem: { template: '<div><slot /></div>' },
  AccordionContent: { template: '<div><slot /></div>' },
  AccordionTrigger: { template: '<div><slot /></div>' },
  TiptapEditor: { template: '<div />' },
  UserAvatar: { template: '<div />' },
  Switch: {
    props: ['modelValue'],
    template: '<button type="button" role="switch" @click="$emit(\'update:modelValue\', !modelValue)" />',
  },
};

const emptyDuty = (overrides: Record<string, unknown> = {}) => ({
  id: undefined,
  name: { lt: '', en: '' },
  email: null,
  institution_id: null,
  places_to_occupy: 1,
  contacts_grouping: 'none',
  description: { lt: '', en: '' },
  current_users: [],
  assignable_tenants: [],
  roles: [],
  types: [],
  ex_officio_target_duties: [],
  ...overrides,
});

beforeEach(() => {
  useDuplicateDutyCheckMock.mockClear();
  duplicateMatches.value = { same_institution: [], other_institution: [], other_institution_count: 0 };
});

const mountForm = (duty = emptyDuty()) =>
  mount(DutyForm, {
    props: {
      duty,
      dutyTypes: [],
      assignableUsers: [],
      roles: [],
      assignableInstitutions: [],
      assignableTenants: [],
      assignableDuties: [],
    },
    global: { stubs },
  });

describe('DutyForm.vue — genderization preview', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('shows no preview before a name has been typed', () => {
    wrapper = mountForm();

    // Regression guard: the preview used to read the (empty, on create) `duty` prop
    // instead of the live `form` state, so it silently rendered nothing forever.
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('jie (they)');
  });

  it('previews the live masculine/feminine inflection as the admin types', async () => {
    wrapper = mountForm();

    const nameInput = wrapper.find('input[placeholder]');
    await nameInput.setValue('Komunikacijos koordinatorius');
    await nextTick();

    expect(wrapper.text()).toContain('Komunikacijos koordinator');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('ius');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
  });

  it('updates the preview live as the typed name keeps changing', async () => {
    wrapper = mountForm();

    const nameInput = wrapper.find('input[placeholder]');
    await nameInput.setValue('Pirmininkas');
    await nextTick();
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('as');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');

    await nameInput.setValue('Sekretorius');
    await nextTick();
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('ius');
    expect(wrapper.text()).not.toContain('Pirminink');
  });

  it('previews an existing duty being edited immediately, without typing', () => {
    wrapper = mountForm(emptyDuty({ id: 'duty-1', name: { lt: 'Vadovas', en: 'Head' } }));

    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('as');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
  });
});

describe('DutyForm.vue — missing-language advisory', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('shows no advisory while the missing-language alert is intentionally disabled', () => {
    wrapper = mountForm();

    expect(wrapper.text()).not.toContain('forms.helpers.duty_name_missing_lt');
    expect(wrapper.text()).not.toContain('forms.helpers.duty_name_missing_en');
  });

  it('shows no advisory when only Lithuanian is filled', () => {
    wrapper = mountForm(emptyDuty({ name: { lt: 'Pirmininkas', en: '' } }));

    expect(wrapper.text()).not.toContain('forms.helpers.duty_name_missing_en');
    expect(wrapper.text()).not.toContain('forms.helpers.duty_name_missing_lt');
  });

  it('shows no advisory when only English is filled', () => {
    wrapper = mountForm(emptyDuty({ name: { lt: '', en: 'Chair' } }));

    expect(wrapper.text()).not.toContain('forms.helpers.duty_name_missing_lt');
    expect(wrapper.text()).not.toContain('forms.helpers.duty_name_missing_en');
  });

  it('shows no advisory when both name locales are filled', () => {
    wrapper = mountForm(emptyDuty({ name: { lt: 'Pirmininkas', en: 'Chair' } }));

    expect(wrapper.text()).not.toContain('forms.helpers.duty_name_missing_lt');
    expect(wrapper.text()).not.toContain('forms.helpers.duty_name_missing_en');
  });
});

describe('DutyForm.vue — duplicate duty warning wiring', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('passes the name and institution to the duplicate check, excluding the duty itself on edit', () => {
    wrapper = mountForm(emptyDuty({ id: 'duty-9', institution_id: 'inst-1', name: { lt: 'Pirmininkas', en: '' } }));

    expect(useDuplicateDutyCheckMock).toHaveBeenCalled();
    const [nameGetter, institutionGetter, excludeGetter] = useDuplicateDutyCheckMock.mock.calls[0] as [
      () => string,
      () => string | null,
      () => string | null,
    ];

    expect(nameGetter()).toBe('Pirmininkas');
    expect(institutionGetter()).toBe('inst-1');
    expect(excludeGetter()).toBe('duty-9');
  });

  it('renders the warning when the composable reports a same-institution variant', async () => {
    duplicateMatches.value = {
      same_institution: [{
        id: 'duty-2',
        name: 'Komunikacijos koordinatorė',
        reason: 'same_institution_variant',
        institution_name: 'VU SA MIF',
        tenant_shortname: 'VU SA MIF',
        current_holder_names: [],
        places_to_occupy: 1,
        can_manage: true,
      }],
      other_institution: [],
      other_institution_count: 0,
    };

    wrapper = mountForm();
    await nextTick();

    expect(wrapper.text()).toContain('forms.duty_duplicate.warning_title');
    expect(wrapper.text()).toContain('forms.duty_duplicate.variant_hint');
  });

  it('shows nothing when the composable reports no matches', () => {
    wrapper = mountForm();

    expect(wrapper.text()).not.toContain('forms.duty_duplicate.warning_title');
  });
});
