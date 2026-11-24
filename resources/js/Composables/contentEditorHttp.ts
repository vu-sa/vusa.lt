import { apiRequest } from '@/Composables/apiRequest';
import type { ApiMethod } from '@/Composables/apiRequest';
import type { ContentEditorData } from '@/Composables/useContentEditor';

import type { RecoveryCopy } from './contentEditorStorage';

interface EditorResponseBody<T> {
  success?: boolean;
  data: T;
  message?: string;
  errors?: Record<string, string[]>;
  meta?: { redirect_url?: string };
}

type EditorReadData = RecoveryCopy & ContentEditorData & { id: number; title: string };

interface PairingPreview {
  token: string;
  confirmation_required: boolean;
  records: { id: number; title: string; lang: string; other_lang_id?: number }[];
}

class ContentEditorHttpError extends Error {
  constructor(public response: { status: number; data: Partial<EditorResponseBody<RecoveryCopy | null>> }) {
    super(response.data.message ?? `Request failed (${response.status})`);
  }
}

async function request<T = unknown>(options: { url: string; method?: string; data?: unknown }) {
  const result = await apiRequest<EditorResponseBody<T>>(options.url, {
    method: (options.method?.toUpperCase() ?? 'GET') as ApiMethod,
    body: options.data,
    raw: true,
  });
  if (result.status === 0) throw new TypeError(result.message ?? 'Network error');
  if (result.data === null) throw new SyntaxError('Unexpected end of JSON input');
  if (result.status < 200 || result.status >= 300) {
    throw new ContentEditorHttpError({ status: result.status, data: result.body as Partial<EditorResponseBody<RecoveryCopy | null>> });
  }

  return { data: result.data };
}

export const contentEditorHttp = {
  request,
  get: (url: string) => request<EditorReadData>({ url }),
  post: (url: string, data: unknown) => request<PairingPreview>({ url, method: 'POST', data }),
  put: (url: string, data: unknown) => request<RecoveryCopy & { revision: number }>({ url, method: 'PUT', data }),
  delete: (url: string, options: { data: unknown }) => request({ url, method: 'DELETE', data: options.data }),
  isError: (error: unknown): error is ContentEditorHttpError => error instanceof ContentEditorHttpError,
};
