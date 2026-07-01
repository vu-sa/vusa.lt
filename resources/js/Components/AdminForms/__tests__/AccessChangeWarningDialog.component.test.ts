import { describe, test, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import AccessChangeWarningDialog from '../AccessChangeWarningDialog.vue';

const alertDialogStubs = {
  AlertDialog: { template: '<div><slot /></div>' },
  AlertDialogContent: { template: '<div><slot /></div>' },
  AlertDialogHeader: { template: '<div><slot /></div>' },
  AlertDialogTitle: { template: '<div><slot /></div>' },
  AlertDialogDescription: { template: '<div><slot /></div>' },
  AlertDialogFooter: { template: '<div><slot /></div>' },
  AlertDialogAction: { template: '<button class="confirm-btn"><slot /></button>' },
  AlertDialogCancel: { template: '<button class="cancel-btn"><slot /></button>' },
  TriangleAlert: true,
};

function mountDialog(report: Record<string, unknown>) {
  return mount(AccessChangeWarningDialog, {
    props: { open: true, report: report as any },
    global: { stubs: alertDialogStubs },
  });
}

describe('AccessChangeWarningDialog', () => {
  test('lists the roles the user would lose', () => {
    const wrapper = mountDialog({
      lostRoles: ['Communication Coordinator', 'Resource Manager'],
      severity: 'warning',
    });

    const text = wrapper.text();
    expect(text).toContain('Communication Coordinator');
    expect(text).toContain('Resource Manager');
  });

  test('renders the intro and note copy', () => {
    const wrapper = mountDialog({
      lostRoles: ['Communication Coordinator'],
      severity: 'warning',
    });

    const text = wrapper.text();
    expect(text).toContain('access_change.intro');
    expect(text).toContain('access_change.note');
  });

  test('emits confirm and cancel from the footer actions', async () => {
    const wrapper = mountDialog({
      lostRoles: ['Communication Coordinator'],
      severity: 'warning',
    });

    await wrapper.find('.confirm-btn').trigger('click');
    await wrapper.find('.cancel-btn').trigger('click');

    expect(wrapper.emitted('confirm')).toHaveLength(1);
    expect(wrapper.emitted('cancel')).toHaveLength(1);
  });
});
