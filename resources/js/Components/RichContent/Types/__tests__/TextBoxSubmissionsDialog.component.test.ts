import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import TextBoxSubmissionsDialog from '../TextBoxSubmissionsDialog.vue';

import { useApi, useApiMutation } from '@/Composables/useApi';
import { commonStubs } from '@/tests/stubs';

vi.mock('@/Composables/useApi', () => ({ useApi: vi.fn(), useApiMutation: vi.fn() }));
vi.mock('@/Composables/useToasts', () => ({ useToasts: () => ({ success: vi.fn(), error: vi.fn() }) }));

describe('TextBoxSubmissionsDialog', () => {
  it('confirms a single deletion and refreshes the responses', async () => {
    const refresh = vi.fn(async () => {});
    const deleteOne = vi.fn(async () => {});
    vi.mocked(useApi).mockReturnValue({
      data: ref([{ id: 'answer-1', text: 'Answer', submitted_by: 'Visitor', created_at: '2026-09-27T10:00:00Z' }]),
      response: ref({ success: true, data: [], meta: { pagination: { total: 1, last_page: 1, per_page: 20 } } }),
      isFetching: ref(false),
      execute: refresh,
    } as unknown as ReturnType<typeof useApi>);
    vi.mocked(useApiMutation)
      .mockReturnValueOnce({ execute: vi.fn(), isFetching: ref(false) } as unknown as ReturnType<typeof useApiMutation>)
      .mockReturnValueOnce({ execute: deleteOne, isFetching: ref(false), response: ref({ success: true, message: 'Deleted' }) } as unknown as ReturnType<typeof useApiMutation>);

    const slot = { template: '<div><slot /></div>' };
    const wrapper = mount(TextBoxSubmissionsDialog, {
      props: { contentPartId: 4 },
      global: {
        stubs: {
          ...commonStubs,
          Dialog: slot,
          DialogTrigger: slot,
          AlertDialog: { props: ['open'], template: '<div v-if="open"><slot /></div>' },
          AlertDialogContent: slot,
          AlertDialogHeader: slot,
          AlertDialogTitle: slot,
          AlertDialogDescription: slot,
          AlertDialogFooter: slot,
          AlertDialogCancel: { template: '<button><slot /></button>' },
          AlertDialogAction: { emits: ['click'], template: '<button @click="$emit(\'click\')"><slot /></button>' },
        },
      },
    });

    await wrapper.find('[aria-label="rich-content.text_box_delete"]').trigger('click');
    expect(wrapper.text()).toContain('rich-content.text_box_delete_confirm_title');

    await wrapper.findAll('button').find(button => button.text() === 'rich-content.text_box_delete')!.trigger('click');
    expect(deleteOne).toHaveBeenCalledOnce();
    expect(refresh).toHaveBeenCalledOnce();
  });
});
