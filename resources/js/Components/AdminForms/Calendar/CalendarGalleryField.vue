<template>
  <FormFieldWrapper
    id="images"
    :label="$t('Galerijos nuotraukos')"
    :hint="`${$t('Papildomos nuotraukos, rodomos galerijoje')}. ${$t('Nuotraukos optimizuojamos automatiškai prieš įkėlimą.')}`"
  >
    <ImageUpload
      v-if="!readOnly"
      v-model:files="files"
      :max="20"
      :existing-urls="existing"
      cropper
      :compress="false"
      @remove:existing="removeExisting"
    />
    <div v-else-if="existing.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <img
        v-for="image in existing"
        :key="image.id"
        :src="image.url"
        :alt="image.name"
        class="aspect-video w-full border border-border object-cover"
      >
    </div>
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import ImageUpload from '@/Components/FileUpload/ImageUpload.vue';

import FormFieldWrapper from '../FormFieldWrapper.vue';

interface ExistingImage {
  id: string | number;
  url: string;
  name: string;
}

/** An event's gallery: new files go out with the form, saved ones are removed right away. */
const props = defineProps<{
  calendarId?: string | number | null;
  images?: Record<string, unknown>[] | null;
  readOnly?: boolean;
}>();

const files = defineModel<File[]>('files', { default: () => [] });

const existing = ref<ExistingImage[]>(
  (props.images ?? []).map(image => ({
    id: image.id as string | number,
    url: (image.url ?? image.original_url ?? '') as string,
    name: (image.name ?? 'image.jpg') as string,
  })),
);

function removeExisting(image: { id: string | number }): void {
  if (props.readOnly || !props.calendarId) return;

  router.post(route('calendar.destroyMedia', { calendar: props.calendarId, media: image.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      existing.value = existing.value.filter(candidate => candidate.id !== image.id);
    },
  });
}
</script>
