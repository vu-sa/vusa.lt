import { reactive, ref, watch, type Ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useApiMutation } from '@/Composables/useApi';

export interface ActivityPreviewRecipient {
  id: string;
  name: string;
  period_start: string;
  period_end: string;
  delivery_mode: string;
  skip_reason: string | null;
  incomplete_meetings?: Array<{ id: string; date: string; status: string }>;
}

export interface ActivityPreviewEntry {
  institution: { id: string; name: string };
  recipients: ActivityPreviewRecipient[];
  excluded_recipients?: ActivityPreviewRecipient[];
  skip_reason: string | null;
}

export interface ActivityRecipientPair {
  institution_id: string;
  user_id: string;
}

interface PreviewPayload {
  institution_ids: string[];
  campaign_type: string | null;
  recipients?: ActivityRecipientPair[];
}

const CHUNK = 20;

export const skipLabel = (reason: string) => $t(`activity_requests.skip.${reason}`);

/**
 * Runs the review's own preview for the rows a picker shows, so a row that would be left out
 * is disabled with its reason before it is picked. `pairsFor` asks about picked people instead.
 */
export function useActivityRequestAskability(campaign: Ref<string | null>, pairsFor?: (institutionId: string) => ActivityRecipientPair[]) {
  const payload = ref<PreviewPayload>({ institution_ids: [], campaign_type: null });
  const { data, execute } = useApiMutation<ActivityPreviewEntry[], PreviewPayload>(
    route('api.v1.admin.activityRequests.preview'), 'POST', payload, { showSuccessToast: false, showErrorToast: false },
  );
  const entries = reactive(new Map<string, ActivityPreviewEntry>());
  const requested = new Set<string>();
  let generation = 0;
  let queue = Promise.resolve();

  watch(campaign, () => {
    generation++;
    entries.clear();
    requested.clear();
  });

  const ensure = (ids: string[]) => {
    const missing = ids.filter(id => !requested.has(id));
    if (!campaign.value || missing.length === 0) return;
    missing.forEach(id => requested.add(id));
    const current = generation;
    queue = queue.then(async () => {
      for (let index = 0; index < missing.length; index += CHUNK) {
        const chunk = missing.slice(index, index + CHUNK);
        payload.value = {
          institution_ids: chunk,
          campaign_type: campaign.value,
          ...(pairsFor ? { recipients: chunk.flatMap(pairsFor) } : {}),
        };
        await execute();
        if (current !== generation) return;
        (data.value ?? []).forEach(entry => entries.set(entry.institution.id, entry));
      }
    });
  };

  return { entries, ensure };
}
