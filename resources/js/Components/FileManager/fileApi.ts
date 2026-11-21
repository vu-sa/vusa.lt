import type { ApiResponse } from '@/Types/api.d';

/**
 * Read a `{success, data, meta}` envelope from the admin file API.
 *
 * @throws Error carrying the server message on any non-success response.
 */
export async function getFilesJson<T>(url: string): Promise<{ data: T | null; meta: Record<string, unknown> }> {
  const response = await fetch(url, {
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  });

  const body = await response.json().catch(() => null) as ApiResponse<T> | null;

  if (!response.ok || !body?.success) {
    throw new Error((body && 'message' in body && body.message) || `Request failed (${response.status})`);
  }

  return { data: body.data, meta: body.meta ?? {} };
}
