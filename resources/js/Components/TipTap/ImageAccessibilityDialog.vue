<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('rich-content.image_accessibility_title') }}</DialogTitle>
        <DialogDescription>
          {{ $t('rich-content.image_accessibility_description') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- Preview of the image -->
        <div v-if="imageData.src" class="flex justify-center">
          <img
            :src="imageData.src"
            :alt="formData.alt || $t('rich-content.image_preview')"
            class="max-w-full max-h-32 rounded-md object-contain"
          >
        </div>

        <!-- Alt text field -->
        <div class="space-y-2">
          <Label for="alt-text">
            {{ $t('rich-content.image_alt_text') }} *
          </Label>
          <Input
            id="alt-text"
            v-model="formData.alt"
            :disabled="isDecorative"
            :placeholder="$t('rich-content.image_alt_placeholder')"
            maxlength="125"
          />
          <p class="text-xs text-muted-foreground">
            {{ $t('rich-content.image_alt_help') }}
            {{ formData.alt?.length || 0 }}/125 {{ $t('rich-content.characters') }}.
          </p>
          <label class="flex items-center gap-2 text-xs text-muted-foreground">
            <Checkbox v-model="isDecorative" />
            {{ $t('rich-content.image_is_decorative') }}
          </label>
        </div>

        <!-- Title field -->
        <div class="space-y-2">
          <Label for="title-text">
            {{ $t('rich-content.image_title') }}
          </Label>
          <Input
            id="title-text"
            v-model="formData.title"
            :placeholder="$t('rich-content.image_title_placeholder')"
          />
          <p class="text-xs text-muted-foreground">
            {{ $t('rich-content.image_title_help') }}
          </p>
        </div>

        <!-- Quick examples -->
        <div class="text-xs text-muted-foreground">
          <strong>{{ $t('rich-content.examples') }}:</strong>
          <ul class="mt-1 space-y-1 list-disc list-inside">
            <li>{{ $t('rich-content.example_photo') }}</li>
            <li>{{ $t('rich-content.example_chart') }}</li>
            <li>{{ $t('rich-content.example_decorative') }}</li>
          </ul>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="handleCancel">
          {{ $t('rich-content.cancel') }}
        </Button>
        <Button :disabled="!canSubmit" @click="handleSubmit">
          {{ $t('rich-content.update_image') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

interface ImageData {
  src: string;
  alt: string;
  title: string;
}

const props = defineProps<{
  open: boolean;
  imageData: ImageData;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  'submit': [data: { alt: string; title: string }];
}>();

const formData = ref({
  alt: '',
  title: '',
});
const isDecorative = ref(false);

// Reset form when dialog opens
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    formData.value = {
      alt: props.imageData.alt || '',
      title: props.imageData.title || '',
    };
    // An empty alt on an already-saved image most often means someone deliberately
    // marked it decorative before this checkbox existed — carry that intent forward
    // instead of forcing them to re-declare it.
    isDecorative.value = !props.imageData.alt;
  }
});

// Aligned with ImageSelector.vue (initial insert): alt is required, with an explicit
// "decorative" escape hatch instead of silently allowing an empty value through.
const canSubmit = computed(() => isDecorative.value || formData.value.alt.trim().length > 0);

function handleSubmit() {
  emit('submit', {
    alt: isDecorative.value ? '' : formData.value.alt.trim(),
    title: formData.value.title.trim(),
  });
  emit('update:open', false);
}

function handleCancel() {
  emit('update:open', false);
}
</script>
