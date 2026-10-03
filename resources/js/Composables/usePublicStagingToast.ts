import { onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';

import { publicToastAppearance } from './publicToastAppearance';

export interface PublicStagingState {
  isStaging: boolean;
  filesReadOnly: boolean;
  sharepointReadOnly: boolean;
}

const TOAST_ID = 'public-staging-status';

function stagingDescription(staging: PublicStagingState | null | undefined): string | null {
  if (!staging?.isStaging) {
    return null;
  }

  const warnings = [
    staging.filesReadOnly ? 'File storage is shared with production (read-only)' : null,
    staging.sharepointReadOnly ? 'SharePoint is shared with production (read-only)' : null,
  ].filter((warning): warning is string => warning !== null);

  return warnings.join(' · ') || 'Test environment — data may differ from production';
}

export function usePublicStagingToast(
  stagingGetter: () => PublicStagingState | null | undefined,
  pageKeyGetter: () => string,
): void {
  let currentDescription: string | null = null;
  let currentToastId: string | null = null;
  let toastSequence = 0;

  const show = (staging: PublicStagingState | null | undefined): void => {
    const description = stagingDescription(staging);
    if (description === currentDescription) {
      return;
    }

    if (currentToastId !== null) {
      toast.dismiss(currentToastId);
      currentToastId = null;
    }

    currentDescription = description;

    if (description !== null) {
      currentToastId = `${TOAST_ID}:${++toastSequence}`;
      toast('STAGING ENVIRONMENT', {
        id: currentToastId,
        description,
        duration: Infinity,
        closeButton: true,
        dismissible: true,
        ...publicToastAppearance,
      });
    }
  };

  onMounted(() => show(stagingGetter()));

  watch([stagingGetter, pageKeyGetter], ([staging, pageKey], [, previousPageKey]) => {
    if (pageKey !== previousPageKey && currentToastId !== null) {
      toast.dismiss(currentToastId);
      currentToastId = null;
      currentDescription = null;
    }

    show(staging);
  });
}
