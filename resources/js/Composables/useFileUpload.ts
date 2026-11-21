import { apiRequest, SessionExpiredError } from '@/Composables/apiRequest';

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

/** Uploads run independently of Inertia visits, which can cancel one another. */
export async function uploadFiles(files: File[], path: string): Promise<UploadBatchResult> {
  const formData = new FormData();

  files.forEach((file, index) => {
    formData.append(`files[${index}][file]`, file);
  });
  formData.append('path', path);

  const result = await apiRequest<UploadBatchResult>(route('api.v1.admin.files.store'), {
    method: 'POST',
    body: formData,
  });

  if (result.status === 0) throw new TypeError(result.message ?? 'Network error');
  if (result.status === 419) throw new SessionExpiredError();
  if (!result.ok || !result.data) {
    throw new Error(result.message || `Upload failed (${result.status})`);
  }

  return { ...result.data, message: result.message ?? undefined };
}
