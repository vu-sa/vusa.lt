import { trans as $t } from 'laravel-vue-i18n';

import { useToasts } from '@/Composables/useToasts';

/** The user closed the share sheet. Not a failure — nothing else should happen. */
const CANCELLED = ['AbortError', 'NotAllowedError'];

interface ShareData {
  title: string;
  text?: string;
  /** Defaults to the current page. */
  url?: string;
}

/**
 * Share the current page: the native share sheet where there is one, a copied link where
 * there isn't.
 *
 * Extracted from `Components/Calendar/EventActions.vue`, which had three defects this fixes:
 *
 * 1. **A dismissed share sheet copied the link anyway.** `navigator.share()` rejects with
 *    `AbortError` when the user closes it, and the old `catch` fell straight through to the
 *    clipboard — so cancelling silently did the thing you cancelled.
 * 2. **A successful copy said nothing.** Clicking "share" and getting no feedback at all reads
 *    as a broken button.
 * 3. **`navigator.clipboard` is undefined on an insecure origin**, so the fallback's own fallback
 *    threw a TypeError into the console rather than telling the reader anything.
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
