import { useForm } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { beforeEach, expect, it, vi } from 'vitest';

import { SheetForm } from '@/Components/Patterns';
import { commonStubs } from '@/tests/stubs';
import WikiSubjectsSheet from '../WikiSubjectsSheet.vue';
import { KnowledgeItemKind } from '@/Types/enums';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => vi.clearAllMocks());

// The real dialog searches Typesense; this one confirms a single institution.
const InstitutionSelectDialog = defineComponent({
  name: 'InstitutionSelectDialog',
  emits: ['confirm', 'update:open'],
  template: '<div><slot name="trigger" /><button type="button" data-testid="pick" @click="$emit(\'confirm\', [{ recordId: \'01inst\', title: \'Senatas\' }])">pick</button></div>',
});

it('adds an institution picked in the dialog without saving the sheet', async () => {
  const wrapper = mount(WikiSubjectsSheet, {
    props: { open: false, itemId: 'item-1', subjects: [], institutionTypes: [], dutyTypes: [], kind: KnowledgeItemKind.Term },
    global: { stubs: { ...commonStubs, Sheet: commonStubs.Dialog, SheetContent: commonStubs.DialogContent, SheetHeader: commonStubs.DialogHeader, SheetTitle: commonStubs.DialogTitle, SheetDescription: commonStubs.DialogDescription, InstitutionSelectDialog } },
  });
  await wrapper.setProps({ open: true });
  const form = vi.mocked(useForm).mock.results[0]!.value;

  await wrapper.get('[data-testid="pick"]').trigger('click');

  expect(wrapper.get('[data-slot="wiki-subjects"]').text()).toContain('Senatas');
  expect(form.put).not.toHaveBeenCalled();

  await wrapper.getComponent(SheetForm).vm.$emit('submit');
  expect(form.transform).toHaveBeenCalled();
  wrapper.unmount();
});
