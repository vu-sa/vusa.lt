import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { useToasts } from '@/Composables/useToasts';
import type { ImageData } from '@/Types/media';

export const MAX_COMMENT_IMAGES = 4;

export interface ComposerImage {
  key: string;
  preview: string;
  uploaded: ImageData | null;
  failed: boolean;
}

/** Stages pasted or picked images as pending uploads; posting the comment attaches them. */
export function useComposerImages() {
  const images = ref<ComposerImage[]>([]);
  const toasts = useToasts();

  const uploading = computed(() => images.value.some(image => !image.uploaded && !image.failed));
  const imageIds = computed(() => images.value.flatMap(image => (image.uploaded?.id ? [image.uploaded.id] : [])));
  const canAdd = computed(() => images.value.length < MAX_COMMENT_IMAGES);

  async function upload(image: ComposerImage, file: File): Promise<void> {
    const formData = new FormData();
    formData.append('image', file);
    try {
      const response = await fetch(route('api.v1.admin.pendingUploads.store'), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': (usePage().props.csrf_token as string | undefined) ?? '', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: formData,
      });
      const json = await response.json().catch(() => null) as { data?: ImageData; message?: string } | null;
      if (!response.ok || !json?.data) throw new Error(json?.message ?? $t('forms.image_upload.failed'));
      const current = images.value.find(candidate => candidate.key === image.key);
      if (current) current.uploaded = json.data;
    }
    catch (error) {
      toasts.error((error as Error).message);
      remove(image.key);
    }
  }

  function add(files: Iterable<File>): void {
    for (const file of files) {
      if (!file.type.startsWith('image/')) continue;
      if (!canAdd.value) {
        toasts.error($t('discussions.images.limit', { count: String(MAX_COMMENT_IMAGES) }));
        return;
      }
      const image: ComposerImage = { key: crypto.randomUUID(), preview: URL.createObjectURL(file), uploaded: null, failed: false };
      images.value.push(image);
      void upload(image, file);
    }
  }

  function remove(key: string): void {
    const image = images.value.find(candidate => candidate.key === key);
    if (image) URL.revokeObjectURL(image.preview);
    images.value = images.value.filter(candidate => candidate.key !== key);
  }

  function clear(): void {
    images.value.forEach(image => URL.revokeObjectURL(image.preview));
    images.value = [];
  }

  return { images, uploading, imageIds, canAdd, add, remove, clear };
}
