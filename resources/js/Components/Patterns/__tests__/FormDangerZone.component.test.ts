import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import FormDangerZone from '@/Components/Patterns/FormDangerZone.vue';

const ConfirmDialog = {
  name: 'ConfirmDialog',
  props: { open: Boolean, title: String, confirmLabel: String, destructive: Boolean },
  emits: ['update:open', 'confirm'],
  template: '<div v-if="open" data-testid="confirm"><button data-testid="confirm-action" @click="$emit(\'confirm\')">{{ confirmLabel }}</button></div>',
};

const mountZone = () => mount(FormDangerZone, {
  props: { label: 'Ištrinti pareigybę', title: 'Ištrinti pareigybę?' },
  global: { stubs: { ConfirmDialog } },
});

describe('FormDangerZone', () => {
  it('asks before it deletes', async () => {
    const wrapper = mountZone();
    expect(wrapper.find('[data-testid="confirm"]').exists()).toBe(false);

    await wrapper.get('button').trigger('click');
    expect(wrapper.findComponent({ name: 'ConfirmDialog' }).props()).toMatchObject({ open: true, destructive: true, confirmLabel: 'Ištrinti' });
    expect(wrapper.emitted('confirm')).toBeUndefined();

    await wrapper.get('[data-testid="confirm-action"]').trigger('click');
    expect(wrapper.emitted('confirm')).toHaveLength(1);
  });

  it('uses the outlined destructive button', () => {
    expect(mountZone().get('button').classes()).toContain('text-destructive');
  });
});
