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
  AlertDialogAction: { template: '<button type="button" data-testid="confirm" v-bind="$attrs"><slot /></button>' },
};

const mountDialog = (props: Record<string, unknown> = {}) =>
  mount(ConfirmDialog, {
    props: { open: true, title: 'Ištrinti pareigybę?', confirmLabel: 'Ištrinti', ...props },
    global: { stubs },
  });

describe('ConfirmDialog.vue', () => {
  it('names the result on the confirm button and offers a Lithuanian cancel', () => {
    const wrapper = mountDialog({ description: 'Pareigybė bus pašalinta.' });

    expect(wrapper.text()).toContain('Ištrinti pareigybę?');
    expect(wrapper.text()).toContain('Pareigybė bus pašalinta.');
    expect(wrapper.find('[data-testid="confirm"]').text()).toBe('Ištrinti');
    expect(wrapper.find('[data-testid="cancel"]').text()).toBe('Atšaukti');
  });

  it('emits confirm when the action is chosen', async () => {
    const wrapper = mountDialog();

    await wrapper.find('[data-testid="confirm"]').trigger('click');

    expect(wrapper.emitted('confirm')).toHaveLength(1);
  });

  it('gives a destructive confirm its own dark twin, since the default variant has one', () => {
    const wrapper = mountDialog({ destructive: true });
    const classes = wrapper.find('[data-testid="confirm"]').classes();

    expect(classes).toContain('bg-destructive');
    expect(classes).toContain('dark:bg-destructive');
  });

  it('leaves a non-destructive confirm neutral', () => {
    expect(mountDialog().find('[data-testid="confirm"]').classes()).not.toContain('bg-destructive');
  });
});
