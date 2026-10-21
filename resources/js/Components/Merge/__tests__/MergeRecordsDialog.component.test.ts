import { flushPromises, mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import MergeRecordsDialog from '@/Components/Merge/MergeRecordsDialog.vue';
import UserMergeDetails from '@/Components/Merge/UserMergeDetails.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const records = [
  { id: 'kept', label: 'Jonas Jonaitis', context: 'jonas@stud.vu.lt', email: 'jonas@stud.vu.lt', can_update_identity: true },
  { id: 'other', label: 'Jonas Jonaitis', context: 'jonas@gmail.com', email: 'jonas@gmail.com', can_update_identity: true },
];

const mountDialog = (props: Record<string, unknown> = {}) => mount({
  components: { MergeRecordsDialog, UserMergeDetails },
  data: () => ({ loginEmail: null as string | null, records, props }),
  template: `
    <MergeRecordsDialog
      open
      type="users"
      :records="records"
      submit-url="/merge"
      target-field="kept_user_id"
      source-field="source_user_ids"
      :extra-payload="{ login_email: loginEmail }"
      v-bind="props"
    >
      <template #details="{ targetId }">
        <UserMergeDetails v-model="loginEmail" :records="records" :target-id="targetId" />
      </template>
    </MergeRecordsDialog>`,
}, { global: { stubs: { ...commonStubs, UserMergeDutyEmails: true } } });

const recordButton = (wrapper: ReturnType<typeof mount>, label: string) =>
  wrapper.findAll('button').find(button => button.text().includes(label))!;

describe('MergeRecordsDialog.vue', () => {
  beforeEach(() => vi.mocked(router.post).mockClear());

  it('sends the extra payload along with the chosen target and sources', async () => {
    const wrapper = mountDialog();

    await recordButton(wrapper, 'jonas@gmail.com').trigger('click');
    await recordButton(wrapper, 'Sujungti').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      '/merge',
      { login_email: 'jonas@gmail.com', kept_user_id: 'other', source_user_ids: ['kept'] },
      expect.any(Object),
    );
  });

  it('preselects the kept record passed in, so a "merge into this one" link opens ready to confirm', async () => {
    const wrapper = mountDialog({ initialTargetId: 'other' });
    await flushPromises();
    await recordButton(wrapper, 'Sujungti').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      '/merge',
      expect.objectContaining({ kept_user_id: 'other', source_user_ids: ['kept'] }),
      expect.anything(),
    );
  });

  it('prevents submitting while merge details are loading', async () => {
    const wrapper = mountDialog({ submitDisabled: true });
    await recordButton(wrapper, 'jonas@gmail.com').trigger('click');

    expect(recordButton(wrapper, 'Sujungti').attributes('disabled')).toBeDefined();
    await recordButton(wrapper, 'Sujungti').trigger('click');
    expect(router.post).not.toHaveBeenCalled();
  });

  it('only lets a keepable record be the target, and preselects it when it is the only one', async () => {
    const wrapper = mountDialog({ keepableIds: ['kept'] });
    await flushPromises();

    expect(recordButton(wrapper, 'jonas@gmail.com').attributes('disabled')).toBeDefined();

    await recordButton(wrapper, 'Sujungti').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      '/merge',
      expect.objectContaining({ kept_user_id: 'kept', source_user_ids: ['other'], login_email: 'jonas@stud.vu.lt' }),
      expect.any(Object),
    );
  });
});
