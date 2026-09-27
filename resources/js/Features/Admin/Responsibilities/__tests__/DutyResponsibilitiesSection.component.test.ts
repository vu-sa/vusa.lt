import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { nextTick, reactive, ref } from 'vue';

import DutyResponsibilitiesSection from '@/Features/Admin/Responsibilities/DutyResponsibilitiesSection.vue';
import type { DutyResponsibilityOptions } from '@/Features/Admin/Responsibilities/types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(false), dismiss: vi.fn() }),
}));

const options: DutyResponsibilityOptions = {
  responsibilities: [{ value: 'student_rep_coordination', label: 'Studentų atstovų koordinavimas', description: '', scopes: ['tenant', 'type', 'institution'] }],
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
  Select: true,
  SelectTrigger: true,
  SelectValue: true,
  SelectContent: true,
  SelectItem: true,
};

const item = {
  id: 'assignment-1',
  responsibility: 'student_rep_coordination',
  label: 'Studentų atstovų koordinavimas',
  scope_type: 'tenant' as const,
  scope_id: '1',
  scope_name: 'MIF',
};

function mountSection(items = [item], canUpdate = true) {
  return mount(DutyResponsibilitiesSection, {
    props: { dutyId: 'duty-1', tenantId: 1, items, roles: [{ id: 'role-1', name: 'Redaktorius' }], canUpdate, options },
    global: { stubs },
  });
}

beforeEach(() => {
  vi.clearAllMocks();
  vi.stubGlobal('route', (name: string) => `/mocked/${name}`);
  vi.mocked(useForm).mockReturnValue(reactive({
    responsibility: 'student_rep_coordination',
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
  it('renders assigned responsibilities and roles', () => {
    const wrapper = mountSection();

    expect(wrapper.findAll('[data-testid="responsibility-row"]')).toHaveLength(1);
    expect(wrapper.text()).toContain('Studentų atstovų koordinavimas');
    expect(wrapper.text()).toContain('MIF');
    expect(wrapper.findAll('[data-testid="duty-role-row"]')).toHaveLength(1);
  });

  it('shows a compact empty line', () => {
    const wrapper = mountSection([]);

    expect(wrapper.find('[data-testid="responsibility-row"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('responsibilities.duty.empty');
  });

  it('hides add and remove actions without update permission', () => {
    const wrapper = mountSection([item], false);

    expect(wrapper.find('[data-testid="responsibility-add"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="responsibility-remove"]').exists()).toBe(false);
  });

  it('clears the old target when the scope changes', async () => {
    const wrapper = mountSection();
    const form = vi.mocked(useForm).mock.results[0]?.value;

    await wrapper.get('[data-testid="responsibility-add"]').trigger('click');
    await wrapper.get('[data-testid="scope-type"]').trigger('click');
    await nextTick();

    expect(form.scope_type).toBe('type');
    expect(form.scope_id).toBe('');

    form.scope_id = '2';
    await wrapper.get('[data-testid="scope-tenant"]').trigger('click');
    await nextTick();

    expect(form.scope_id).toBe('1');
  });
});
