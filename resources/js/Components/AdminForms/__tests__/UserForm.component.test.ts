import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import UserForm from '@/Components/AdminForms/UserForm.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@/Composables/useDuplicateUserCheck', () => ({
  useDuplicateUserCheck: () => ({ matches: { value: [] } }),
}));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

/** FormPage is stubbed so the test can read the mode it is given and fire its submit. */
const stubs = {
  ...commonStubs,
  FormPage: {
    props: ['title', 'mode'],
    emits: ['submit'],
    template: '<form data-testid="form-page" :data-mode="mode" @submit.prevent="$emit(\'submit\')"><h1>{{ title }}</h1><slot /><slot name="footer-extra" /></form>',
  },
  FormSection: { props: ['title'], template: '<section :data-section="title"><slot /></section>' },
  Input: {
    props: ['modelValue', 'disabled'],
    template: '<input data-testid="input" :disabled="disabled" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
  },
  Checkbox: { props: ['modelValue', 'disabled'], template: '<input type="checkbox" :checked="modelValue" :disabled="disabled" />' },
  MultiSelect: { props: ['id', 'options'], template: '<div :data-testid="id" :data-options="options.map(o => o.label).join(\'|\')" />' },
  ImageUpload: true,
  DuplicateUserWarning: true,
};

const createUser = (overrides: Record<string, unknown> = {}) => ({
  id: 'user-1',
  name: 'Petras Petraitis',
  email: 'petras@stud.vu.lt',
  phone: null,
  facebook_url: null,
  profile_photo_path: null,
  pronouns: { lt: '', en: '' },
  show_pronouns: false,
  last_action: null,
  current_duties: [],
  ...overrides,
});

const tenantsWithDuties = [
  {
    id: 1,
    shortname: 'VU SA MIF',
    institutions: [{ id: 'i1', name: 'MIF taryba', duties: [{ id: 'd1', name: 'Pirmininkas' }, { id: 'd2', name: 'Sekretorius' }] }],
  },
  {
    id: 2,
    shortname: 'VU SA CHGF',
    institutions: [{ id: 'i2', name: 'CHGF taryba', duties: [{ id: 'd3', name: 'Pirmininkas' }] }],
  },
];

const mountForm = (props: Record<string, unknown> = {}) =>
  mount(UserForm, {
    props: { user: createUser(), ...props } as never,
    global: { stubs },
  });

const formOf = (wrapper: ReturnType<typeof mount>) => (wrapper.vm as unknown as { form: Record<string, unknown> }).form;

describe('UserForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: { isSuperAdmin: false } } }) as never);
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('edit', () => {
    it('edits in edit mode and asks only for the person\'s own attributes', () => {
      wrapper = mountForm();

      expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('edit');
      expect(wrapper.findAll('[data-section]').map(section => section.attributes('data-section'))).toEqual([
        'Kas tai?',
        'Kaip su juo susisiekti?',
        'Kaip į jį kreiptis?',
      ]);
    });

    it('never sends duties or roles — they are managed on the record', () => {
      wrapper = mountForm();

      expect(Object.keys(formOf(wrapper))).not.toContain('current_duties');
      expect(Object.keys(formOf(wrapper))).not.toContain('roles');
      expect(wrapper.find('[data-testid="user-duties"]').exists()).toBe(false);
      expect(wrapper.find('[data-testid="user-roles"]').exists()).toBe(false);
    });

    it('hands the form to the page on submit', async () => {
      wrapper = mountForm();

      await wrapper.find('[data-testid="form-page"]').trigger('submit');

      expect(wrapper.emitted('submit:form')).toHaveLength(1);
    });

    it('shows when the person last signed in', () => {
      wrapper = mountForm({ user: createUser({ last_action: '2026-09-01T10:00:00Z' }) });

      expect(wrapper.text()).toContain('Paskutinį kartą prisijungė');
    });
  });

  describe('email warning', () => {
    it('advises against a @vusa.lt address, which usually belongs to a duty', () => {
      wrapper = mountForm({ user: createUser({ email: 'koordinatorius@vusa.lt' }) });

      expect(wrapper.find('[data-testid="duty-email-hint"]').exists()).toBe(true);
    });

    it.each(['petras@stud.vu.lt', 'petras@gmail.com'])('says nothing about %s', (email) => {
      wrapper = mountForm({ user: createUser({ email }) });

      expect(wrapper.find('[data-testid="duty-email-hint"]').exists()).toBe(false);
    });
  });

  describe('identity lock', () => {
    it('leaves email editable by default', () => {
      wrapper = mountForm();

      expect(wrapper.find('#user-email').attributes('disabled')).toBeUndefined();
    });

    it('disables name and email when the actor may not change identity, and explains why', () => {
      wrapper = mountForm({ canUpdateIdentity: false });

      expect(wrapper.find('#user-email').attributes('disabled')).toBeDefined();
      expect(wrapper.find('#user-name').attributes('disabled')).toBeDefined();
      expect(wrapper.text()).toContain('users.identity_locked_hint');
    });

    it('suppresses the @vusa.lt advice when the field is locked', () => {
      wrapper = mountForm({ user: createUser({ email: 'koordinatorius@vusa.lt' }), canUpdateIdentity: false });

      expect(wrapper.find('[data-testid="duty-email-hint"]').exists()).toBe(false);
    });

    it('lets a super admin fix an existing name that is otherwise locked', () => {
      vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: { isSuperAdmin: true } } }) as never);
      wrapper = mountForm();

      expect(wrapper.find('#user-name').attributes('disabled')).toBeUndefined();
    });

    it('keeps an existing name locked for anyone else', () => {
      wrapper = mountForm();

      expect(wrapper.find('#user-name').attributes('disabled')).toBeDefined();
    });
  });

  describe('duty email context', () => {
    it('lists only the @vusa.lt duty emails, which also allow signing in', () => {
      wrapper = mountForm({
        user: createUser({
          current_duties: [
            { id: 'd1', name: 'Koordinatorius', email: 'koordinatorius@vusa.lt' },
            { id: 'd2', name: 'Narys', email: 'narys@gmail.com' },
          ],
        }),
      });

      expect(wrapper.text()).toContain('koordinatorius@vusa.lt');
      expect(wrapper.text()).not.toContain('narys@gmail.com');
    });
  });

  describe('create', () => {
    const createProps = () => ({
      user: createUser({ id: undefined, name: '', email: '' }),
      rememberKey: 'CreateUser',
      tenantsWithDuties,
      permissableTenants: [{ id: 1, shortname: 'VU SA MIF' }],
      roles: [{ id: 1, name: 'Editor' }],
    });

    it('creates in create mode and asks for the first duties', () => {
      wrapper = mountForm(createProps());

      expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('create');
      expect(wrapper.find('[data-testid="user-duties"]').exists()).toBe(true);
      expect(Object.keys(formOf(wrapper))).toContain('current_duties');
    });

    it('offers only duties in tenants the actor may create people in, named with their institution', () => {
      wrapper = mountForm(createProps());

      const options = wrapper.find('[data-testid="user-duties"]').attributes('data-options')!.split('|');

      expect(options).toEqual([
        'Pirmininkas · MIF taryba (VU SA MIF)',
        'Sekretorius · MIF taryba (VU SA MIF)',
      ]);
    });

    it('offers roles to a super admin only', () => {
      wrapper = mountForm(createProps());
      expect(wrapper.find('[data-testid="user-roles"]').exists()).toBe(false);
      wrapper.unmount();

      vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: { isSuperAdmin: true } } }) as never);
      wrapper = mountForm(createProps());
      expect(wrapper.find('[data-testid="user-roles"]').exists()).toBe(true);
    });
  });

  describe('pronouns', () => {
    it('cannot show pronouns publicly until there are some', () => {
      wrapper = mountForm();

      expect(wrapper.find('#user-show-pronouns').attributes('disabled')).toBeDefined();
      wrapper.unmount();

      wrapper = mountForm({ user: createUser({ pronouns: { lt: 'Jie/jų', en: '' } }) });
      expect(wrapper.find('#user-show-pronouns').attributes('disabled')).toBeUndefined();
    });
  });
});
