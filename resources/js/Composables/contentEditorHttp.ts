import type { RecoveryCopy } from './contentEditorStorage';

interface EditorResponseBody { success?: boolean; data?: RecoveryCopy | null; message?: string; errors?: Record<string, string[]> }

class ContentEditorHttpError extends Error {
  constructor(public response: { status: number; data: EditorResponseBody }) {
    super(response.data.message ?? `Request failed (${response.status})`);
  }
}

async function request(options: { url: string; method?: string; data?: unknown }) {
  const token = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='))?.slice('XSRF-TOKEN='.length);
  const response = await fetch(options.url, {
    method: options.method?.toUpperCase() ?? 'GET',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
    },
    body: options.data === undefined ? undefined : JSON.stringify(options.data),
  });
  const data = await response.json();
  if (!response.ok) throw new ContentEditorHttpError({ status: response.status, data });
  return { data };
}

export const contentEditorHttp = {
  request,
  get: (url: string) => request({ url }),
  post: (url: string, data: unknown) => request({ url, method: 'POST', data }),
  put: (url: string, data: unknown) => request({ url, method: 'PUT', data }),
  delete: (url: string, options: { data: unknown }) => request({ url, method: 'DELETE', data: options.data }),
  isError: (error: unknown): error is ContentEditorHttpError => error instanceof ContentEditorHttpError,
};
