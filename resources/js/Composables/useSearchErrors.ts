import { ref, computed, type Ref } from 'vue';

export interface SearchError {
  type: 'network' | 'server' | 'client' | 'timeout' | 'abort';
  message: string;
  userMessage: string; // User-friendly message
  code?: string | number;
  retryable: boolean;
  timestamp: Date;
}

export const useSearchErrors = () => {
  const searchError = ref<SearchError | null>(null);
  const isOnline = ref(navigator.onLine);
  const retryCount = ref(0);
  const maxRetries = 3;

  // Computed properties
  const hasError = computed(() => !!searchError.value);
  const canRetry = computed(() =>
    searchError.value?.retryable && retryCount.value < maxRetries,
  );

  // Network status monitoring
  const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
  };

  // Error creation utility
  const createSearchError = (
    type: SearchError['type'],
    message: string,
    userMessage: string,
    code?: string | number,
    retryable = true,
  ): SearchError => ({
    type,
    message,
    userMessage,
    code,
    retryable,
    timestamp: new Date(),
  });

  // Error handling
  const handleSearchError = (error: unknown, context: string) => {
    console.error(`Search error in ${context}:`, error);

    if (error instanceof Error) {
      if (error.name === 'AbortError') {
        searchError.value = createSearchError(
          'abort',
          'Search was cancelled',
          'Search was cancelled',
          'ABORTED',
          false,
        );
        return;
      }

      if (error.message.includes('fetch') || !isOnline.value) {
        searchError.value = createSearchError(
          'network',
          'Network error - please check your connection',
          'Check your internet connection and try again',
          'NETWORK_ERROR',
        );
        return;
      }

      if (error.message.includes('timeout')) {
        searchError.value = createSearchError(
          'timeout',
          'Search request timed out',
          'Search is taking too long. Please try again.',
          'TIMEOUT',
        );
        return;
      }

      if (error.message.includes('404') || error.message.includes('400')) {
        searchError.value = createSearchError(
          'client',
          'Invalid search request',
          'There was a problem with your search. Please try different terms.',
          'CLIENT_ERROR',
          false,
        );
        return;
      }

      if (error.message.includes('500') || error.message.includes('503')) {
        searchError.value = createSearchError(
          'server',
          'Search service temporarily unavailable',
          'Search is temporarily unavailable. Please try again in a few minutes.',
          'SERVER_ERROR',
        );
        return;
      }
    }

    // Generic error fallback
    searchError.value = createSearchError(
      'client',
      'An unexpected error occurred during search',
      'Something went wrong. Please try again or refresh the page.',
      'UNKNOWN',
    );
  };

  // Error management
  const clearError = () => {
    searchError.value = null;
    retryCount.value = 0;
  };

  const incrementRetryCount = () => {
    retryCount.value++;
  };

  const resetRetryCount = () => {
    retryCount.value = 0;
  };

  // Setup network monitoring
  if (typeof window !== 'undefined') {
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
  }

  // Cleanup function
  const cleanup = () => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('online', updateOnlineStatus);
      window.removeEventListener('offline', updateOnlineStatus);
    }
  };

  return {
    // State
    searchError: searchError as Ref<SearchError | null>,
    isOnline,
    retryCount,
    maxRetries,

    // Computed
    hasError,
    canRetry,

    // Methods
    handleSearchError,
    clearError,
    incrementRetryCount,
    resetRetryCount,
    cleanup,
  };
};

export type SearchErrorController = ReturnType<typeof useSearchErrors>;
