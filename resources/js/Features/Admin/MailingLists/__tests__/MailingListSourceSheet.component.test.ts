import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';

import MailingListSourceSheet from '@/Features/Admin/MailingLists/MailingListSourceSheet.vue';
import type { MailingListSourceOptions } from '@/Features/Admin/MailingLists/types';
import { TenantType } from '@/Types/enums';
import { createMockForm } from '@/tests/helpers/createMockForm';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const options: MailingListSourceOptions = {
  globalScope: false,
  tenants: [{ id: 1, shortname: 'MIF', type: TenantType.Padalinys }],
  institutionTypes: [{ id: 2, title: 'Studentų atstovų organas', parent_id: null }],
  dutyTypes: [{ id: 10, title: 'Studentų atstovas', parent_id: null }],
  institutions: [{ id: 'inst-1', name: 'VU SA · Taryba' }],
  mailingLists: [{ id: 'list-2', address: 'taryba@konf.vusa.lt' }],
};

const stubs = {
  SheetForm: {
    name: 'SheetForm',
    props: ['open', 'disabled'],
    emits: ['submit'],
    template: '<form v-if="open" data-testid="source-sheet" @submit.prevent="$emit(\'submit\')"><slot /></form>',
  },
  FormFieldWrapper: { props: ['id'], template: '<div :data-field="id"><slot /></div>' },
  FormSegmentedControl: true,
  MultiSelect: true,
  SingleSelect: true,
  DatePicker: true,
  DutySearchField: true,
};

let form: ReturnType<typeof makeForm>;

function makeForm(type: string) {
  const initial = {
    type, source_id: '', email: '', display_name: '', note: '', expires_on: null, address_mode: 'holder',
    filters: { tenant_scope: 'all', tenant_ids: [], include_subtypes: true, exclude_institution_type_ids: [], duty_type_ids: [] },
  };
  const mock = createMockForm(structuredClone(initial));
  mock.reset.mockImplementation(() => Object.assign(mock, structuredClone(initial)));
  return mock;
}

function mountSheet(type: string) {
  form = makeForm(type);
  form.transform.mockReturnValue(form);
  vi.mocked(useForm).mockReturnValue(form as unknown as ReturnType<typeof useForm>);

  return mount(MailingListSourceSheet, {
    props: { mailingListId: 'list-1', options, open: true },
    global: { stubs },
  });
}

beforeEach(() => {
  vi.clearAllMocks();
  vi.stubGlobal('route', (name: string, params?: string) => `/mocked/${name}/${params ?? ''}`);
});

describe('MailingListSourceSheet', () => {
  it('asks a manual email source for the address only, with no address mode or tenant scope', () => {
    const wrapper = mountSheet('email');

    expect(wrapper.find('[data-field="source_email"]').exists()).toBe(true);
    expect(wrapper.find('[data-field="source_address_mode"]').exists()).toBe(false);
    expect(wrapper.find('[data-field="source_tenant_scope"]').exists()).toBe(false);
  });

  it('offers tenant scope, subtypes and excluded types for an institution type source', () => {
    const wrapper = mountSheet('institution_type');

    expect(wrapper.find('[data-field="source_address_mode"]').exists()).toBe(true);
    expect(wrapper.find('[data-field="source_tenant_scope"]').exists()).toBe(true);
    expect(wrapper.find('[data-field="source_excluded_types"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('mailing_lists.sources.include_subtypes');
  });

  it('posts to the list and drops the target id for a manual email', async () => {
    const wrapper = mountSheet('email');
    form.email = 'someone@vusa.lt';

    await wrapper.find('[data-testid="source-sheet"]').trigger('submit');

    const transform = form.transform.mock.calls[0]![0] as (data: Record<string, unknown>) => Record<string, unknown>;
    expect(transform({ type: 'email', source_id: 'stale' }).source_id).toBeNull();
    expect(form.post).toHaveBeenCalledWith('/mocked/mailingLists.sources.store/list-1', expect.objectContaining({ preserveScroll: true }));
  });
});

it('reopens a successfully saved manual source with empty fields', async () => {
  const wrapper = mountSheet('email');
  await wrapper.find('#source_email').setValue('someone@vusa.lt');
  await wrapper.find('[data-testid="source-sheet"]').trigger('submit');
  const options = form.post.mock.calls[0]![1] as { onSuccess: () => void };
  options.onSuccess();
  await wrapper.setProps({ open: false });
  await wrapper.setProps({ open: true });

  expect((wrapper.find('#source_email').element as HTMLInputElement).value).toBe('');
  expect(form.defaults).toHaveBeenCalled();
});

it('scopes the duty picker to the authorized tenants', async () => {
  const wrapper = mountSheet('duty');
  expect(wrapper.findComponent({ name: 'DutySearchField' }).props('tenantIds')).toEqual([1]);
});

it('requires a target again after clearing a native picker', async () => {
  const wrapper = mountSheet('institution_type');
  await wrapper.find('#source_type_target').setValue('2');
  expect(wrapper.findComponent({ name: 'SheetForm' }).props('disabled')).toBe(false);
  await wrapper.find('#source_type_target').setValue('');
  expect(wrapper.findComponent({ name: 'SheetForm' }).props('disabled')).toBe(true);
});
