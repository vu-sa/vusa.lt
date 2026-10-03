import { trans as $t } from 'laravel-vue-i18n';

import { useToasts } from '@/Composables/useToasts';

/** The user closed the share sheet. Not a failure — nothing else should happen. */
const CANCELLED = ['AbortError', 'NotAllowedError'];

export interface ShareData {
  title: string;
  text?: string;
  /** Defaults to the current page. */
  url?: string;
}

/**
 * Share the current page: the native share sheet where there is one, a copied link where
 * there isn't.
 */
export function useShareLink() {
  const toasts = useToasts();

  const share = async (data: ShareData): Promise<void> => {
    const url = data.url ?? window.location.href;
    const payload = { title: data.title, text: data.text ?? data.title, url };

    if (typeof navigator !== 'undefined' && typeof navigator.share === 'function') {
      try {
        await navigator.share(payload);
        return;
      }
      catch (error) {
        if (error instanceof Error && CANCELLED.includes(error.name)) return;
        // Anything else (an unsupported payload, a platform refusal) is worth falling back for.
      }
    }

    if (typeof navigator === 'undefined' || !navigator.clipboard?.writeText) {
      toasts.error($t('common.link_copy_failed'));
      return;
    }

    try {
      await navigator.clipboard.writeText(url);
      toasts.success($t('common.link_copied'));
    }
    catch {
      toasts.error($t('common.link_copy_failed'));
    }
  };

  return { share };
}
