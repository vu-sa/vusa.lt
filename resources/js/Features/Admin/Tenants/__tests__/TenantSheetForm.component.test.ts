import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import TenantSheetForm, { type TenantInput, type TenantInstitution } from '@/Features/Admin/Tenants/TenantSheetForm.vue';
import CollectionSelectContent from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectContent.vue';
import { normalizeHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { createMockForm } from '@/tests/helpers/createMockForm';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('laravel-vue-i18n', () => import('@/mocks/i18n'));

const institution: TenantInstitution = {
  id: '01INSTITUTION', name: 'Padalinio taryba', is_active: false,
  tenant: { id: 7, shortname: 'VU SA MIF' },
};
const tenant: TenantInput & { primary_institution: TenantInstitution | null } = {
  id: 7, fullname: 'VU SA MIF', shortname: 'VU SA MIF', type: 'padalinys',
  alias: 'mif', shortname_vu: 'MIF', primary_institution_id: institution.id,
  primary_institution: institution, goals_enabled: true,
};

const SheetFormStub = {
  props: ['open', 'title'], emits: ['submit', 'cancel', 'update:open'],
  template: `<form v-if="open" @submit.prevent="$emit('submit')"><h1>{{ title }}</h1><slot name="body"><slot /></slot><slot name="footer" /><button type="submit" data-testid="submit">save</button></form>`,
};

const mountForm = (value: typeof tenant | null = null) => mount(TenantSheetForm, {
  props: { open: true, tenant: value },
  global: { stubs: {
    SheetForm: SheetFormStub,
    SpotlightPopover: { template: '<div><slot /></div>' },
    CollectionSelectContent: { props: ['baseFilterBy', 'selectedIds', 'pinnedHits'], emits: ['toggle'], template: '<div data-testid="search"><input type="text" /></div>' },
  } },
});

let form: ReturnType<typeof createMockForm>;
beforeEach(() => {
  vi.clearAllMocks();
  vi.mocked(useForm).mockImplementation((data) => {
    form = createMockForm(data as Record<string, unknown>);
    let defaults = { ...data as Record<string, unknown> };
    form.defaults.mockImplementation((values?: Record<string, unknown>) => {
      defaults = values ?? form.data();
    });
    form.reset.mockImplementation(() => Object.assign(form, defaults));
    return form as unknown as ReturnType<typeof useForm>;
  });
});

const openPicker = async (wrapper: ReturnType<typeof mountForm>) => {
  await wrapper.get('#tenant-primary-institution').trigger('click');
  return wrapper.getComponent(CollectionSelectContent);
};
const hit = normalizeHit('institutions', { id: '01NEWINSTITUTION', name_lt: 'Nauja institucija', tenant_shortname: 'VU SA MIF', is_active: true });

describe('TenantSheetForm', () => {
  it('posts a new tenant and explains why no primary institution can be selected yet', async () => {
    const wrapper = mountForm();
    expect(wrapper.find('#tenant-primary-institution').exists()).toBe(false);
    expect(wrapper.text()).toContain($t('forms.tenants.save_first'));
    await wrapper.get('form').trigger('submit');
    expect(form.post).toHaveBeenCalledWith(expect.stringContaining('tenants.store'), expect.any(Object));
  });

  it('shows the selected institution, its tenant and inactive status, and patches edits', async () => {
    const wrapper = mountForm(tenant);
    expect(wrapper.get('#tenant-primary-institution').text()).toContain(institution.name);
    expect(wrapper.get('#tenant-primary-institution').text()).toContain('VU SA MIF');
    expect(wrapper.getComponent({ name: 'InactiveInstitutionBadge' }).props('active')).toBe(false);
    await wrapper.get('form').trigger('submit');
    expect(form.patch).toHaveBeenCalledWith(expect.stringContaining('tenants.update'), expect.any(Object));
  });

  it('uses a native select for tenant type and shows usage hints', async () => {
    const wrapper = mountForm(tenant);
    expect(wrapper.get('select#tenant-type').findAll('option')).toHaveLength(3);
    await wrapper.get('select#tenant-type').setValue('pkp');
    expect(form.type).toBe('pkp');
    expect(wrapper.text()).toContain($t('forms.tenants.alias_hint'));
    expect(wrapper.text()).toContain($t('forms.tenants.primary_institution_hint'));
    expect(wrapper.text()).not.toContain('forms.');
  });

  it('scopes search to the tenant, pins the current institution and preserves string IDs', async () => {
    const wrapper = mountForm(tenant);
    const picker = await openPicker(wrapper);
    expect(picker.props('baseFilterBy')).toBe('tenant_ids:[7]');
    expect(picker.props('pinnedHits')[0].recordId).toBe(institution.id);
    picker.vm.$emit('toggle', hit);
    await wrapper.get('[data-testid="tenant-picker-confirm"]').trigger('click');
    expect(form.primary_institution_id).toBe('01NEWINSTITUTION');
    expect(wrapper.get('#tenant-primary-institution').text()).toContain('Nauja institucija');
  });

  it('keeps tenant edits and the saved selection when backing out of search', async () => {
    const wrapper = mountForm(tenant);
    await wrapper.get('#tenant-fullname').setValue('Changed name');
    const picker = await openPicker(wrapper);
    picker.vm.$emit('toggle', hit);
    await wrapper.get('form').trigger('submit');
    expect(form.patch).not.toHaveBeenCalled();
    await wrapper.findAll('button').find(button => button.text() === 'Grįžti')!.trigger('click');
    expect(form.primary_institution_id).toBe(institution.id);
    expect(form.fullname).toBe('Changed name');
  });

  it('clears the selection only on confirmation', async () => {
    const wrapper = mountForm(tenant);
    await openPicker(wrapper);
    await wrapper.get('[data-testid="tenant-picker-clear"]').trigger('click');
    expect(form.primary_institution_id).toBe(institution.id);
    await wrapper.get('[data-testid="tenant-picker-confirm"]').trigger('click');
    expect(form.primary_institution_id).toBeNull();
  });

  it('resets picker state when switching tenants and reopening the form', async () => {
    const wrapper = mountForm(tenant);
    await openPicker(wrapper);
    await wrapper.setProps({ tenant: { ...tenant, id: 8, primary_institution_id: null, primary_institution: null } });
    expect(wrapper.find('[data-testid="search"]').exists()).toBe(false);
    expect(form.primary_institution_id).toBeNull();
    await openPicker(wrapper);
    expect(wrapper.getComponent(CollectionSelectContent).props('baseFilterBy')).toBe('tenant_ids:[8]');
    await wrapper.setProps({ open: false });
    await wrapper.setProps({ open: true });
    expect(wrapper.find('[data-testid="search"]').exists()).toBe(false);
  });

  it('shows a warning for legacy cross-tenant selections and never pins them in search', async () => {
    const wrapper = mountForm({ ...tenant, primary_institution: { ...institution, tenant: { id: 9, shortname: 'Other' } } });
    expect(wrapper.get('[role="alert"]').text()).toBe($t('forms.tenants.cross_tenant_warning'));
    const picker = await openPicker(wrapper);
    expect(picker.props('pinnedHits')).toEqual([]);
    expect(picker.props('selectedIds').size).toBe(0);
  });
});
