import { usePage } from '@inertiajs/vue3';
import { ref, watch, type MaybeRefOrGetter, toValue } from 'vue';

import { useApiMutation } from '@/Composables/useApi';
import type { ChecklistItemStatus, OnboardingChecklistData } from '@/Components/Onboarding/types';
import type { PageProps } from '@/Types/index';

export function useOnboarding(initial: MaybeRefOrGetter<OnboardingChecklistData>) {
  const page = usePage<PageProps>();
  const checklist = ref(toValue(initial));
  const saving = ref(false);
  const failed = ref(false);

  watch(() => toValue(initial), (value) => {
    checklist.value = value;
  });

  async function save(url: string, body: Record<string, unknown>): Promise<boolean> {
    if (saving.value) return false;
    saving.value = true;
    failed.value = false;
    const actorId = page.props.auth?.user?.id;
    try {
      const request = useApiMutation<OnboardingChecklistData>(url, 'PATCH', body, { showSuccessToast: false });
      await request.execute();
      if (page.props.auth?.user?.id !== actorId) return false;
      if (!request.isSuccess.value || !request.data.value) {
        failed.value = true;
        return false;
      }
      checklist.value = request.data.value;
      const user = page.props.auth?.user;
      if (user) {
        user.onboarding_progress = {
          ...user.onboarding_progress,
          [checklist.value.key]: {
            hidden: checklist.value.hidden,
            items: Object.fromEntries(checklist.value.items.map(item => [item.key, { status: item.status }])),
          },
        };
      }
      return true;
    }
    catch {
      failed.value = true;
      return false;
    }
    finally {
      saving.value = false;
    }
  }

  const updateItem = (item: string, status: ChecklistItemStatus) => save(
    route('api.v1.admin.onboarding.items.update', { item }), { status },
  );
  const setHidden = (hidden: boolean) => save(route('api.v1.admin.onboarding.summary.update'), { hidden });
  return { checklist, saving, failed, updateItem, setHidden };
}
