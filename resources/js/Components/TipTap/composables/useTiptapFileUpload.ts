/**
 * TipTap File Upload Composable
 *
 * Handles file drag-and-drop and paste uploads with placeholder management.
 */
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Editor } from '@tiptap/vue-3';

import { useToasts } from '@/Composables/useToasts';

interface UploadState {
  fileName: string;
  placeholder: {
    uploadId: string;
    text: string;
  };
}

interface UploadResult {
  url: string;
  name: string;
  originalSize?: number;
  compressedSize?: number;
  compressionRatio?: number;
  message?: string;
}

/**
 * Composable for handling file uploads in TipTap editor
 *
 * @example
 * ```ts
 * const { handleFileDrop, handleFilePaste } = useTiptapFileUpload();
 *
 * // In FileHandler extension config
 * FileHandler.configure({
 *   onDrop: handleFileDrop,
 *   onPaste: handleFilePaste,
 * });
 * ```
 */
export function useTiptapFileUpload() {
  const toasts = useToasts();
  const uploadingFiles = ref(new Map<string, UploadState>());

  /**
   * Handle files dropped into the editor
   */
  async function handleFileDrop(currentEditor: Editor, files: File[], pos?: number) {
    for (const file of files) {
      if (file.type.startsWith('image/')) {
        await processImageUpload(currentEditor, file, pos);
      }
      else {
        await processFileUpload(currentEditor, file, pos);
      }
    }
  }

  /**
   * Handle files pasted into the editor
   */
  async function handleFilePaste(currentEditor: Editor, files: File[]) {
    for (const file of files) {
      if (file.type.startsWith('image/')) {
        await processImageUpload(currentEditor, file);
      }
      else {
        await processFileUpload(currentEditor, file);
      }
    }
  }

  /**
   * Process image upload with compression
   */
  async function processImageUpload(currentEditor: Editor, file: File, pos?: number) {
    const uploadId = generateUploadId();

    try {
      const placeholder = insertUploadPlaceholder(currentEditor, file.name, uploadId, pos);
      uploadingFiles.value.set(uploadId, { fileName: file.name, placeholder });

      const uploadData = {
        image: file,
        name: file.name,
        path: getUploadPath(),
      };

      await new Promise<UploadResult>((resolve, reject) => {
        router.post(route('files.uploadImage'), uploadData, {
          preserveState: true,
          preserveScroll: true,
          onSuccess: (page) => {
            const result = page.props.flash?.data as UploadResult;

            if (result) {
              replacePlaceholderWithImage(currentEditor, uploadId, {
                src: result.url,
                alt: file.name,
                title: `Uploaded: ${file.name}`,
              });
              resolve(result);
            }
            else {
              reject(new Error('Upload succeeded but no data received'));
            }
          },
          onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ') || 'Upload failed';
            reject(new Error(errorMessage));
          },
        });
      });
    }
    catch (error: unknown) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      console.error('Upload failed:', error);
      replacePlaceholderWithError(currentEditor, uploadId, errorMessage);
      toasts.error(`Failed to upload ${file.name}`, {
        description: errorMessage,
      });
    }
    finally {
      uploadingFiles.value.delete(uploadId);
    }
  }

  /**
   * Process non-image file upload
   */
  async function processFileUpload(currentEditor: Editor, file: File, pos?: number) {
    const uploadId = generateUploadId();

    try {
      const placeholder = insertUploadPlaceholder(currentEditor, file.name, uploadId, pos);
      uploadingFiles.value.set(uploadId, { fileName: file.name, placeholder });

      const formData = new FormData();
      formData.append('files[0][file]', file);
      formData.append('path', getUploadPath());

      await new Promise<unknown>((resolve, reject) => {
        router.post(route('files.store'), formData, {
          preserveState: true,
          preserveScroll: true,
          onSuccess: (page) => {
            const fileName = file.name;
            const fileUrl = `/uploads/files/${getUploadPath()}/${fileName}`;
            replacePlaceholderWithFileLink(currentEditor, uploadId, fileName, fileUrl);
            resolve(page);
          },
          onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ') || 'Upload failed';
            reject(new Error(errorMessage));
          },
        });
      });
    }
    catch (error: unknown) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      console.error('File upload failed:', error);
      replacePlaceholderWithError(currentEditor, uploadId, errorMessage);
      toasts.error(`Failed to upload ${file.name}`, {
        description: errorMessage,
      });
    }
    finally {
      uploadingFiles.value.delete(uploadId);
    }
  }

  /**
   * Insert a placeholder text while uploading
   */
  function insertUploadPlaceholder(
    currentEditor: Editor,
    fileName: string,
    uploadId: string,
    pos?: number,
  ) {
    const placeholderText = `🔄 Uploading and compressing ${fileName}...`;

    if (pos !== undefined) {
      currentEditor.chain().focus().insertContentAt(pos, placeholderText).run();
    }
    else {
      currentEditor.chain().focus().insertContent(placeholderText).run();
    }

    return { uploadId, text: placeholderText };
  }

  /**
   * Replace placeholder with uploaded image
   */
  function replacePlaceholderWithImage(
    currentEditor: Editor,
    uploadId: string,
    imageData: { src: string; alt: string; title: string },
  ) {
    const uploadInfo = uploadingFiles.value.get(uploadId);
    if (!uploadInfo) return;

    const { doc } = currentEditor.state;
    let found = false;

    doc.descendants((node, pos) => {
      if (found) return false;

      if (node.isText && node.text?.includes(uploadInfo.placeholder.text)) {
        const from = pos;
        const to = pos + uploadInfo.placeholder.text.length;

        currentEditor
          .chain()
          .focus()
          .deleteRange({ from, to })
          .insertContentAt(from, {
            type: 'image',
            attrs: imageData,
          })
          .run();

        found = true;
      }
    });
  }

  /**
   * Replace placeholder with file download link
   */
  function replacePlaceholderWithFileLink(
    currentEditor: Editor,
    uploadId: string,
    fileName: string,
    fileUrl: string,
  ) {
    const uploadInfo = uploadingFiles.value.get(uploadId);
    if (!uploadInfo) return;

    const { doc } = currentEditor.state;
    let found = false;

    doc.descendants((node, pos) => {
      if (found) return false;

      if (node.isText && node.text?.includes(uploadInfo.placeholder.text)) {
        const fileLink = `<a href="${fileUrl}" target="_blank" class="file-download-link">${fileName}</a>`;

        currentEditor
          .chain()
          .focus()
          .setTextSelection({ from: pos, to: pos + node.text.length })
          .insertContent(fileLink)
          .run();

        found = true;
        return false;
      }
    });
  }

  /**
   * Replace placeholder with error message
   */
  function replacePlaceholderWithError(
    currentEditor: Editor,
    uploadId: string,
    errorMessage: string,
  ) {
    const uploadInfo = uploadingFiles.value.get(uploadId);
    if (!uploadInfo) return;

    const { doc } = currentEditor.state;
    let found = false;

    doc.descendants((node, pos) => {
      if (found) return false;

      if (node.isText && node.text?.includes(uploadInfo.placeholder.text)) {
        const from = pos;
        const to = pos + uploadInfo.placeholder.text.length;

        currentEditor
          .chain()
          .focus()
          .deleteRange({ from, to })
          .insertContent(`❌ Upload failed: ${errorMessage}`)
          .run();

        found = true;
      }
    });
  }

  /**
   * Clear all pending uploads (call on unmount)
   */
  function clearPendingUploads() {
    uploadingFiles.value.clear();
  }

  return {
    uploadingFiles,
    handleFileDrop,
    handleFilePaste,
    clearPendingUploads,
  };
}

// Helper functions

function generateUploadId(): string {
  return `upload-${Date.now()}-${Math.random().toString(36).substring(2, 11)}`;
}

function getUploadPath(): string {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  return `content/${year}/${month}`;
}
