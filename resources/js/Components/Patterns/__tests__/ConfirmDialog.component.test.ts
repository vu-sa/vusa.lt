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
});
