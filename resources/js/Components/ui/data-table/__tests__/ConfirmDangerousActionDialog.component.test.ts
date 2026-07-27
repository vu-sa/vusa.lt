import { describe, it, expect, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';

import ConfirmDangerousActionDialog from '@/Components/ui/data-table/ConfirmDangerousActionDialog.vue';
import { commonStubs } from '@/tests/stubs';

describe('ConfirmDangerousActionDialog', () => {
  let wrapper: ReturnType<typeof mount>;

  const mountDialog = (props: Record<string, unknown> = {}) => mount(ConfirmDangerousActionDialog, {
    props: {
      open: true,
      title: 'trash.permanently_delete',
      description: 'trash.permanently_delete_description',
      confirmationText: 'Test record',
      confirmLabel: 'trash.permanently_delete',
      ...props,
    },
    global: {
      stubs: {
        ...commonStubs,
      },
    },
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('keeps confirm disabled until the exact confirmation text is typed', async () => {
    wrapper = mountDialog();

    const confirmButton = wrapper.findAll('button')
      .find(button => button.text() === 'trash.permanently_delete');

    expect(confirmButton).toBeDefined();
    expect(confirmButton!.attributes('disabled')).toBeDefined();

    await wrapper.find('input').setValue('test record');

    expect(confirmButton!.attributes('disabled')).toBeDefined();

    await wrapper.find('input').setValue('Test record');

    expect(confirmButton!.attributes('disabled')).toBeUndefined();
  });

  it('enables confirmation without typing when no confirmation text is available', () => {
    wrapper = mountDialog({ confirmationText: null });

    const confirmButton = wrapper.findAll('button')
      .find(button => button.text() === 'trash.permanently_delete');

    expect(wrapper.find('input').exists()).toBe(false);
    expect(confirmButton!.attributes('disabled')).toBeUndefined();
  });
});
