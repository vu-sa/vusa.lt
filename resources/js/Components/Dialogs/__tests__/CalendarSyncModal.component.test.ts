import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import CalendarSyncModal from '../CalendarSyncModal.vue';

describe('CalendarSyncModal', () => {
  it('renders instructions and uses sm:max-w-3xl for a wider dialog', () => {
    const wrapper = mount(CalendarSyncModal, {
      props: {
        showModal: true,
      },
      global: {
        stubs: {
          Dialog: {
            props: ['open'],
            template: '<div v-if="open" data-testid="dialog"><slot /></div>',
          },
          DialogContent: {
            template: '<div data-testid="dialog-content" :class="$attrs.class"><slot /></div>',
          },
          DialogHeader: { template: '<div><slot /></div>' },
          DialogTitle: { template: '<h2><slot /></h2>' },
          DialogDescription: { template: '<p><slot /></p>' },
          Tabs: { template: '<div><slot /></div>' },
          TabsList: { template: '<div><slot /></div>' },
          TabsTrigger: { template: '<button><slot /></button>' },
          TabsContent: { template: '<div><slot /></div>' },
          CopyToClipboardButton: { template: '<button><slot /></button>' },
        },
      },
    });

    const dialogContent = wrapper.find('[data-testid="dialog-content"]');
    expect(dialogContent.exists()).toBe(true);
    expect(dialogContent.classes()).toContain('sm:max-w-3xl');
    expect(wrapper.text()).toContain('Kalendoriaus sinchronizavimo instrukcija');
  });

  it('emits close event when dialog is closed', async () => {
    const wrapper = mount(CalendarSyncModal, {
      props: {
        showModal: true,
      },
      global: {
        stubs: {
          Dialog: {
            props: ['open'],
            template: '<div v-if="open"><slot /><button data-testid="close-btn" @click="$emit(\'update:open\', false)">Close</button></div>',
          },
          DialogContent: { template: '<div><slot /></div>' },
          DialogHeader: { template: '<div><slot /></div>' },
          DialogTitle: { template: '<h2><slot /></h2>' },
          DialogDescription: { template: '<p><slot /></p>' },
          Tabs: { template: '<div><slot /></div>' },
          TabsList: { template: '<div><slot /></div>' },
          TabsTrigger: { template: '<button><slot /></button>' },
          TabsContent: { template: '<div><slot /></div>' },
          CopyToClipboardButton: { template: '<button><slot /></button>' },
        },
      },
    });

    await wrapper.find('[data-testid="close-btn"]').trigger('click');
    expect(wrapper.emitted('close')).toHaveLength(1);
  });
});
