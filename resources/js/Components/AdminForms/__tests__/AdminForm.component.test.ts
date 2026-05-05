import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

describe('AdminForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;
  let form: ReturnType<typeof createMockForm>;
  let confirmMock: ReturnType<typeof vi.fn>;
  let addEventListenerSpy: ReturnType<typeof vi.spyOn>;
  let removeEventListenerSpy: ReturnType<typeof vi.spyOn>;

  const createWrapper = (props = {}, slots = {}) => {
    return mount(AdminForm, {
      props: {
        model: form,
        ...props,
      },
      slots: {
        default: '<div data-testid="form-content">Form content</div>',
        ...slots,
      },
      global: {
        stubs: {
          ...commonStubs,
          IFluentCheckmarkCircle16Filled: {
            template: '<span class="icon-check" />',
          },
          IFluentDelete24Filled: {
            template: '<span class="icon-delete" />',
          },
          IFluentSave24Filled: {
            template: '<span class="icon-save" />',
          },
        },
      },
    });
  };

  beforeEach(() => {
    form = createMockForm({ name: 'Test' });
    confirmMock = vi.fn().mockReturnValue(true);
    vi.stubGlobal('confirm', confirmMock);
    addEventListenerSpy = vi.spyOn(window, 'addEventListener');
    removeEventListenerSpy = vi.spyOn(window, 'removeEventListener');
    vi.clearAllMocks();
  });

  afterEach(() => {
    wrapper?.unmount();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  describe('rendering', () => {
    it('renders form slot content', () => {
      wrapper = createWrapper();

      expect(wrapper.find('[data-testid="form-content"]').exists()).toBe(true);
    });

    it('shows saving indicator when processing', async () => {
      wrapper = createWrapper();
      form.processing = true;
      await nextTick();

      expect(wrapper.text()).toContain('Išsaugoma...');
    });

    it('shows saved indicator when recentlySuccessful', async () => {
      wrapper = createWrapper();
      form.recentlySuccessful = true;
      await nextTick();

      expect(wrapper.text()).toContain('Išsaugota');
    });

    it('shows unsaved changes indicator when isDirty', async () => {
      wrapper = createWrapper();
      form.isDirty = true;
      await nextTick();

      expect(wrapper.text()).toContain('Neišsaugoti pakeitimai');
    });

    it('shows nothing when form is clean and idle', async () => {
      wrapper = createWrapper();
      await nextTick();

      expect(wrapper.text()).not.toContain('Išsaugoma...');
      expect(wrapper.text()).not.toContain('Išsaugota');
      expect(wrapper.text()).not.toContain('Neišsaugoti pakeitimai');
    });
  });

  describe('submit behavior', () => {
    it('emits submit:form when save button is clicked', async () => {
      wrapper = createWrapper();
      const saveButton = wrapper.findAll('button').find(b => b.text().includes('Išsaugoti'));
      expect(saveButton).toBeDefined();

      await saveButton!.trigger('click');

      expect(wrapper.emitted('submit:form')).toBeTruthy();
    });

    it('disables save button during processing', async () => {
      wrapper = createWrapper();
      form.processing = true;
      await nextTick();

      const saveButton = wrapper.findAll('button').find(b => b.text().includes('Išsaugoti'));
      expect(saveButton!.attributes('disabled')).toBeDefined();
    });

    it('enables save button when idle', async () => {
      wrapper = createWrapper();
      await nextTick();

      const saveButton = wrapper.findAll('button').find(b => b.text().includes('Išsaugoti'));
      expect(saveButton!.attributes('disabled')).toBeUndefined();
    });
  });

  describe('delete behavior', () => {
    it('shows delete button when enableDelete is true', () => {
      wrapper = createWrapper({ enableDelete: true });

      const deleteButton = wrapper.findAll('button').find(b => b.text().includes('Ištrinti'));
      expect(deleteButton).toBeDefined();
    });

    it('hides delete button when enableDelete is false', () => {
      wrapper = createWrapper({ enableDelete: false });

      const deleteButton = wrapper.findAll('button').find(b => b.text().includes('Ištrinti'));
      expect(deleteButton).toBeUndefined();
    });

    it('opens delete dialog on delete button click', async () => {
      wrapper = createWrapper({ enableDelete: true });
      const deleteButton = wrapper.findAll('button').find(b => b.text().includes('Ištrinti'));

      await deleteButton!.trigger('click');
      await nextTick();

      expect(wrapper.find('.dialog').exists()).toBe(true);
    });

    it('emits delete event on confirm', async () => {
      wrapper = createWrapper({ enableDelete: true });
      const deleteButton = wrapper.findAll('button').find(b => b.text().includes('Ištrinti'));

      await deleteButton!.trigger('click');
      await nextTick();

      const confirmDeleteButton = wrapper.find('.dialog-footer').findAll('button').find(b => b.text().includes('Ištrinti'));
      await confirmDeleteButton!.trigger('click');

      expect(wrapper.emitted('delete')).toBeTruthy();
    });

    it('closes dialog on cancel', async () => {
      wrapper = createWrapper({ enableDelete: true });
      const deleteButton = wrapper.findAll('button').find(b => b.text().includes('Ištrinti'));

      await deleteButton!.trigger('click');
      await nextTick();

      const cancelButton = wrapper.findAll('button').find(b => b.text().includes('Atšaukti'));
      await cancelButton!.trigger('click');
      await nextTick();

      expect(wrapper.find('.dialog').exists()).toBe(false);
    });
  });

  describe('autosave', () => {
    it('does not autosave by default', async () => {
      vi.useFakeTimers();
      wrapper = createWrapper();
      await nextTick();

      form.isDirty = true;
      await nextTick();
      vi.advanceTimersByTime(5000);

      expect(wrapper.emitted('submit:form')).toBeUndefined();
    });

    it('autosaves 5s after becoming dirty when enabled', async () => {
      vi.useFakeTimers();
      wrapper = createWrapper();
      await nextTick();

      // Enable autosave via the Switch component
      const autosaveSwitch = wrapper.find('[role="switch"]');
      await autosaveSwitch.trigger('click');
      await nextTick();

      form.isDirty = true;
      await nextTick();
      vi.advanceTimersByTime(5000);

      expect(wrapper.emitted('submit:form')).toHaveLength(1);
    });

    it('does not autosave when form is clean', async () => {
      vi.useFakeTimers();
      wrapper = createWrapper();
      await nextTick();

      const autosaveSwitch = wrapper.find('[role="switch"]');
      await autosaveSwitch.trigger('click');
      await nextTick();

      form.isDirty = false;
      await nextTick();
      vi.advanceTimersByTime(5000);

      expect(wrapper.emitted('submit:form')).toBeUndefined();
    });

    it('does not autosave during processing', async () => {
      vi.useFakeTimers();
      wrapper = createWrapper();
      await nextTick();

      const autosaveSwitch = wrapper.find('[role="switch"]');
      await autosaveSwitch.trigger('click');
      await nextTick();

      form.isDirty = true;
      form.processing = true;
      await nextTick();
      vi.advanceTimersByTime(5000);

      expect(wrapper.emitted('submit:form')).toBeUndefined();
    });

    it('does not autosave on create pages', async () => {
      vi.useFakeTimers();
      vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path: '/mano/forms/create' } }));

      wrapper = createWrapper();
      await nextTick();

      const autosaveSwitch = wrapper.find('[role="switch"]');
      await autosaveSwitch.trigger('click');
      await nextTick();

      form.isDirty = true;
      await nextTick();
      vi.advanceTimersByTime(5000);

      expect(wrapper.emitted('submit:form')).toBeUndefined();
      vi.mocked(usePage).mockRestore?.();
    });
  });

  describe('navigation guard', () => {
    it('blocks navigation with confirm dialog when dirty', () => {
      wrapper = createWrapper();
      form.isDirty = true;

      const event = { detail: { visit: { prefetch: false } }, preventDefault: vi.fn() };
      (router as any).__triggerBefore(event);

      expect(confirmMock).toHaveBeenCalled();
      expect(event.preventDefault).not.toHaveBeenCalled();
    });

    it('cancels navigation when user clicks Cancel on confirm', () => {
      confirmMock.mockReturnValue(false);
      wrapper = createWrapper();
      form.isDirty = true;

      const event = { detail: { visit: { prefetch: false } }, preventDefault: vi.fn() };
      (router as any).__triggerBefore(event);

      expect(confirmMock).toHaveBeenCalled();
      expect(event.preventDefault).toHaveBeenCalled();
    });

    it('allows navigation when form is clean', () => {
      wrapper = createWrapper();
      form.isDirty = false;

      const event = { detail: { visit: { prefetch: false } }, preventDefault: vi.fn() };
      (router as any).__triggerBefore(event);

      expect(confirmMock).not.toHaveBeenCalled();
      expect(event.preventDefault).not.toHaveBeenCalled();
    });

    it('allows navigation when processing', () => {
      wrapper = createWrapper();
      form.isDirty = true;
      form.processing = true;

      const event = { detail: { visit: { prefetch: false } }, preventDefault: vi.fn() };
      (router as any).__triggerBefore(event);

      expect(confirmMock).not.toHaveBeenCalled();
      expect(event.preventDefault).not.toHaveBeenCalled();
    });

    it('allows navigation when isSubmitting is true', async () => {
      wrapper = createWrapper();
      form.isDirty = true;

      // Trigger a submit to set isSubmitting = true
      const saveButton = wrapper.findAll('button').find(b => b.text().includes('Išsaugoti'));
      await saveButton!.trigger('click');

      const event = { detail: { visit: { prefetch: false } }, preventDefault: vi.fn() };
      (router as any).__triggerBefore(event);

      expect(confirmMock).not.toHaveBeenCalled();
      expect(event.preventDefault).not.toHaveBeenCalled();
    });

    it('skips prefetch requests', () => {
      wrapper = createWrapper();
      form.isDirty = true;

      const event = { detail: { visit: { prefetch: true } }, preventDefault: vi.fn() };
      (router as any).__triggerBefore(event);

      expect(confirmMock).not.toHaveBeenCalled();
      expect(event.preventDefault).not.toHaveBeenCalled();
    });
  });

  describe('beforeunload', () => {
    it('prevents unload when form is dirty', () => {
      wrapper = createWrapper();
      form.isDirty = true;

      const event = new Event('beforeunload') as BeforeUnloadEvent & { returnValue: string };
      const handler = addEventListenerSpy.mock.calls.find(
        (call: any) => call[0] === 'beforeunload',
      )?.[1] as EventListener;

      expect(handler).toBeDefined();
      handler(event);

      expect(event.returnValue).toBeTruthy();
    });

    it('allows unload when form is clean', () => {
      wrapper = createWrapper();
      form.isDirty = false;

      const event = new Event('beforeunload') as BeforeUnloadEvent;
      const handler = addEventListenerSpy.mock.calls.find(
        (call: any) => call[0] === 'beforeunload',
      )?.[1] as EventListener;

      expect(handler).toBeDefined();
      handler(event);

      expect(event.defaultPrevented).toBe(false);
    });
  });

  describe('create page detection', () => {
    it('disables autosave toggle on create pages', async () => {
      vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path: '/mano/forms/create' } }));

      wrapper = createWrapper();
      await nextTick();

      const autosaveSwitch = wrapper.find('[role="switch"]');
      expect(autosaveSwitch.attributes('disabled')).toBeDefined();
    });

    it('shows tooltip explaining disabled autosave on create pages', async () => {
      vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path: '/mano/forms/create' } }));

      wrapper = createWrapper();
      await nextTick();

      expect(wrapper.find('.tooltip-content').exists()).toBe(true);
    });

    it('respects isCreateForm prop override', async () => {
      wrapper = createWrapper({ isCreateForm: true });
      await nextTick();

      const autosaveSwitch = wrapper.find('[role="switch"]');
      expect(autosaveSwitch.attributes('disabled')).toBeDefined();
    });
  });
});
