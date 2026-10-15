import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { useApiMutation } from '@/Composables/useApi';
import { useToasts } from '@/Composables/useToasts';

interface SubscriptionState {
  is_followed: boolean;
  is_duty_based: boolean;
}

interface ToggleResponseData {
  is_followed?: boolean;
  message?: string;
}

/**
 * Follow institutions through the admin API. Pass `reloadProps` when the page's Inertia
 * props carry the state; pages holding it locally (the institution collection) pass none.
 */
function reload(only: string[]): void {
  if (only.length > 0) {
    router.reload({ only });
  }
}

export function useInstitutionSubscription() {
  const toasts = useToasts();

  const followLoading = ref<Record<string, boolean>>({});

  /**
   * Toggle follow state for an institution.
   * Duty-based institutions cannot be unfollowed.
   */
  async function toggleFollow(
    institutionId: string,
    currentState?: SubscriptionState,
    reloadProps: string[] = [],
  ): Promise<boolean> {
    if (currentState?.is_duty_based && currentState?.is_followed) {
      toasts.info($t('visak.cannot_unfollow_duty_institution'));
      return currentState.is_followed;
    }

    followLoading.value[institutionId] = true;

    const isCurrentlyFollowed = currentState?.is_followed ?? false;
    const method = isCurrentlyFollowed ? 'DELETE' : 'POST';
    const routeName = isCurrentlyFollowed
      ? 'api.v1.admin.institutions.unfollow'
      : 'api.v1.admin.institutions.follow';

    const { execute, isSuccess } = useApiMutation<ToggleResponseData>(
      route(routeName, { institution: institutionId }),
      method,
      undefined,
      {
        showSuccessToast: true,
        successMessage: isCurrentlyFollowed
          ? $t('visak.institution_unfollowed')
          : $t('visak.institution_followed'),
      },
    );

    try {
      await execute();

      if (isSuccess.value) {
        reload(reloadProps);
        return !isCurrentlyFollowed;
      }

      return isCurrentlyFollowed;
    }
    catch (error) {
      console.error('Failed to toggle follow:', error);
      return isCurrentlyFollowed;
    }
    finally {
      followLoading.value[institutionId] = false;
    }
  }

  const bulkLoading = ref(false);

  /** Follow or unfollow many at once; resolves to whether the server accepted it. */
  async function setFollowedMany(institutionIds: string[], followed: boolean): Promise<boolean> {
    if (institutionIds.length === 0) {
      return true;
    }

    const { execute, isSuccess } = useApiMutation<{ institution_ids: string[]; is_followed: boolean }>(
      route(followed ? 'api.v1.admin.institutions.follows.store' : 'api.v1.admin.institutions.follows.destroy'),
      followed ? 'POST' : 'DELETE',
      { institution_ids: institutionIds },
      {
        showSuccessToast: true,
        successMessage: followed
          ? $t('Pradėjai sekti institucijų: :count', { count: String(institutionIds.length) })
          : $t('Nebeseki institucijų: :count', { count: String(institutionIds.length) }),
      },
    );

    bulkLoading.value = true;

    try {
      await execute();

      return isSuccess.value;
    }
    finally {
      bulkLoading.value = false;
    }
  }

  function isFollowLoading(institutionId: string): boolean {
    return followLoading.value[institutionId] ?? false;
  }

  return {
    setFollowedMany,
    bulkLoading,
    toggleFollow,
    isFollowLoading,
    followLoading,
  };
}
