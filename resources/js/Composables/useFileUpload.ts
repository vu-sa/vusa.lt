import { usePage } from '@inertiajs/vue3';

import type { ApiResponse } from '@/Types/api.d';

export interface UploadedFileResult {
  name: string;
  path: string;
  url: string;
  renamed: boolean;
}

export interface FailedFileResult {
  name: string;
  reason: string;
}

export interface UploadBatchResult {
  uploaded: UploadedFileResult[];
  failed: FailedFileResult[];
  path: string;
  message?: string;
}

/**
 * Upload files to the admin file API.
 *
 * Deliberately a plain XHR rather than `router.post`. Inertia's sync visit stream is
 * single-slot and interruptible, so an upload issued as a visit gets cancelled the moment
 * anything else on the page navigates — an AdminForm autosave, its dirty-form guard, a
 * `router.reload`. A cancelled visit fires only `onFinish`, so callers waiting on
 * `onSuccess`/`onError` hung forever over an upload the server had already accepted.
 *
 * `useApiMutation` is not usable here: it forces `Content-Type: application/json` and
 * JSON.stringify's the body.
 *
 * @throws Error carrying the server message when the batch is rejected outright.
 */
export async function uploadFiles(files: File[], path: string): Promise<UploadBatchResult> {
  const formData = new FormData();

  files.forEach((file, index) => {
    formData.append(`files[${index}][file]`, file);
  });
  formData.append('path', path);

  const response = await fetch(route('api.v1.admin.files.store'), {
    method: 'POST',
    headers: {
      // No Content-Type: the browser has to set the multipart boundary itself.
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': (usePage().props.csrf_token as string) ?? '',
    },
    credentials: 'same-origin',
    body: formData,
  });

  const body = await response.json().catch(() => null) as ApiResponse<UploadBatchResult> | null;

  if (!response.ok || !body?.success) {
    throw new Error(
      (body && 'message' in body && body.message) || `Upload failed (${response.status})`,
    );
  }

  return { ...body.data, message: body.message };
}
