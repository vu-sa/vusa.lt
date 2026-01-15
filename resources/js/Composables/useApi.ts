/**
 * API Composable
 *
 * A thin wrapper around VueUse's useFetch that provides:
 * - Consistent typing with ApiResponse<T>
 * - Automatic auth header handling
 * - Error parsing and toast notifications
 * - Route helper integration
 *
 * Usage:
 * ```ts
 * import { useApi } from '@/Composables/useApi';
 *
 * // Simple GET request
 * const { data, error, isFetching } = useApi<TaskIndicatorData[]>(
 *   route('api.admin.tasks.indicator')
 * );
 *
 * // With options
 * const { data } = useApi<FileBrowserData>(
 *   route('api.admin.files.index', { path: 'public/files' }),
 *   { immediate: false }
 * );
 *
 * // Execute manually
 * await execute();
 * ```
 *
 * When to use this vs Inertia:
 * - USE useApi: Dynamic data refresh, polling, on-demand loading, cross-component data
 * - USE Inertia lazy/defer: Page-bound data, browser history state, initial page render
 */

import { computed, type Ref, type ComputedRef, unref } from 'vue';
import { useFetch, type UseFetchReturn, type UseFetchOptions } from '@vueuse/core';
import { useToasts } from '@/Composables/useToasts';
import type {
  ApiResponse,
  ApiSuccessResponse,
  ApiErrorResponse,
  isApiSuccess,
  isApiError,
} from '@/Types/api.d';
import { usePage } from '@inertiajs/vue3';

/**
 * Options for useApi composable
 */
export interface UseApiOptions<T = unknown> extends Omit<UseFetchOptions, 'beforeFetch' | 'afterFetch' | 'onFetchError'> {
  /**
   * Show toast notification on error (default: true)
   */
  showErrorToast?: boolean;

  /**
   * Show toast notification on success (default: false)
   */
  showSuccessToast?: boolean;

  /**
   * Custom success message (uses response message if not provided)
   */
  successMessage?: string;

  /**
   * Custom error message (uses response message if not provided)
   */
  errorMessage?: string;

  /**
   * Transform data after successful response
   */
  transform?: (data: T) => T;
}

/**
 * Return type for useApi
 */
export interface UseApiReturn<T> {
  /** Unwrapped data from successful response */
  data: ComputedRef<T | null>;

  /** Raw API response (useful for checking success/error) */
  response: Ref<ApiResponse<T> | null>;

  /** Error message from failed response */
  error: ComputedRef<string | null>;

  /** Whether request is in progress */
  isFetching: Ref<boolean>;

  /** Whether request has completed at least once */
  isFinished: Ref<boolean>;

  /** Whether the response was successful */
  isSuccess: ComputedRef<boolean>;

  /** Execute or re-execute the request */
  execute: () => Promise<void>;

  /** Abort current request */
  abort: () => void;
}

/**
 * Make type-safe API calls with consistent error handling
 *
 * @param url - API endpoint URL (use route() helper)
 * @param options - Fetch options
 */
export function useApi<T = unknown>(
  url: string | Ref<string>,
  options: UseApiOptions<T> = {}
): UseApiReturn<T> {
  const {
    showErrorToast = true,
    showSuccessToast = false,
    successMessage,
    errorMessage,
    transform,
    ...fetchOptions
  } = options;

  const { toast } = useToasts();

  // Use VueUse's useFetch
  const fetchReturn = useFetch<ApiResponse<T>>(url, {
    ...fetchOptions,
    beforeFetch({ options }) {
      // Ensure we accept JSON and send credentials for session auth
      options.headers = {
        ...options.headers,
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };
      options.credentials = 'same-origin';
      return { options };
    },
    afterFetch(ctx) {
      // Parse JSON response
      if (typeof ctx.data === 'string') {
        try {
          ctx.data = JSON.parse(ctx.data);
        } catch {
          // Keep as-is if not JSON
        }
      }
      return ctx;
    },
    onFetchError(ctx) {
      // Try to parse error response
      if (typeof ctx.data === 'string') {
        try {
          ctx.data = JSON.parse(ctx.data);
        } catch {
          ctx.data = {
            success: false,
            message: ctx.error?.message || 'Network error',
            code: 'SERVER_ERROR',
          };
        }
      }
      return ctx;
    },
  }).json<ApiResponse<T>>();

  // Computed: unwrap data from successful response
  const data = computed<T | null>(() => {
    const rawData = unref(fetchReturn.data);
    if (rawData && rawData.success === true && 'data' in rawData) {
      const responseData = rawData.data as T;
      return transform ? transform(responseData) : responseData;
    }
    return null;
  });

  // Computed: extract error message
  const error = computed<string | null>(() => {
    const rawData = unref(fetchReturn.data);
    if (rawData && rawData.success === false) {
      return rawData.message || 'An error occurred';
    }
    if (fetchReturn.error.value) {
      return fetchReturn.error.value.message || 'Network error';
    }
    return null;
  });

  // Computed: check if successful
  const isSuccess = computed<boolean>(() => {
    const rawData = unref(fetchReturn.data);
    return rawData?.success === true;
  });

  // Wrap execute to handle toasts
  const execute = async (): Promise<void> => {
    await fetchReturn.execute();

    const rawData = unref(fetchReturn.data);

    if (rawData?.success === true && showSuccessToast) {
      const message = successMessage || (rawData as ApiSuccessResponse<T>).message || 'Success';
      toast.success(message);
    }

    if (rawData?.success === false && showErrorToast) {
      const message = errorMessage || (rawData as ApiErrorResponse).message || 'An error occurred';
      toast.error(message);
    }
  };

  return {
    data,
    response: fetchReturn.data as Ref<ApiResponse<T> | null>,
    error,
    isFetching: fetchReturn.isFetching,
    isFinished: fetchReturn.isFinished,
    isSuccess,
    execute,
    abort: fetchReturn.abort,
  };
}

/**
 * Make a POST/PUT/PATCH/DELETE API call
 *
 * @param url - API endpoint URL
 * @param method - HTTP method
 * @param body - Request body
 * @param options - Fetch options
 */
export function useApiMutation<T = unknown, B = unknown>(
  url: string | Ref<string>,
  method: 'POST' | 'PUT' | 'PATCH' | 'DELETE' = 'POST',
  body?: B | Ref<B>,
  options: UseApiOptions<T> = {}
): UseApiReturn<T> {
  const {
    showErrorToast = true,
    showSuccessToast = true,
    successMessage,
    errorMessage,
    transform,
    ...fetchOptions
  } = options;

  const { toast } = useToasts();

  const fetchReturn = useFetch<ApiResponse<T>>(url, {
    ...fetchOptions,
    immediate: false, // Mutations should be explicit
    beforeFetch({ options }) {
      // Get CSRF token from meta tag (set by Laravel in the HTML head)
      const csrfToken = usePage().props.csrf_token || '';
      
      options.method = method;
      options.headers = {
        ...options.headers,
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      };
      options.credentials = 'same-origin';
      if (body) {
        options.body = JSON.stringify(unref(body));
      }
      return { options };
    },
    afterFetch(ctx) {
      if (typeof ctx.data === 'string') {
        try {
          ctx.data = JSON.parse(ctx.data);
        } catch {
          // Keep as-is
        }
      }
      return ctx;
    },
    onFetchError(ctx) {
      if (typeof ctx.data === 'string') {
        try {
          ctx.data = JSON.parse(ctx.data);
        } catch {
          ctx.data = {
            success: false,
            message: ctx.error?.message || 'Network error',
            code: 'SERVER_ERROR',
          };
        }
      }
      return ctx;
    },
  }).json<ApiResponse<T>>();

  const data = computed<T | null>(() => {
    const rawData = unref(fetchReturn.data);
    if (rawData && rawData.success === true && 'data' in rawData) {
      const responseData = rawData.data as T;
      return transform ? transform(responseData) : responseData;
    }
    return null;
  });

  const error = computed<string | null>(() => {
    const rawData = unref(fetchReturn.data);
    if (rawData && rawData.success === false) {
      return rawData.message || 'An error occurred';
    }
    if (fetchReturn.error.value) {
      return fetchReturn.error.value.message || 'Network error';
    }
    return null;
  });

  const isSuccess = computed<boolean>(() => {
    const rawData = unref(fetchReturn.data);
    return rawData?.success === true;
  });

  const execute = async (): Promise<void> => {
    await fetchReturn.execute();

    const rawData = unref(fetchReturn.data);

    if (rawData?.success === true && showSuccessToast) {
      const message = successMessage || (rawData as ApiSuccessResponse<T>).message || 'Success';
      toast.success(message);
    }

    if (rawData?.success === false && showErrorToast) {
      const message = errorMessage || (rawData as ApiErrorResponse).message || 'An error occurred';
      toast.error(message);
    }
  };

  return {
    data,
    response: fetchReturn.data as Ref<ApiResponse<T> | null>,
    error,
    isFetching: fetchReturn.isFetching,
    isFinished: fetchReturn.isFinished,
    isSuccess,
    execute,
    abort: fetchReturn.abort,
  };
}
