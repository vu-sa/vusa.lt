import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';

export type ApiMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export interface ApiRequestOptions {
  method?: ApiMethod;
  /** Plain values are sent as JSON; `FormData` goes as multipart. */
  body?: unknown;
  signal?: AbortSignal;
  keepalive?: boolean;
  headers?: Record<string, string>;
  /** Imperative callers mostly handle errors themselves, so this is off by default. */
  showErrorToast?: boolean;
  /** Keep the body as-is instead of unwrapping `{ success, data }`. */
  raw?: boolean;
}

export interface ApiResult<T> {
  ok: boolean;
  /** 0 when the request never got a response. */
  status: number;
  headers: Headers | null;
  /** The unwrapped `data` of an envelope, or the whole body for bare JSON / `raw`. */
  data: T | null;
  /** The parsed body, for callers that read conflict payloads or non-standard keys. */
  body: unknown;
  message: string | null;
  errors: Record<string, string[]> | null;
  meta: Record<string, unknown> | null;
}

export const SESSION_EXPIRED_STATUS = 419;

/** Thrown by callers that turn a failed result into an exception, so their catch can skip a second toast. */
export class SessionExpiredError extends Error {
  constructor() {
    super('Session expired');
    this.name = 'SessionExpiredError';
  }
}

function readCookie(name: string): string | null {
  if (typeof document === 'undefined') {
    return null;
  }

  const entry = document.cookie.split('; ').find(value => value.startsWith(`${name}=`));

  return entry ? decodeURIComponent(entry.slice(name.length + 1)) : null;
}

/** The XSRF cookie stays fresh across responses; the page prop is the fallback for tests and first paint. */
export function csrfHeaders(): Record<string, string> {
  const cookieToken = readCookie('XSRF-TOKEN');
  if (cookieToken) {
    return { 'X-XSRF-TOKEN': cookieToken };
  }

  let propToken: unknown;
  try {
    propToken = (usePage()?.props as { csrf_token?: unknown } | undefined)?.csrf_token;
  }
  catch {
    propToken = undefined;
  }

  return typeof propToken === 'string' && propToken !== '' ? { 'X-CSRF-TOKEN': propToken } : {};
}

export function jsonHeaders(withBody: boolean): Record<string, string> {
  return {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(withBody ? { 'Content-Type': 'application/json' } : {}),
    ...csrfHeaders(),
  };
}

/** A fixed toast id keeps concurrent failures down to one message. */
export function notifySessionExpired(): void {
  toast.error($t('common.session_expired'), {
    id: 'session-expired',
    duration: Infinity,
    action: {
      label: $t('common.reload_page'),
      onClick: () => window.location.reload(),
    },
  });
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

async function readBody(response: Response, signal?: AbortSignal): Promise<unknown> {
  if (response.status === 204) {
    return null;
  }

  try {
    return await response.json();
  }
  catch (error) {
    if (isAbort(error, signal)) {
      throw error;
    }

    return null;
  }
}

/** A failed response never fills `data`, so a bare error body cannot pass for a payload. */
function readData<T>(body: unknown, envelope: Record<string, unknown> | null, ok: boolean, raw: boolean): T | null {
  if (raw) {
    return (body ?? null) as T | null;
  }
  if (!ok) {
    return null;
  }

  return (envelope === null ? body ?? null : envelope.data ?? null) as T | null;
}

function toResult<T>(response: Response, body: unknown, raw: boolean): ApiResult<T> {
  const envelope = isRecord(body) && 'success' in body ? body : null;
  const ok = response.ok && (envelope === null || envelope.success !== false);

  const data = readData<T>(body, envelope, ok, raw);
  const source = isRecord(body) ? body : {};

  return {
    ok,
    status: response.status,
    headers: response.headers ?? null,
    data,
    body,
    message: typeof source.message === 'string' ? source.message : null,
    errors: isRecord(source.errors) ? (source.errors as Record<string, string[]>) : null,
    meta: isRecord(source.meta) ? source.meta : null,
  };
}

function isAbort(error: unknown, signal?: AbortSignal): boolean {
  return Boolean(signal?.aborted) || (error instanceof DOMException && error.name === 'AbortError');
}

function networkFailure<T>(error: unknown, showErrorToast: boolean): ApiResult<T> {
  const message = error instanceof Error ? error.message : 'Network error';
  if (showErrorToast) {
    toast.error(message);
  }

  return { ok: false, status: 0, headers: null, data: null, body: null, message, errors: null, meta: null };
}

function buildInit(options: ApiRequestOptions): RequestInit {
  const { method = 'GET', body, signal, keepalive, headers = {} } = options;
  const isFormData = typeof FormData !== 'undefined' && body instanceof FormData;
  const hasJsonBody = body !== undefined && !isFormData;

  return {
    method,
    credentials: 'same-origin',
    headers: { ...jsonHeaders(hasJsonBody), ...headers },
    body: isFormData ? body : hasJsonBody ? JSON.stringify(body) : undefined,
    signal,
    keepalive,
  };
}

/**
 * Imperative JSON call for any URL — concurrent, `FormData`, `keepalive` and status-aware.
 * Resolves for every HTTP status; rejects only when the request is aborted.
 */
export async function apiRequest<T = unknown>(url: string, options: ApiRequestOptions = {}): Promise<ApiResult<T>> {
  const { showErrorToast = false, raw = false } = options;

  let response: Response;
  try {
    response = await fetch(url, buildInit(options));
  }
  catch (error) {
    if (isAbort(error, options.signal)) {
      throw error;
    }

    return networkFailure<T>(error, showErrorToast);
  }

  const result = toResult<T>(response, await readBody(response, options.signal), raw);

  if (response.status === SESSION_EXPIRED_STATUS) {
    notifySessionExpired();
  }
  else if (!result.ok && showErrorToast) {
    toast.error(result.message ?? $t('common.request_failed'));
  }

  return result;
}
