import { computed, toValue, type ComputedRef, type MaybeRefOrGetter } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { useIsAdminContext } from '@/Composables/useIsAdminContext';

export interface LinkableUser {
  id?: string | number | null;
}

export interface UseUserLinkOptions {
  disabled?: MaybeRefOrGetter<boolean>;
}

/** Whether the viewer can open admin user profiles in the current surface. */
export function useCanLinkUsers(): ComputedRef<boolean> {
  const page = usePage();
  const isAdminContext = useIsAdminContext();

  return computed(() => isAdminContext.value && Boolean((page.props.auth?.can as Record<string, unknown> | undefined)?.['users.read.padalinys']));
}

/** Single linking rule for user profiles: admin context, `users.read.padalinys` permission, and a user id. */
export function useUserLink(
  user?: MaybeRefOrGetter<LinkableUser | null | undefined>,
  options: UseUserLinkOptions = {},
) {
  const canLinkUsers = useCanLinkUsers();

  const hrefFor = (target: LinkableUser | null | undefined): string | undefined => {
    if (toValue(options.disabled) || !canLinkUsers.value || !target?.id) {
      return undefined;
    }

    return route('users.show', target.id);
  };

  const href = computed(() => hrefFor(toValue(user)));
  const canLink = computed(() => href.value !== undefined);

  return { canLink, canLinkUsers, href, hrefFor };
}
