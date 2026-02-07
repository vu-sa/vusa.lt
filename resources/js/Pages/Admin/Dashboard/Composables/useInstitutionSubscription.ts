import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useApiMutation } from '@/Composables/useApi';
import { useToasts } from '@/Composables/useToasts';
import { trans as $t } from 'laravel-vue-i18n';

interface SubscriptionState {
  is_followed: boolean;
  is_muted: boolean;
  is_duty_based: boolean;
}

/**
 * Inner data payload from follow/mute API endpoints.
 * Note: This represents the `data` property from the API response,
 * NOT the full ApiResponse wrapper (which has success/data/message).
 */
interface ToggleResponseData {
  is_followed?: boolean;
  is_muted?: boolean;
  message?: string;
}

/**
 * Composable for managing institution follow/mute subscriptions.
 * 
 * Provides methods to toggle follow and mute states for institutions,
 * with automatic Inertia reload to refresh page data after changes.
 */
export function useInstitutionSubscription() {
  const toasts = useToasts();
  
  // Track loading states per institution ID
  const followLoading = ref<Record<string, boolean>>({});
  const muteLoading = ref<Record<string, boolean>>({});

  /**
   * Toggle follow state for an institution.
   * Duty-based institutions cannot be unfollowed.
   */
  async function toggleFollow(
    institutionId: string,
    currentState?: SubscriptionState,
    reloadProps: string[] = ['userInstitutions', 'relatedInstitutions']
  ): Promise<boolean> {
    // Prevent unfollowing duty-based institutions
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
      }
    );

    try {
      await execute();

      if (isSuccess.value) {
        // Reload Inertia props to refresh institution data
        router.reload({ only: reloadProps });
        return !isCurrentlyFollowed;
      }

      return isCurrentlyFollowed;
    } catch (error) {
      console.error('Failed to toggle follow:', error);
      return isCurrentlyFollowed;
    } finally {
      followLoading.value[institutionId] = false;
    }
  }

  /**
   * Toggle mute state for an institution.
   * Duty-based institutions cannot be muted (they always receive notifications).
   */
  async function toggleMute(
    institutionId: string,
    currentState?: SubscriptionState,
    reloadProps: string[] = ['userInstitutions', 'relatedInstitutions']
  ): Promise<boolean> {
    // Prevent muting duty-based institutions
    if (currentState?.is_duty_based && !currentState?.is_muted) {
      toasts.info($t('visak.cannot_mute_duty_institution'));
      return currentState?.is_muted ?? false;
    }

    muteLoading.value[institutionId] = true;

    const isCurrentlyMuted = currentState?.is_muted ?? false;
    const method = isCurrentlyMuted ? 'DELETE' : 'POST';
    const routeName = isCurrentlyMuted 
      ? 'api.v1.admin.institutions.unmute' 
      : 'api.v1.admin.institutions.mute';

    const { execute, isSuccess } = useApiMutation<ToggleResponseData>(
      route(routeName, { institution: institutionId }),
      method,
      undefined,
      {
        showSuccessToast: true,
        successMessage: isCurrentlyMuted 
          ? $t('visak.notifications_unmuted') 
          : $t('visak.notifications_muted'),
      }
    );

    try {
      await execute();

      if (isSuccess.value) {
        // Reload Inertia props to refresh institution data
        router.reload({ only: reloadProps });
        return !isCurrentlyMuted;
      }

      return isCurrentlyMuted;
    } catch (error) {
      console.error('Failed to toggle mute:', error);
      return isCurrentlyMuted;
    } finally {
      muteLoading.value[institutionId] = false;
    }
  }

  /**
   * Check if follow action is loading for a specific institution
   */
  function isFollowLoading(institutionId: string): boolean {
    return followLoading.value[institutionId] ?? false;
  }

  /**
   * Check if mute action is loading for a specific institution
   */
  function isMuteLoading(institutionId: string): boolean {
    return muteLoading.value[institutionId] ?? false;
  }

  return {
    toggleFollow,
    toggleMute,
    isFollowLoading,
    isMuteLoading,
    followLoading,
    muteLoading,
  };
}
