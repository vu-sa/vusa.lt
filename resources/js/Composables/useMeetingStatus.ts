import { trans as $t } from 'laravel-vue-i18n';

/**
 * Shared meeting status utilities for consistent display across components
 */
export function useMeetingStatus() {
  /**
   * Get badge variant for completion status
   */
  const getCompletionVariant = (status: string) => {
    return {
      'complete': 'success',
      'incomplete': 'warning',
      'no_items': 'secondary',
    }[status] || 'secondary';
  };

  /**
   * Get localized label for completion status
   * @param status - The completion status string
   * @param verbose - If true, uses longer descriptive labels (e.g., for detail pages)
   */
  const getCompletionLabel = (status: string, verbose = false) => {
    if (verbose) {
      return {
        'complete': $t('Užpildyta'),
        'incomplete': $t('Neužpildyta iki galo'),
        'no_items': $t('Nėra darbotvarkės'),
      }[status] || status;
    }
    return {
      'complete': $t('Užpildyta'),
      'incomplete': $t('Neužpildyta'),
      'no_items': $t('Nėra darbotvarkės'),
    }[status] || status;
  };

  /**
   * Get dot/indicator color classes for completion status
   */
  const getStatusDotColor = (status: string) => {
    return {
      'complete': 'bg-green-500 dark:bg-green-400',
      'incomplete': 'bg-amber-500 dark:bg-amber-400',
      'no_items': 'bg-zinc-400 dark:bg-zinc-500',
    }[status] || 'bg-zinc-400';
  };

  return {
    getCompletionVariant,
    getCompletionLabel,
    getStatusDotColor,
  };
}
