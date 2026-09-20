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
  };

  it('renders title, description and actions', () => {
    const wrapper = mount(SheetForm, {
      props: {
        open: true,
        title: 'Priskirti narį',
        description: 'Priskirkite naudotoją pareigoms.',
        saveLabel: 'Išsaugoti',
      },
      slots: {
        default: '<div data-testid="form-body">Form Fields</div>',
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Priskirti narį');
    expect(wrapper.text()).toContain('Priskirkite naudotoją pareigoms.');
    expect(wrapper.find('[data-testid="form-body"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Išsaugoti');
    expect(wrapper.text()).toContain('Atšaukti');
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
});
