import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import ShowProfile from '../ShowProfile.vue';

import { commonStubs } from '@/tests/stubs';
import { ModelEnum } from '@/Types/enums';

const patchCalls: Array<{ url: string; options?: Record<string, unknown> }> = [];

vi.mock('@inertiajs/vue3', async () => {
  const actual = await import('@/mocks/inertia.mock');
  return {
    ...actual,
    useForm: (initialData: Record<string, unknown>) => {
      const form = actual.useForm(initialData);
      form.patch = vi.fn((url: string, options?: Record<string, unknown>) => {
        patchCalls.push({ url, options });
        if (options?.onSuccess) {
          (options.onSuccess as () => void)();
        }
      });
      return form;
    },
  };
});

const executeMutationMock = vi.fn();
vi.mock('@/Composables/useApi', () => ({
  useApiMutation: vi.fn(() => ({
    execute: executeMutationMock.mockResolvedValue(undefined),
    isSuccess: { value: true },
  })),
}));

const resetInitializationMock = vi.fn();
vi.mock('@/Composables/useTutorialProgress', () => ({
  resetInitialization: () => resetInitializationMock(),
}));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const stubs = {
  ...commonStubs,
  FormPage: {
    props: ['title', 'barTitle', 'headTitle', 'mode', 'processing', 'dirty', 'errors', 'backHref', 'entityType'],
    emits: ['submit'],
    template: '<form data-testid="form-page" :data-mode="mode" :data-entity="entityType" @submit.prevent="$emit(\'submit\')"><h1>{{ title }}</h1><slot /><slot name="aside" /></form>',
  },
  FormPanel: { props: ['title', 'flush'], template: '<div data-testid="form-panel" :data-panel="title" :data-flush="flush"><slot /></div>' },
  FormSection: { props: ['title', 'description', 'badge', 'publicMarker'], template: '<section :data-section="title"><slot /></section>' },
  FormToggleRow: {
    props: ['label', 'hint', 'modelValue'],
    template: '<div data-testid="form-toggle-row" :data-label="label"><input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" /></div>',
  },
  FormFieldWrapper: { props: ['id', 'label', 'error'], template: '<div :data-field="id" :data-error="error"><slot /></div>' },
  Input: {
    props: ['modelValue', 'disabled', 'id', 'type'],
    template: '<input :id="id" :type="type" data-testid="input" :disabled="disabled" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
  },
  Checkbox: {
    props: ['modelValue', 'disabled', 'id'],
    template: '<input :id="id" type="checkbox" :checked="modelValue" :disabled="disabled" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
  },
  MultiLocaleInput: {
    props: ['input', 'id'],
    template: '<div :id="id" data-testid="multi-locale-input" />',
  },
  ImageUpload: {
    props: ['url', 'focalPointValue'],
    template: '<div data-testid="image-upload" />',
  },
  Link: {
    props: ['href'],
    template: '<a :href="href" data-testid="link-row"><slot /></a>',
  },
};

const createMockUser = (overrides: Record<string, unknown> = {}) => ({
  id: 'user-1',
  name: 'Vardenis Pavardenis',
  email: 'vardenis@stud.vu.lt',
  phone: '+37061234567',
  facebook_url: 'https://facebook.com/vardenis',
  profile_photo_path: '/photos/vardenis.jpg',
  profile_photo_focal_point: null,
  pronouns: { lt: 'jis/jo', en: 'he/him' },
  show_pronouns: true,
  name_was_changed: false,
  has_password: true,
  created_at: '2025-01-01T10:00:00Z',
  updated_at: '2025-01-02T10:00:00Z',
  ...overrides,
});

const mountProfile = (userOverrides: Record<string, unknown> = {}) => {
  return mount(ShowProfile, {
    props: {
      user: createMockUser(userOverrides) as never,
    },
    global: { stubs },
  });
};

describe('ShowProfile.vue', () => {
  beforeEach(() => {
    patchCalls.length = 0;
    vi.clearAllMocks();
  });

  afterEach(() => {
    patchCalls.length = 0;
  });

  it('renders FormPage layout with user entity mark and title', () => {
    const wrapper = mountProfile();

    const formPage = wrapper.find('[data-testid="form-page"]');
    expect(formPage.exists()).toBe(true);
    expect(formPage.attributes('data-mode')).toBe('edit');
    expect(formPage.attributes('data-entity')).toBe(ModelEnum.USER);
    expect(wrapper.text()).toContain('Vardenis Pavardenis');
  });

  it('omits redundant section questions and public markers', () => {
    const wrapper = mountProfile();

    expect(wrapper.text()).not.toContain('Kas tu?');
    expect(wrapper.text()).not.toContain('Tavo paskyros ir tapatybės informacija sistemoje.');
    expect(wrapper.text()).not.toContain('Kaip su tavimi susisiekti?');
    expect(wrapper.text()).not.toContain('Matoma vusa.lt');
    expect(wrapper.text()).not.toContain('Nuotrauka ir kontaktai rodomi viešame vusa.lt puslapyje, kai turi pareigybę.');
  });

  it('renders identity fields with editable name when name was not previously changed', () => {
    const wrapper = mountProfile({ name_was_changed: false });

    const nameField = wrapper.find('[data-field="user-name"]');
    expect(nameField.exists()).toBe(true);

    const nameInput = nameField.find('input');
    expect(nameInput.attributes('disabled')).toBeUndefined();
    expect(nameField.text()).toContain('Paskyros vardą galima pakeisti tik VIENĄ kartą!');
  });

  it('disables name input and shows explanatory note when name was already changed', () => {
    const wrapper = mountProfile({ name_was_changed: true });

    const nameField = wrapper.find('[data-field="user-name"]');
    const nameInput = nameField.find('input');
    expect(nameInput.attributes('disabled')).toBeDefined();
    expect(nameField.text()).toContain('Paskyros vardas jau buvo pakeistas');
  });

  it('renders email input as disabled with identity lock hint', () => {
    const wrapper = mountProfile();

    const emailField = wrapper.find('[data-field="user-email"]');
    expect(emailField.exists()).toBe(true);

    const emailInput = emailField.find('input');
    expect(emailInput.attributes('disabled')).toBeDefined();
    expect(emailField.text()).toContain('users.identity_locked_hint');
  });

  it('renders contacts inputs: phone, facebook and picture upload', () => {
    const wrapper = mountProfile();

    expect(wrapper.find('[data-field="user-phone"]').exists()).toBe(true);
    expect(wrapper.find('[data-field="user-facebook"]').exists()).toBe(true);
    expect(wrapper.find('[data-field="user-picture"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="image-upload"]').exists()).toBe(true);
  });

  it('renders password change section when user has password', () => {
    const wrapper = mountProfile({ has_password: true });

    expect(wrapper.find('[data-field="current-password"]').exists()).toBe(true);
    expect(wrapper.find('[data-field="new-password"]').exists()).toBe(true);
    expect(wrapper.find('[data-field="password-confirmation"]').exists()).toBe(true);
  });

  it('omits password change section when user has no password', () => {
    const wrapper = mountProfile({ has_password: false });

    expect(wrapper.find('[data-field="current-password"]').exists()).toBe(false);
    expect(wrapper.find('[data-field="new-password"]').exists()).toBe(false);
  });

  it('renders aside panels including FormToggleRow for animations and flush link rows', () => {
    const wrapper = mountProfile();

    const panels = wrapper.findAll('[data-testid="form-panel"]');
    expect(panels.length).toBeGreaterThanOrEqual(4);

    expect(wrapper.find('[data-field="user-pronouns"]').exists()).toBe(true);

    // FormToggleRow for animations
    const toggleRow = wrapper.find('[data-testid="form-toggle-row"]');
    expect(toggleRow.exists()).toBe(true);
    expect(toggleRow.attributes('data-label')).toContain('Puslapių animacijos');

    // Flush link rows for related pages
    const links = wrapper.findAll('[data-testid="inertia-link"]');
    expect(links.length).toBe(2);
    expect(links[0].attributes('href')).toContain('profile.notifications');
    expect(links[1].attributes('href')).toContain('profile.roles');
  });

  it('submits profile form to profile.update on FormPage submit', async () => {
    const wrapper = mountProfile();

    await wrapper.find('form').trigger('submit');

    expect(patchCalls.some(call => call.url === '/mocked/profile.update')).toBe(true);
  });

  it('submits password form to profile.updatePassword when clicking password change button', async () => {
    const wrapper = mountProfile({ has_password: true });

    const passwordSection = wrapper.find('[data-section="Slaptažodžio keitimas"]');
    const button = passwordSection.find('button');
    await button.trigger('click');

    expect(patchCalls.some(call => call.url === '/mocked/profile.updatePassword')).toBe(true);
  });

  it('triggers tutorials reset when clicking reset tutorials button', async () => {
    const wrapper = mountProfile();

    const tutorialsPanel = wrapper.find('[data-panel="Vadovų nustatymai"]');
    const button = tutorialsPanel.find('button');
    await button.trigger('click');

    expect(executeMutationMock).toHaveBeenCalled();
  });
});
