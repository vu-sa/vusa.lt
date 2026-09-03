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
): void {
  let currentDescription: string | null = null;

  const show = (staging: PublicStagingState | null | undefined): void => {
    const description = stagingDescription(staging);
    if (description === currentDescription) {
      return;
    }

    if (currentDescription !== null) {
      toast.dismiss(TOAST_ID);
    }

    currentDescription = description;

    if (description !== null) {
      toast('STAGING ENVIRONMENT', {
        id: TOAST_ID,
        description,
        duration: Infinity,
        closeButton: false,
        dismissible: false,
        ...publicToastAppearance,
      });
    }
  };

  onMounted(() => show(stagingGetter()));

  watch(stagingGetter, show);
}
