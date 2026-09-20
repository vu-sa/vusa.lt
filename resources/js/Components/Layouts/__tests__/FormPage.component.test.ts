import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ModelEnum } from '@/Types/enums';

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual<any>('@inertiajs/vue3');
  return {
    ...actual,
    Head: { name: 'Head', template: '<div style="display:none"><slot /></div>' },
    Link: { name: 'Link', template: '<a><slot /></a>' },
    usePage: () => ({
      props: {
        app: { locale: 'lt', url: 'https://vusa.test' },
        auth: { user: { isSuperAdmin: true } },
      },
    }),
  };
});

describe('FormPage.vue', () => {
  const stubs = {
    Head: true,
    Link: true,
    AdminContentPage: { template: '<div><slot /></div>' },
    EntityTypeMark: { template: '<div data-testid="entity-type-mark" />' },
  };

  it('renders form header with title, lead, and entity type mark', () => {
    const wrapper = mount(FormPage, {
      props: {
        title: 'Pareigybės forma',
        lead: 'Sukurkite naują pareigybę',
        entityType: ModelEnum.DUTY,
        backHref: '/mano/duties',
        backLabel: 'Pareigybės',
      },
      slots: {
        default: '<div data-testid="form-content">Fields</div>',
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Pareigybės forma');
    expect(wrapper.text()).toContain('Sukurkite naują pareigybę');
    expect(wrapper.find('[data-testid="entity-type-mark"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="form-content"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Pareigybės');
  });

  it('displays sticky save bar with save button and status indicator', () => {
    const wrapper = mount(FormPage, {
      props: {
        title: 'Test Form',
        dirty: true,
        saveLabel: 'Išsaugoti pakeitimus',
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Neišsaugota');
    expect(wrapper.text()).toContain('Išsaugoti pakeitimus');
  });

  it('renders validation error summary when errors prop is populated', () => {
    const wrapper = mount(FormPage, {
      props: {
        title: 'Test Form',
        errors: {
          name: 'Pavadinimas yra privalomas',
          email: 'Neteisingas el. pašto formatas',
        },
      },
      global: { stubs },
    });

    expect(wrapper.find('[role="alert"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Pavadinimas yra privalomas');
    expect(wrapper.text()).toContain('Neteisingas el. pašto formatas');
  });

  it('emits update:locale on language button click', async () => {
    const wrapper = mount(FormPage, {
      props: {
        title: 'Test Form',
        locale: 'lt',
        availableLocales: ['lt', 'en'],
      },
      global: { stubs },
    });

    const enButton = wrapper.findAll('button').find(b => b.text().includes('EN'));
    await enButton?.trigger('click');

    expect(wrapper.emitted('update:locale')).toEqual([['en']]);
  });
});
