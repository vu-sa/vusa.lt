import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { nextTick, reactive, ref } from 'vue';

import DutyResponsibilitiesSection from '@/Features/Admin/Responsibilities/DutyResponsibilitiesSection.vue';
import type { DutyResponsibilityItem, DutyResponsibilityOptions } from '@/Features/Admin/Responsibilities/types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(false), dismiss: vi.fn() }),
}));

const options: DutyResponsibilityOptions = {
  responsibilities: [
    { id: 'role-coordination', title: 'Studentų atstovų koordinavimas', description: null, purpose_label: 'Studentų atstovų koordinavimas', scopes: ['tenant', 'institution_type', 'institution'] },
    { id: 'role-communication', title: 'Komunikacija', description: null, purpose_label: null, scopes: [] },
  ],
  tenants: [{ id: 1, shortname: 'MIF' }],
  types: [{ id: '2', title: 'VU Senatas' }],
  institutions: [{ id: 'inst-1', name: 'Taryba' }],
};

const stubs = {
  Button: { template: '<button><slot /></button>' },
  SpotlightPopover: { template: '<div><slot /></div>' },
  SheetForm: {
    props: ['open'],
    template: '<div v-if="open" data-testid="responsibility-sheet"><slot /></div>',
  },
  ConfirmDialog: true,
  FormFieldWrapper: { template: '<div><slot /></div>' },
  FormSegmentedControl: {
    props: ['modelValue', 'options'],
    emits: ['update:modelValue'],
    template: '<button v-for="option in options" :key="option.value" :data-testid="`scope-${option.value}`" @click="$emit(\'update:modelValue\', option.value)">{{ option.label }}</button>',
  },
  SingleSelect: true,
  NativeSelect: true,
  Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
};

const direct: DutyResponsibilityItem = {
  id: 'grant-1',
  role_id: 'role-coordination',
  title: 'Studentų atstovų koordinavimas',
  purpose: 'student_rep_coordination',
  purpose_label: 'Studentų atstovų koordinavimas',
  source: 'duty',
  source_name: null,
  scope_type: 'tenant',
  scope_name: 'MIF',
  missing_permissions: ['meetings.update.padalinys'],
};

const viaType: DutyResponsibilityItem = {
  ...direct,
  id: 'grant-2',
  role_id: 'role-problems',
  title: 'Problemų redagavimas',
  purpose: null,
  purpose_label: null,
  source: 'duty_type',
  source_name: 'Koordinatoriai',
  scope_type: null,
  scope_name: null,
  missing_permissions: [],
};

function mountSection(items = [direct, viaType], canUpdate = true) {
  return mount(DutyResponsibilitiesSection, {
    props: { dutyId: 'duty-1', tenantId: 1, items, canUpdate, options },
    global: { stubs },
  });
}

beforeEach(() => {
  vi.clearAllMocks();
  vi.stubGlobal('route', (name: string) => `/mocked/${name}`);
  vi.mocked(useForm).mockReturnValue(reactive({
    role_id: 'role-coordination',
    scope_type: 'tenant',
    scope_id: '1',
    errors: {},
    processing: false,
    isDirty: false,
    reset: vi.fn(),
    clearErrors: vi.fn(),
    post: vi.fn(),
    defaults: vi.fn(),
  }) as ReturnType<typeof useForm>);
});

describe('DutyResponsibilitiesSection', () => {
  it('lists direct and inherited responsibilities, with the gap a purpose leaves', () => {
    const wrapper = mountSection();
    const rows = wrapper.findAll('[data-testid="responsibility-row"]');

    expect(rows).toHaveLength(2);
    expect(rows[0]!.text()).toContain('MIF');
    expect(rows[0]!.find('[data-testid="responsibility-gap"]').exists()).toBe(true);
    expect(rows[1]!.find('[data-testid="responsibility-source"]').text()).toContain('responsibilities.duty.via_duty_type');
  });

  it('offers removal only for responsibilities given to the duty itself', () => {
    const rows = mountSection().findAll('[data-testid="responsibility-row"]');

    expect(rows[0]!.find('[data-testid="responsibility-remove"]').exists()).toBe(true);
    expect(rows[1]!.find('[data-testid="responsibility-remove"]').exists()).toBe(false);
  });

  it('shows a compact empty line', () => {
    const wrapper = mountSection([]);

    expect(wrapper.find('[data-testid="responsibility-row"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('responsibilities.duty.empty');
  });

  it('hides add and remove actions without update permission', () => {
    const wrapper = mountSection([direct], false);

    expect(wrapper.find('[data-testid="responsibility-add"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="responsibility-remove"]').exists()).toBe(false);
  });

  it('clears the old target when the scope changes', async () => {
    const wrapper = mountSection();
    const form = vi.mocked(useForm).mock.results[0]?.value;

    await wrapper.get('[data-testid="responsibility-add"]').trigger('click');
    await wrapper.get('[data-testid="scope-institution_type"]').trigger('click');
    await nextTick();

    expect(form.scope_type).toBe('institution_type');
    expect(form.scope_id).toBe('');

    form.scope_id = '2';
    await wrapper.get('[data-testid="scope-tenant"]').trigger('click');
    await nextTick();

    expect(form.scope_id).toBe('1');
  });
});
