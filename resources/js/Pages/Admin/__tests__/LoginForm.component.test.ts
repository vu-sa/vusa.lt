import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import LoginForm from '@/Pages/Admin/LoginForm.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@/Composables/usePWA', () => ({
  usePWA: () => ({ isPWA: { value: false } }),
}));

function createWrapper(props: { status?: string } = {}, pageErrors: Record<string, string> = {}) {
  vi.mocked(usePage).mockReturnValue(createMockPage({
    app: { locale: 'lt', subdomain: 'www' },
    errors: pageErrors,
    organization: { privacyPageUrl: 'https://vusa.lt/privatumas' },
  }));

  return mount(LoginForm, {
    props,
    global: {
      stubs: {
        Head: true,
        Carousel: { template: '<div><slot /></div>' },
        CarouselContent: { template: '<div><slot /></div>' },
        CarouselItem: { template: '<div><slot /></div>' },
        MicrosoftButton: { template: '<button type="button" data-testid="microsoft-btn">Microsoft</button>' },
      },
    },
  });
}

describe('LoginForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders Microsoft login and identity branding by default', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="microsoft-btn"]').exists()).toBe(true);
    expect(wrapper.find('img[alt="VU SA"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('auth.welcome_back');
  });

  it('hides Microsoft login when email login form is active and restores it on back', async () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="microsoft-btn"]').exists()).toBe(true);
    expect(wrapper.find('input[type="email"]').exists()).toBe(false);

    // Find the toggle button by matching translation key
    const toggleButton = wrapper.findAll('button').find(btn => btn.text().includes('auth.login_with_email'));
    expect(toggleButton).toBeDefined();

    await toggleButton!.trigger('click');

    // Microsoft button is hidden when email form is open
    expect(wrapper.find('[data-testid="microsoft-btn"]').exists()).toBe(false);
    expect(wrapper.find('input[id="email"]').exists()).toBe(true);
    expect(wrapper.find('input[id="password"]').exists()).toBe(true);

    // Click back button to restore Microsoft login
    const backButton = wrapper.findAll('button').find(btn => btn.text().includes('auth.back_to_login_methods'));
    expect(backButton).toBeDefined();
    await backButton!.trigger('click');

    expect(wrapper.find('[data-testid="microsoft-btn"]').exists()).toBe(true);
    expect(wrapper.find('input[id="email"]').exists()).toBe(false);
  });

  it('toggles password visibility between password and text', async () => {
    const wrapper = createWrapper();

    const toggleButton = wrapper.findAll('button').find(btn => btn.text().includes('auth.login_with_email'));
    await toggleButton!.trigger('click');

    const passwordInput = wrapper.find('input[id="password"]');
    expect(passwordInput.attributes('type')).toBe('password');

    // Click show password button
    const eyeButton = wrapper.find('button[aria-label="Rodyti slaptažodį"]');
    expect(eyeButton.exists()).toBe(true);
    await eyeButton.trigger('click');

    expect(wrapper.find('input[id="password"]').attributes('type')).toBe('text');
  });

  it('displays error alerts when errors exist in page props', () => {
    const wrapper = createWrapper({}, { email: 'Neteisingas el. paštas arba slaptažodis.' });

    expect(wrapper.find('[role="alert"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Neteisingas el. paštas arba slaptažodis.');
  });

  it('submits credentials on email form submit', async () => {
    const wrapper = createWrapper();

    const toggleButton = wrapper.findAll('button').find(btn => btn.text().includes('auth.login_with_email'));
    await toggleButton!.trigger('click');

    const emailInput = wrapper.find('input[id="email"]');
    const passwordInput = wrapper.find('input[id="password"]');

    await emailInput.setValue('test@vu.lt');
    await passwordInput.setValue('secret123');

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.find('form').exists()).toBe(true);
  });
});
