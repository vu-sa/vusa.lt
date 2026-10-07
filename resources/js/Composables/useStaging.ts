import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface StagingState {
  isStaging: boolean;
  filesReadOnly: boolean;
  sharepointReadOnly: boolean;
  mailToRequester?: boolean;
}

export type StagingTopic = 'reset' | 'files' | 'sharepoint' | 'mail';

export interface StagingNote {
  topic: StagingTopic;
  key: string;
}

/**
 * The staging differences, once: the banner's dialog lists them all and StagingNote shows one
 * where it affects an action, so the two never word the same difference differently.
 */
export function useStaging() {
  const page = usePage();
  const staging = computed(() => page.props.staging as StagingState | undefined);
  const isStaging = computed(() => staging.value?.isStaging ?? false);

  const notes = computed<StagingNote[]>(() => {
    if (!staging.value?.isStaging) {
      return [];
    }

    const list: StagingNote[] = [{ topic: 'reset', key: 'staging.topics.reset' }];
    if (staging.value.filesReadOnly) {
      list.push({ topic: 'files', key: 'staging.topics.files' });
    }
    list.push({
      topic: 'sharepoint',
      key: staging.value.sharepointReadOnly ? 'staging.topics.sharepoint_read_only' : 'staging.topics.sharepoint_test_site',
    });
    if (staging.value.mailToRequester) {
      list.push({ topic: 'mail', key: 'staging.topics.mail' });
    }

    return list;
  });

  return { isStaging, notes };
}
