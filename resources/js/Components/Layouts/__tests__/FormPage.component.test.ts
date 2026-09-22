import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { describe, it, expect, vi } from 'vitest';
import { nextTick } from 'vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ModelEnum } from '@/Types/enums';

describe('FormPage.vue', () => {
  const stubs = {
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

  describe('editing state', () => {
    it('says it is editing, and offers a saved state only for an existing record', () => {
      const edit = mount(FormPage, { props: { title: 'Pareigybė' }, global: { stubs } });
      const create = mount(FormPage, { props: { title: 'Nauja pareigybė', mode: 'create' }, global: { stubs } });

      expect(edit.find('[data-testid="form-page-eyebrow"]').text()).toBe('Redaguoji');
      expect(edit.text()).toContain('Visi pakeitimai išsaugoti');

      expect(create.find('[data-testid="form-page-eyebrow"]').text()).toBe('Kuri naują');
      expect(create.text()).not.toContain('Visi pakeitimai išsaugoti');
    });

    it('still shows unsaved changes on a create form', () => {
      const wrapper = mount(FormPage, { props: { title: 'Nauja', mode: 'create', dirty: true }, global: { stubs } });

      expect(wrapper.text()).toContain('Neišsaugota');
    });

    it('renders view mode without save actions', () => {
      const wrapper = mount(FormPage, { props: { title: 'Renginys', mode: 'view' }, global: { stubs } });

      expect(wrapper.find('[data-testid="form-page-eyebrow"]').text()).toBe('Peržiūri');
      expect(wrapper.text()).not.toContain('Išsaugoti');
      expect(wrapper.text()).not.toContain('Visi pakeitimai išsaugoti');
    });

    it('ties the save button to the form so it submits without a click handler', () => {
      const wrapper = mount(FormPage, { props: { title: 'Forma' }, global: { stubs } });

      const formId = wrapper.find('form').attributes('id');
      const save = wrapper.findAll('button').find(b => b.text().includes('Išsaugoti'));

      expect(formId).toBeTruthy();
      expect(save?.attributes('form')).toBe(formId);
      expect(save?.attributes('type')).toBe('submit');
    });
  });

  describe('keyboard', () => {
    it('submits with Ctrl+Enter and with Cmd+Enter', async () => {
      const wrapper = mount(FormPage, { props: { title: 'Forma' }, global: { stubs }, attachTo: document.body });
      await nextTick(); // the shortcut listener attaches once the form ref is set
      const form = wrapper.find('form');

      await form.trigger('keydown', { key: 'Enter', ctrlKey: true });
      expect(wrapper.emitted('submit')).toHaveLength(1);

      form.element.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', metaKey: true, bubbles: true, cancelable: true }));
      expect(wrapper.emitted('submit')).toHaveLength(2);
      wrapper.unmount();
    });

    it('does not submit by keyboard while saving or disabled', async () => {
      const saving = mount(FormPage, { props: { title: 'Forma', processing: true }, global: { stubs }, attachTo: document.body });
      const disabled = mount(FormPage, { props: { title: 'Forma', disabled: true }, global: { stubs }, attachTo: document.body });
      await nextTick();

      await saving.find('form').trigger('keydown', { key: 'Enter', ctrlKey: true });
      await disabled.find('form').trigger('keydown', { key: 'Enter', ctrlKey: true });

      expect(saving.emitted('submit')).toBeUndefined();
      expect(disabled.emitted('submit')).toBeUndefined();
      saving.unmount();
      disabled.unmount();
    });

    it('does not submit by keyboard in view mode', async () => {
      const wrapper = mount(FormPage, { props: { title: 'Renginys', mode: 'view' }, global: { stubs }, attachTo: document.body });
      await nextTick();

      await wrapper.find('form').trigger('keydown', { key: 'Enter', ctrlKey: true });

      expect(wrapper.emitted('submit')).toBeUndefined();
      wrapper.unmount();
    });

    it('cancels with Escape and goes back', async () => {
      const visit = vi.spyOn(router, 'visit').mockImplementation(() => undefined);
      const wrapper = mount(FormPage, { props: { title: 'Forma', backHref: '/mano/duties' }, global: { stubs }, attachTo: document.body });
      await nextTick(); // the shortcut listener attaches once the form ref is set

      await wrapper.find('form').trigger('keydown', { key: 'Escape' });

      expect(wrapper.emitted('cancel')).toHaveLength(1);
      expect(visit).toHaveBeenCalledWith('/mano/duties');
      visit.mockRestore();
      wrapper.unmount();
    });

    it('leaves Escape alone when something inside already handled it', async () => {
      const wrapper = mount(FormPage, { props: { title: 'Forma', backHref: '/mano/duties' }, global: { stubs }, attachTo: document.body });
      await nextTick(); // the shortcut listener attaches once the form ref is set
      const form = wrapper.find('form').element;
      form.addEventListener('keydown', e => e.preventDefault(), { capture: true });

      form.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }));

      expect(wrapper.emitted('cancel')).toBeUndefined();
      wrapper.unmount();
    });
  });

  describe('error summary', () => {
    it('links each message to its field, using a mapped id when the key differs', async () => {
      const target = document.createElement('input');
      target.id = 'duty-name';
      target.focus = vi.fn();
      target.scrollIntoView = vi.fn();
      document.body.appendChild(target);

      const wrapper = mount(FormPage, {
        props: {
          title: 'Forma',
          errors: { 'name.lt': 'Pavadinimas yra privalomas' },
          fieldIds: { 'name.lt': 'duty-name' },
        },
        global: { stubs },
      });

      await wrapper.find('[data-testid="form-page-errors"] button').trigger('click');

      expect(target.focus).toHaveBeenCalled();
      target.remove();
    });

    it('brings the summary into view and focuses it when errors first appear', async () => {
      const wrapper = mount(FormPage, { props: { title: 'Forma' }, global: { stubs }, attachTo: document.body });
      await nextTick(); // the shortcut listener attaches once the form ref is set

      await wrapper.setProps({ errors: { email: 'Neteisingas el. paštas' } });
      const summary = wrapper.find('[data-testid="form-page-errors"]').element as HTMLElement;
      summary.scrollIntoView = vi.fn();
      summary.focus = vi.fn();
      await wrapper.setProps({ errors: { email: 'Neteisingas el. paštas', name: 'Privalomas' } });
      await wrapper.vm.$nextTick();

      expect(summary.focus).toHaveBeenCalled();
      wrapper.unmount();
    });
  });
});
