import { apiRequest } from '@/Composables/apiRequest';

/** @throws Error carrying the server message on any non-success response. */
export async function getFilesJson<T>(url: string): Promise<{ data: T | null; meta: Record<string, unknown> }> {
  const result = await apiRequest<T>(url);
  if (result.status === 0) throw new TypeError(result.message ?? 'Network error');
  if (!result.ok || result.body === null) {
    throw new Error(result.message || `Request failed (${result.status})`);
  }

  return { data: result.data, meta: result.meta ?? {} };
}
