import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { commonStubs } from '@/tests/stubs';

describe('SheetForm.vue', () => {
  const stubs = {
    ...commonStubs,
    Sheet: { template: '<div><slot /></div>' },
    SheetContent: { template: '<div><slot /></div>' },
    SheetHeader: { template: '<div><slot /></div>' },
    SheetTitle: { template: '<h2><slot /></h2>' },
    SheetDescription: { template: '<p><slot /></p>' },
    ConfirmDialog: {
      props: ['open'],
      emits: ['confirm', 'update:open'],
      template: '<div v-if="open" data-testid="discard"><button type="button" data-testid="discard-yes" @click="$emit(\'confirm\')" /></div>',
    },
  };

  it('renders title, description and actions', () => {
    const wrapper = mount(SheetForm, {
      props: {
        open: true,
        title: 'Priskirti narį',
        description: 'Priskirk naudotoją pareigoms.',
        saveLabel: 'Išsaugoti',
      },
      slots: {
        default: '<div data-testid="form-body">Form Fields</div>',
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Priskirti narį');
    expect(wrapper.text()).toContain('Priskirk naudotoją pareigoms.');
    expect(wrapper.find('[data-testid="form-body"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Išsaugoti');
    expect(wrapper.text()).toContain('Atšaukti');
  });

  it('shows required guidance only when the caller has mixed fields', async () => {
    const wrapper = mount(SheetForm, {
      props: { open: true, title: 'Forma' },
      slots: { default: '<input id="first-field" />' },
      global: { stubs },
    });

    expect(wrapper.find('[data-slot="form-required-note"]').exists()).toBe(false);
    await wrapper.setProps({ requiredNote: true });
    const note = wrapper.get('[data-slot="form-required-note"]');
    expect(note.text()).toBe('forms.required_note');
    expect(note.element.nextElementSibling?.id).toBe('first-field');
    await wrapper.setProps({ requiredNote: false });
    expect(wrapper.find('[data-slot="form-required-note"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('emits submit on form submission', async () => {
    const wrapper = mount(SheetForm, {
      props: {
        open: true,
        title: 'Test Form',
      },
      global: { stubs },
    });

    await wrapper.find('form').trigger('submit.prevent');
    expect(wrapper.emitted('submit')).toHaveLength(1);
  });

  it('shows the saved record dates after the fields and updates them when the record changes', async () => {
    const wrapper = mount(SheetForm, {
      props: { open: true, title: 'Žyma', createdAt: '2026-10-01T10:15:00Z', updatedAt: '2026-10-27T14:30:00Z' },
      slots: { default: '<input id="name" />' },
      global: { stubs },
    });

    expect(wrapper.get('[data-slot="model-timestamps"]').text()).toContain('2026-10-27 16:30');
    expect(wrapper.get('[data-slot="model-timestamps"]').element.previousElementSibling?.id).toBe('name');

    await wrapper.setProps({ createdAt: null, updatedAt: null });
    expect(wrapper.find('[data-slot="model-timestamps"]').exists()).toBe(false);
  });

  it('replaces the fields and save action with picker content without opening another dialog', () => {
    const wrapper = mount(SheetForm, {
      props: { open: true, title: 'Picker' },
      slots: {
        default: '<input data-testid="hidden-field" />',
        body: '<div data-testid="picker-body">Search</div>',
        footer: '<button type="button" data-testid="picker-confirm">Confirm</button>',
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="hidden-field"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="picker-body"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="picker-confirm"]').exists()).toBe(true);
    expect(wrapper.find('button[type="submit"]').exists()).toBe(false);
  });

  it('emits cancel and update:open false on cancel button click', async () => {
    const wrapper = mount(SheetForm, {
      props: {
        open: true,
        title: 'Test Form',
      },
      global: { stubs },
    });

    const cancelButton = wrapper.findAll('button').find(b => b.text().includes('Atšaukti'));
    await cancelButton?.trigger('click');

    expect(wrapper.emitted('cancel')).toHaveLength(1);
    expect(wrapper.emitted('update:open')).toEqual([[false]]);
  });

  describe('unsaved changes', () => {
    const cancel = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('button').find(b => b.text().includes('Atšaukti'))!;

    it('asks before throwing edits away, and only closes once confirmed', async () => {
      const wrapper = mount(SheetForm, { props: { open: true, title: 'Forma', dirty: true }, global: { stubs } });

      await cancel(wrapper).trigger('click');

      expect(wrapper.emitted('update:open')).toBeUndefined();
      expect(wrapper.find('[data-testid="discard"]').exists()).toBe(true);

      await wrapper.find('[data-testid="discard-yes"]').trigger('click');
      expect(wrapper.emitted('cancel')).toHaveLength(1);
      expect(wrapper.emitted('update:open')).toEqual([[false]]);
    });

    it('closes straight away when nothing changed', async () => {
      const wrapper = mount(SheetForm, { props: { open: true, title: 'Forma', dirty: false }, global: { stubs } });

      await cancel(wrapper).trigger('click');

      expect(wrapper.find('[data-testid="discard"]').exists()).toBe(false);
      expect(wrapper.emitted('update:open')).toEqual([[false]]);
    });
  });

  it('puts destructive actions in the body, not the footer', () => {
    const wrapper = mount(SheetForm, {
      props: { open: true, title: 'Forma' },
      slots: { 'default': '<p>Laukai</p>', 'danger-zone': '<button type="button">Ištrinti</button>' },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Ištrinti');
    expect(wrapper.text()).toContain('Atšaukti');
  });
});
