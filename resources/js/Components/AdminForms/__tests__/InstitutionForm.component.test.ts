import type * as Inertia from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionForm from '@/Components/AdminForms/InstitutionForm.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

/** FormPage is stubbed so the test can read the mode it is given and fire its submit. */
const stubs = {
  ...commonStubs,
  FormPage: {
    name: 'FormPage',
    props: ['title', 'barTitle', 'mode', 'errors'],
    emits: ['submit'],
    template: '<form data-testid="form-page" :data-mode="mode" @submit.prevent="$emit(\'submit\')"><h1>{{ title }}</h1><slot /><slot name="aside" /><slot name="advanced" /><slot name="danger-zone" /></form>',
  },
  FormSection: { props: ['title'], template: '<section :data-section="title"><slot /></section>' },
  MultiSelect: true,
  TiptapEditor: true,
  ImageUpload: true,
  InstitutionScopeBadge: true,
  ConfirmDialog: true,
};

const types = [
  { id: 1, title: 'VU SA padalinys', parent_id: null, extra_attributes: { governance_scope: 'vusa' } },
  { id: 2, title: 'VU organas', parent_id: null, extra_attributes: { governance_scope: 'vu' } },
];

const institution = {
  id: 'inst-1',
  name: { lt: 'VU SA FF', en: '' },
  short_name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  address: { lt: '', en: '' },
  working_hours: { lt: '', en: '' },
  alias: 'ff',
  is_active: true,
  tenant_id: 2,
  types: [1],
};

const mountForm = (props: Record<string, unknown> = {}) =>
  mount(InstitutionForm, {
    props: { institution, institutionTypes: types, assignableTenants: [], ...props } as never,
    global: { stubs },
  });

describe('InstitutionForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('updates the draft headline while keeping the saved title in the top bar', async () => {
    const actual = await vi.importActual<typeof Inertia>('@inertiajs/vue3');
    vi.mocked(useForm).mockImplementationOnce(actual.useForm);
    wrapper = mountForm();
    const barTitle = wrapper.findComponent({ name: 'FormPage' }).props('barTitle');
    await wrapper.get('input#institution-name').setValue('VU SA MIF');
    expect(wrapper.findComponent({ name: 'FormPage' }).props('title')).toBe('VU SA MIF');
    expect(wrapper.findComponent({ name: 'FormPage' }).props('barTitle')).toBe(barTitle);
    expect(barTitle).not.toBe('VU SA MIF');
  });

  it('edits in edit mode and creates in create mode', () => {
    wrapper = mountForm();
    expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('edit');
    wrapper.unmount();

    wrapper = mountForm({ rememberKey: 'CreateInstitution' });
    expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('create');
  });

  it('keeps duties, terms and secretaries out of the form — they are managed on the record', () => {
    wrapper = mountForm();

    const sections = wrapper.findAll('[data-section]').map(section => section.attributes('data-section'));

    expect(sections).toEqual(['Kas tai?', 'Kaip su ja susisiekti?']);
    expect(wrapper.text()).not.toContain('Pareigybės');
  });

  it('asks for contact details only for a body VU SA runs itself', () => {
    wrapper = mountForm();
    expect(wrapper.find('[data-section="Kaip su ja susisiekti?"]').exists()).toBe(true);
    wrapper.unmount();

    wrapper = mountForm({ institution: { ...institution, types: [2] } });
    expect(wrapper.find('[data-section="Kaip su ja susisiekti?"]').exists()).toBe(false);
  });

  it('hands the form to the page on submit', async () => {
    wrapper = mountForm();

    await wrapper.find('[data-testid="form-page"]').trigger('submit');

    expect(wrapper.emitted('submit:form')).toHaveLength(1);
  });

  it('renders assignable tenants in the tenant picker', () => {
    wrapper = mountForm({
      assignableTenants: [
        { id: 1, shortname: 'Centrinis biuras' },
        { id: 2, shortname: 'FF' },
      ],
    });

    const select = wrapper.find('select#institution-tenant');
    expect(select.exists()).toBe(true);
    expect(select.element.value).toBe('2');

    const options = select.findAll('option');
    expect(options).toHaveLength(3); // placeholder + 2 tenants
    expect(options[1].text()).toBe('Centrinis biuras');
    expect(options[2].text()).toBe('FF');
  });

  it('offers deletion only when the page enables it', () => {
    wrapper = mountForm();
    expect(wrapper.text()).not.toContain('Ištrinti instituciją');
    wrapper.unmount();

    wrapper = mountForm({ enableDelete: true });
    expect(wrapper.text()).toContain('Ištrinti instituciją');
  });
});
