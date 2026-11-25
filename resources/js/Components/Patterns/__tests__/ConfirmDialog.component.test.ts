import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';

const passthrough = { template: '<div><slot /></div>' };
const stubs = {
  AlertDialog: passthrough,
  AlertDialogContent: passthrough,
  AlertDialogHeader: passthrough,
  AlertDialogFooter: passthrough,
  AlertDialogTitle: { template: '<h2><slot /></h2>' },
  AlertDialogDescription: { template: '<p><slot /></p>' },
  AlertDialogCancel: { template: '<button type="button" data-testid="cancel"><slot /></button>' },
};

const mountDialog = (props: Record<string, unknown> = {}) =>
  mount(ConfirmDialog, {
    props: { open: true, title: 'Ištrinti pareigybę?', confirmLabel: 'Ištrinti', ...props },
    global: { stubs },
  });

const confirmButton = (wrapper: ReturnType<typeof mountDialog>) => wrapper.find('[data-slot="confirm-dialog-action"]');

describe('ConfirmDialog.vue', () => {
  it('names the result on the confirm button and offers a Lithuanian cancel', () => {
    const wrapper = mountDialog({ description: 'Pareigybė bus pašalinta.' });

    expect(wrapper.text()).toContain('Ištrinti pareigybę?');
    expect(wrapper.text()).toContain('Pareigybė bus pašalinta.');
    expect(confirmButton(wrapper).text()).toBe('Ištrinti');
    expect(wrapper.find('[data-testid="cancel"]').text()).toBe('Atšaukti');
  });

  it('emits confirm when the action is chosen', async () => {
    const wrapper = mountDialog();

    await confirmButton(wrapper).trigger('click');

    expect(wrapper.emitted('confirm')).toHaveLength(1);
  });

  /** Callers clear their target on close; confirming first is what leaves them something to act on. */
  it('confirms before it closes', async () => {
    const events: string[] = [];
    const wrapper = mountDialog({
      'onConfirm': () => events.push('confirm'),
      'onUpdate:open': (open: boolean) => events.push(`open:${open}`),
    });

    await confirmButton(wrapper).trigger('click');

    expect(events).toEqual(['confirm', 'open:false']);
  });

  it('gives a destructive confirm its own dark twin, since the default variant has one', () => {
    const wrapper = mountDialog({ destructive: true });
    const classes = confirmButton(wrapper).classes();

    expect(classes).toContain('bg-destructive');
    expect(classes).toContain('dark:bg-destructive');
  });

  it('leaves a non-destructive confirm neutral', () => {
    expect(confirmButton(mountDialog()).classes()).not.toContain('bg-destructive');
  });

  it('keeps an irreversible confirm disabled until the exact text is typed', async () => {
    const wrapper = mountDialog({ confirmationText: 'Test record' });

    expect(confirmButton(wrapper).attributes('disabled')).toBeDefined();

    await wrapper.find('input').setValue('test record');
    expect(confirmButton(wrapper).attributes('disabled')).toBeDefined();

    await wrapper.find('input').setValue('Test record');
    expect(confirmButton(wrapper).attributes('disabled')).toBeUndefined();

    await confirmButton(wrapper).trigger('click');
    expect(wrapper.emitted('confirm')).toHaveLength(1);
  });

  it('asks for no typing when there is no confirmation text', () => {
    const wrapper = mountDialog({ confirmationText: null });

    expect(wrapper.find('input').exists()).toBe(false);
    expect(confirmButton(wrapper).attributes('disabled')).toBeUndefined();
  });

  it('confirms a typed deletion with Enter and clears the text when reopened', async () => {
    const wrapper = mountDialog({ confirmationText: 'Test record' });
    const input = wrapper.find('input');

    await input.setValue('Test');
    await input.trigger('keydown', { key: 'Enter' });
    expect(wrapper.emitted('confirm')).toBeUndefined();

    await input.setValue('Test record');
    await input.trigger('keydown', { key: 'Enter' });
    expect(wrapper.emitted('confirm')).toHaveLength(1);

    await wrapper.setProps({ open: false });
    await wrapper.setProps({ open: true });
    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('');
  });
});
