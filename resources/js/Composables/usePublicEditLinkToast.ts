import { onMounted, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';

export interface PublicEditLink {
  url: string;
  type: string;
  id: number | string;
}

const TOAST_ID_PREFIX = 'public-edit-link:';

/**
 * Shows a persistent Sonner toast with an "edit this page" action whenever the
 * current public page exposes an editable record to the signed-in user. Each
 * record gets its own toast id, so an SPA visit to a different record dismisses
 * the previous toast and shows a fresh one; visiting a page with nothing to edit
 * dismisses it. The action opens the admin edit page in a new tab — /mano is a
 * separate Inertia app, so the public page stays where it is.
 */
export function usePublicEditLinkToast(linkGetter: () => PublicEditLink | null | undefined): void {
  let currentToastId: string | null = null;
  let currentRecordKey: string | null = null;

  const show = (link: PublicEditLink | null | undefined): void => {
    // Same record (e.g. a partial reload) — keep the existing toast, no flash
    const recordKey = link ? `${link.type}:${link.id}` : null;
    if (recordKey === currentRecordKey) {
      return;
    }

    if (currentToastId !== null) {
      toast.dismiss(currentToastId);
    }

    currentRecordKey = recordKey;
    currentToastId = null;

    if (link) {
      currentToastId = TOAST_ID_PREFIX + recordKey;

      toast($t('edit-link.hint'), {
        id: currentToastId,
        // Stays until dismissed — refresh brings it back, keeping it discoverable
        duration: Infinity,
        closeButton: true,
        action: {
          label: $t('edit-link.edit'),
          onClick: () => {
            window.open(link.url, '_blank', 'noopener');
          },
        },
      });
    }
  };

  // The initial check must wait for mount: vue-sonner's <Toaster> only renders
  // toasts created after it subscribes, and it subscribes during its own setup —
  // which runs after this layout's setup. An immediate watcher would fire too
  // early and the toast would never appear on the first page load.
  onMounted(() => show(linkGetter()));

  watch(linkGetter, show);
}
