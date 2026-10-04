import type * as Inertia from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

import DutyForm from '@/Components/AdminForms/DutyForm.vue';
import type { DutySimilarityMatches } from '@/Components/AdminForms/DuplicateDutyWarning.vue';
import { commonStubs } from '@/tests/stubs';

// Use the real Inertia useForm (reactive) rather than the global plain-object mock —
// the reactive headline must react to typing, which only a reactive form gives us.
// Same pattern as NewsForm.component.test.ts.
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual<typeof Inertia>('@inertiajs/vue3');
  return {
    ...actual,
    Head: { name: 'Head', template: '<div style="display:none"><slot /></div>' },
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
  Head: true,
  AdminContentPage: { template: '<div><slot /></div>' },
  AdminForm: { template: '<form @submit.prevent><slot name="status-header" /><slot /></form>' },
  FormElement: { template: '<section><slot name="title" /><slot name="description" /><slot /></section>' },
  FormFieldWrapper: { props: ['hint'], template: '<div data-slot="form-field"><slot /><p>{{ hint }}</p></div>' },
  Alert: { template: '<div><slot /></div>' },
  MultiSelect: { name: 'MultiSelect', props: ['modelValue', 'options', 'id'], template: '<div :id="id" />' },
  SingleSelect: { template: '<div />' },
  NumberField: { template: '<input type="number" />' },
  InstitutionSelectDialog: { template: '<div><slot name="trigger" /></div>' },
  CollectionSelectDialog: { template: '<div><slot name="trigger" /></div>' },
  // Props are declared so tests can read what the form hands each picker.
  TransferList: { name: 'TransferList', props: ['modelValue', 'options', 'lockedOptions'], template: '<div />' },
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

const mountForm = (duty = emptyDuty(), extraProps: Record<string, unknown> = {}) =>
  mount(DutyForm, {
    props: {
      duty,
      dutyTypes: [],
      assignableUsers: [],
      roles: [],
      assignableInstitutions: [],
      assignableTenants: [],
      assignableDuties: [],
      ...extraProps,
    },
    global: {
      stubs,
      mocks: {
        $page: {
          props: {
            app: { locale: 'lt', url: 'https://vusa.test' },
            auth: { user: { isSuperAdmin: true } },
          },
        },
      },
    },
  });

describe('DutyForm.vue — reactive headline', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('keeps a generic headline before a name has been typed', () => {
    wrapper = mountForm();

    // Regression guard: the preview used to read the (empty, on create) `duty` prop
    // instead of the live `form` state, so it silently rendered nothing forever.
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('jie (they)');
    expect(wrapper.get('h1').text()).toBe('Nauja pareigybė');
  });

  it('updates the headline with live masculine/feminine inflection as the admin types', async () => {
    wrapper = mountForm();

    const nameInput = wrapper.find('input[placeholder]');
    await nameInput.setValue('Komunikacijos koordinatorius');
    await nextTick();

    expect(wrapper.get('h1').text()).toContain('Komunikacijos koordinator');
    expect(wrapper.get('[data-testid="form-page-bar-title"]').text()).toBe('Nauja pareigybė');
    const field = wrapper.get('input#duty-name').element.closest('[data-slot="form-field"]');
    expect(field?.querySelector('[data-testid="duty-ending-trigger"]')).toBeNull();
    expect(wrapper.text()).toContain('forms.helpers.duty_name_inflected_hint');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('ius');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
  });

  it('updates the headline live as the typed name keeps changing', async () => {
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

  it('shows an existing duty in the headline immediately, without typing', () => {
    wrapper = mountForm(emptyDuty({ id: 'duty-1', name: { lt: 'Vadovas', en: 'Head' } }));

    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('as');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
  });

  it('animates only the headline and keeps the saved name in the form bar', () => {
    wrapper = mountForm(emptyDuty({ id: 'duty-1', name: { lt: 'Vadovas', en: 'Head' } }));

    expect(wrapper.get('h1 [data-testid="duty-ending-masculine"]').text()).toBe('as');
    expect(wrapper.get('h1 [data-testid="duty-ending-feminine"]').text()).toBe('ė');
    expect(wrapper.get('h1').classes()).toContain('u-display');
    expect(wrapper.get('h1 [data-testid="duty-ending-underline"]').classes()).toContain('bg-brand');
    expect(wrapper.get('[data-testid="form-page-bar-title"]').text()).toBe('Vadovas');
    expect(wrapper.find('[data-testid="form-page-bar-title"] [data-testid="duty-ending-trigger"]').exists()).toBe(false);
  });

  it('updates the edit headline and follows the selected language', async () => {
    wrapper = mountForm(emptyDuty({ id: 'duty-1', name: { lt: 'Vadovas', en: 'Head' } }));
    await wrapper.get('input#duty-name').setValue('Sekretorius');
    expect(wrapper.get('h1 [data-testid="duty-ending-masculine"]').text()).toBe('ius');

    await wrapper.findComponent({ name: 'FormPage' }).vm.$emit('update:locale', 'en');
    await nextTick();
    expect(wrapper.get('h1').text()).toBe('Head');
    expect(wrapper.find('[data-testid="duty-ending-trigger"]').exists()).toBe(false);
    await wrapper.get('input#duty-name').setValue('Coordinator');
    expect(wrapper.get('h1').text()).toBe('Coordinator');
    expect(wrapper.get('[data-testid="form-page-bar-title"]').text()).toBe('Vadovas');
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

describe('DutyForm.vue — ex-officio seats in the assignable-tenants section', () => {
  let wrapper: ReturnType<typeof mount>;

  const tenant = { id: 11, shortname: 'VU SA MIF', type: 'padalinys' };

  const dutyWithTenantRow = (quota: number | null) => emptyDuty({
    id: 'duty-1',
    assignable_tenants: [{ id: tenant.id, shortname: tenant.shortname, pivot: { quota } }],
  });

  const exOfficioMember = {
    dutiable_id: 'dutiable-1',
    user_id: 'user-ex',
    name: 'Jonas Jonaitis',
    tenant_id: tenant.id,
    source_duty_name: 'Pirmininkas',
  };

  afterEach(() => {
    wrapper?.unmount();
  });

  it('counts ex-officio seats towards the tenant occupancy badge', () => {
    // The regression: a tenant whose third seat is held ex officio reported 2/3
    // and looked like it still had room.
    wrapper = mountForm(dutyWithTenantRow(3), {
      assignableTenants: [tenant],
      assignableTenantUsers: { [tenant.id]: ['user-a', 'user-b'] },
      exOfficioMembers: [exOfficioMember],
    });

    expect(wrapper.find(`[data-testid="tenant-occupancy-${tenant.id}"]`).text()).toBe('3 / 3');
  });

  it('reports occupancy from the picked reps alone when no seat is held ex officio', () => {
    wrapper = mountForm(dutyWithTenantRow(3), {
      assignableTenants: [tenant],
      assignableTenantUsers: { [tenant.id]: ['user-a', 'user-b'] },
    });

    expect(wrapper.find(`[data-testid="tenant-occupancy-${tenant.id}"]`).text()).toBe('2 / 3');
  });

  it('shows an unlimited quota rather than a cap when none is set', () => {
    wrapper = mountForm(dutyWithTenantRow(null), {
      assignableTenants: [tenant],
      assignableTenantUsers: { [tenant.id]: ['user-a'] },
      exOfficioMembers: [exOfficioMember],
    });

    expect(wrapper.find(`[data-testid="tenant-occupancy-${tenant.id}"]`).text()).toBe('2 / ∞');
  });

  it('does not render TransferList for member associations (Decision O21 / Forms rule 1 & 15)', () => {
    wrapper = mountForm(emptyDuty({ id: 'duty-1' }), {
      assignableUsers: [
        { id: 'user-ex', name: 'Jonas Jonaitis', is_recent: true },
        { id: 'user-a', name: 'Ona Onaitė', is_recent: true },
      ],
      exOfficioMembers: [{ ...exOfficioMember, tenant_id: null }],
    });

    expect(wrapper.findComponent({ name: 'TransferList' }).exists()).toBe(false);
  });
});

describe('DutyForm.vue — picking which tenants may assign representatives', () => {
  let wrapper: ReturnType<typeof mount>;

  const tenantA = { id: 11, shortname: 'VU SA MIF', type: 'padalinys' };
  const tenantB = { id: 12, shortname: 'VU SA TSPMI', type: 'padalinys' };

  /** The tenant picker MultiSelect on the form. */
  const tenantPicker = (w: ReturnType<typeof mount>) => {
    return w.findAllComponents({ name: 'MultiSelect' }).find(c => c.props('id') === 'assignable-tenants')!;
  };

  const mountWithRows = () => mountForm(
    emptyDuty({
      id: 'duty-1',
      assignable_tenants: [
        { id: tenantA.id, shortname: tenantA.shortname, pivot: { quota: null } },
        { id: tenantB.id, shortname: tenantB.shortname, pivot: { quota: null } },
      ],
    }),
    {
      assignableTenants: [tenantA, tenantB],
      assignableTenantUsers: { [tenantA.id]: ['user-a'], [tenantB.id]: ['user-b', 'user-c'] },
    },
  );

  afterEach(() => {
    wrapper?.unmount();
  });

  it('lists the duty\'s existing assignable tenants as the picker selection', () => {
    wrapper = mountWithRows();

    expect((tenantPicker(wrapper).props('modelValue') as Array<{ id: number }>).map(t => t.id))
      .toEqual([tenantA.id, tenantB.id]);
  });

  it('adds a section for a newly picked tenant', async () => {
    wrapper = mountForm(emptyDuty({
      id: 'duty-1',
      assignable_tenants: [{ id: tenantA.id, shortname: tenantA.shortname, pivot: { quota: null } }],
    }), { assignableTenants: [tenantA, tenantB] });

    tenantPicker(wrapper).vm.$emit('update:modelValue', [tenantA, tenantB]);
    await nextTick();

    expect(wrapper.find(`[data-testid="tenant-occupancy-${tenantB.id}"]`).exists()).toBe(true);
  });

  it('keeps each remaining tenant\'s reps with it when another tenant is dropped', async () => {
    // The reps live in an array parallel to the rows, so dropping a row without
    // dropping its entry would shift every tenant below onto someone else's reps.
    wrapper = mountWithRows();

    tenantPicker(wrapper).vm.$emit('update:modelValue', [tenantB]);
    await nextTick();

    expect(wrapper.find(`[data-testid="tenant-occupancy-${tenantA.id}"]`).exists()).toBe(false);
    expect(wrapper.find(`[data-testid="tenant-occupancy-${tenantB.id}"]`).text()).toBe('2 / ∞');
  });

  it('updates contacts grouping via segmented control', async () => {
    wrapper = mountForm(emptyDuty({ contacts_grouping: 'none' }));

    const programOption = wrapper.find('[data-testid="contacts-grouping-study_program"]');
    expect(programOption.exists()).toBe(true);

    await programOption.trigger('click');
    expect(programOption.attributes('aria-pressed')).toBe('true');
  });

  it('passes mapped type objects to the duty types MultiSelect, not raw IDs', async () => {
    const dutyType1 = { id: 1, title: 'Valdyba' };
    const dutyType2 = { id: 2, title: 'Kuratorius' };
    wrapper = mountForm(
      emptyDuty({
        id: 'duty-1',
        types: [dutyType1],
      }),
      {
        dutyTypes: [dutyType1, dutyType2],
      },
    );

    const typePicker = wrapper.findAllComponents({ name: 'MultiSelect' }).find(c => c.props('id') === 'duty-types');
    expect(typePicker).toBeDefined();
    expect(typePicker!.props('modelValue')).toEqual([dutyType1]);

    typePicker!.vm.$emit('update:modelValue', [dutyType1, dutyType2]);
    await nextTick();

    const { form } = wrapper.vm as unknown as { form: { types: number[] } };
    expect(form.types).toEqual([1, 2]);
  });

  it('passes mapped role objects to the administrative roles MultiSelect, not raw IDs', async () => {
    const role1 = { id: '01gxtwdayy2t13j51wg035f8n0', name: 'Komunikacijos koordinatorius' };
    const role2 = { id: '01h6hzfefycaz9bnqpkanp6bn4', name: 'Išteklių administratorius' };
    wrapper = mountForm(
      emptyDuty({
        id: 'duty-1',
        roles: [role1],
      }),
      {
        roles: [role1, role2],
      },
    );

    const rolePicker = wrapper.findAllComponents({ name: 'MultiSelect' }).find(c => c.props('id') === 'admin_role');
    expect(rolePicker).toBeDefined();
    expect(rolePicker!.props('modelValue')).toEqual([{ label: role1.name, value: role1.id }]);

    rolePicker!.vm.$emit('update:modelValue', [
      { label: role1.name, value: role1.id },
      { label: role2.name, value: role2.id },
    ]);
    await nextTick();

    const { form } = wrapper.vm as unknown as { form: { roles: string[] } };
    expect(form.roles).toEqual([role1.id, role2.id]);
  });
});
