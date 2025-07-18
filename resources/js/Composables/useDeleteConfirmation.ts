import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';

export interface DeleteConfirmationOptions {
  /** Custom title for the confirmation dialog */
  title?: string;
  /** Custom message for the confirmation dialog */
  message?: string;
  /** Whether to show confirmation dialog (defaults to true) */
  confirm?: boolean;
  /** Success message after deletion */
  successMessage?: string;
  /** Error message if deletion fails */
  errorMessage?: string;
  /** Whether to preserve scroll position (defaults to true) */
  preserveScroll?: boolean;
  /** Whether to preserve state (defaults to true) */
  preserveState?: boolean;
}

export function useDeleteConfirmation(options: DeleteConfirmationOptions = {}) {
  const isOpen = ref(false);
  const isDeleting = ref(false);
  const currentDeleteAction = ref<(() => void) | null>(null);

  const defaultOptions = {
    title: $t('Are you sure?'),
    message: $t('This action cannot be undone. Are you sure you want to delete this item?'),
    confirm: true,
    successMessage: $t('Item deleted successfully'),
    errorMessage: $t('Failed to delete item'),
    preserveScroll: true,
    preserveState: true,
  };

  const mergedOptions = { ...defaultOptions, ...options };

  /**
   * Show confirmation dialog and execute delete action
   */
  const confirmDelete = (deleteAction: () => void) => {
    if (mergedOptions.confirm) {
      currentDeleteAction.value = deleteAction;
      isOpen.value = true;
    } else {
      deleteAction();
    }
  };

  /**
   * Execute the delete action (called when user confirms)
   */
  const executeDelete = () => {
    if (currentDeleteAction.value) {
      isDeleting.value = true;
      currentDeleteAction.value();
      isOpen.value = false;
      currentDeleteAction.value = null;
    }
  };

  /**
   * Cancel the delete action
   */
  const cancelDelete = () => {
    isOpen.value = false;
    currentDeleteAction.value = null;
  };

  /**
   * Helper function for Inertia.js delete requests
   */
  const deleteWithInertia = (url: string, options: Record<string, any> = {}) => {
    const deleteAction = () => {
      router.delete(url, {
        preserveScroll: mergedOptions.preserveScroll,
        preserveState: mergedOptions.preserveState,
        ...options,
        onSuccess: (response) => {
          isDeleting.value = false;
          if (options.onSuccess) {
            options.onSuccess(response);
          }
        },
        onError: (errors) => {
          isDeleting.value = false;
          if (options.onError) {
            options.onError(errors);
          }
        }
      });
    };

    confirmDelete(deleteAction);
  };

  return {
    // State
    isOpen,
    isDeleting,
    
    // Options
    title: mergedOptions.title,
    message: mergedOptions.message,
    
    // Actions
    confirmDelete,
    executeDelete,
    cancelDelete,
    deleteWithInertia,
  };
}