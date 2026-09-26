import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { describe, it, expect, vi } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { SHELL_FORM_BAR_ID, createShellFocusProvider, type ShellFocusContext } from '@/Composables/useShellFocus';
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

  it('keeps the missing-translation notice in place across languages, so the form never shifts', async () => {
    const wrapper = mount(FormPage, {
      props: { title: 'Išteklius', locale: 'lt', availableLocales: ['lt', 'en'], missingLocaleCounts: { lt: 0, en: 2 } },
      global: { stubs },
    });
    const notice = () => wrapper.get('[data-testid="form-page-missing-locale"]');

    expect(wrapper.get('[role="group"]').classes()).toContain('h-11');
    expect(notice().classes()).toContain('h-11');
    expect(notice().classes()).toContain('pointer-coarse:h-12');
    expect(notice().classes()).toContain('invisible');
    expect(notice().attributes('aria-hidden')).toBe('true');

    await wrapper.setProps({ locale: 'en' });

    expect(notice().classes()).not.toContain('invisible');
    expect(notice().attributes('aria-hidden')).toBe('false');
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

  describe('layout', () => {
    it('puts the aside next to the fields in a two-column form', () => {
      const wrapper = mount(FormPage, {
        props: { title: 'Puslapis' },
        slots: { default: '<div data-testid="main">Laukai</div>', aside: '<div data-testid="side">Paskelbimas</div>' },
        global: { stubs },
      });

      const form = wrapper.find('form');
      expect(form.classes()).toContain('lg:grid-cols-[1.6fr_1fr]');
      expect(form.find('[data-testid="form-page-aside"] [data-testid="side"]').exists()).toBe(true);
      expect(wrapper.attributes('class')).toContain('max-w-6xl');
    });

    it('stays a single column without an aside', () => {
      const wrapper = mount(FormPage, { props: { title: 'Forma' }, global: { stubs } });

      expect(wrapper.find('[data-testid="form-page-aside"]').exists()).toBe(false);
      expect(wrapper.find('form').classes()).not.toContain('lg:grid-cols-[1.6fr_1fr]');
    });

    it('closes a two-column form\'s aside with the record facts and the danger zone', () => {
      const wrapper = mount(FormPage, {
        props: { title: 'Puslapis', createdAt: '2026-01-02T10:00:00Z', updatedAt: '2026-02-03T10:00:00Z' },
        slots: {
          'aside': '<div data-testid="side">Paskelbimas</div>',
          'danger-zone': '<button data-testid="delete">Ištrinti</button>',
        },
        global: { stubs },
      });

      const aside = wrapper.find('[data-testid="form-page-aside"]');
      expect(aside.find('[data-testid="form-page-meta"]').text()).toContain('Sukurta');
      expect(aside.find('[data-testid="form-page-meta"]').text()).toContain('Atnaujinta');
      expect(aside.find('[data-testid="form-page-danger-zone"] [data-testid="delete"]').exists()).toBe(true);
      expect(wrapper.findAll('[data-testid="delete"]')).toHaveLength(1);
    });

    it('keeps the danger zone under the fields in a single-column form', () => {
      const wrapper = mount(FormPage, {
        props: { title: 'Forma' },
        slots: { 'danger-zone': '<button data-testid="delete">Ištrinti</button>' },
        global: { stubs },
      });

      expect(wrapper.find('form [data-testid="delete"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="form-page-meta"]').exists()).toBe(false);
    });

    it('titles the bar with the saved record and the heading with the edit', () => {
      const wrapper = mount(FormPage, {
        props: { title: 'Naujas pavadinimas', barTitle: 'Išsaugotas pavadinimas' },
        global: { stubs },
      });

      expect(wrapper.find('[data-testid="form-page-bar-title"]').text()).toBe('Išsaugotas pavadinimas');
      expect(wrapper.find('h1').text()).toBe('Naujas pavadinimas');
    });

    it('offers the public view and change history from props', () => {
      const wrapper = mount(FormPage, {
        props: { title: 'Puslapis', publicUrl: 'https://www.vusa.test/lt/puslapis', activitySubject: { type: 'page', id: 7 } },
        global: { stubs: { ...stubs, ActivityLogSheet: { props: ['subjectType', 'subjectId'], template: '<div data-testid="activity" :data-subject="`${subjectType}:${subjectId}`" />' } } },
      });

      const bar = wrapper.find('[data-testid="form-page-bar"]');
      expect(bar.find('a[href="https://www.vusa.test/lt/puslapis"]').text()).toContain('Peržiūrėti viešai');
      expect(bar.find('[data-testid="activity"]').attributes('data-subject')).toBe('page:7');
    });

    it('gives phones their own save bar tied to the form', () => {
      const wrapper = mount(FormPage, { props: { title: 'Forma' }, global: { stubs } });

      const formId = wrapper.find('form').attributes('id');
      const save = wrapper.find('[data-testid="form-page-mobile-save"] button');

      expect(save.attributes('type')).toBe('submit');
      expect(save.attributes('form')).toBe(formId);
    });
  });

  describe('shell focus mode', () => {
    function mountInShell() {
      const showForm = ref(true);
      let focus: ShellFocusContext | undefined;

      const Shell = defineComponent({
        setup() {
          focus = createShellFocusProvider();
          return () => h('div', [
            h('div', { id: SHELL_FORM_BAR_ID }),
            showForm.value ? h(FormPage, { title: 'Puslapis', backHref: '/mano/pages' }) : null,
          ]);
        },
      });

      const wrapper = mount(Shell, { global: { stubs }, attachTo: document.body });

      return { wrapper, showForm, focus: () => focus! };
    }

    it('takes over the shell bar while open and hands it back on leave', async () => {
      const { wrapper, showForm, focus } = mountInShell();
      await nextTick();

      expect(focus().isFocused.value).toBe(true);
      expect(document.querySelector(`#${SHELL_FORM_BAR_ID} [data-testid="form-page-bar"]`)).not.toBeNull();

      showForm.value = false;
      await nextTick();

      expect(focus().isFocused.value).toBe(false);
      wrapper.unmount();
    });
  });
});
