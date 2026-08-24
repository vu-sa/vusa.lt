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

          <!-- Guidance folded away by default; this dialog is opened repeatedly while
               tidying up one page's images, so it has to stay glanceable. -->
          <Collapsible v-model:open="showGuidance">
            <div class="flex items-center justify-between gap-2">
              <CollapsibleTrigger as-child>
                <!-- Explicit type: shadcn's Button sets none, so inside a form this
                     toggle would submit it. -->
                <Button type="button" variant="ghost" size="sm"
                  class="h-auto gap-2 p-0 text-xs font-normal has-[>svg]:p-0 text-muted-foreground hover:bg-transparent hover:text-foreground">
                  <Info class="size-4" />
                  {{ $t('rich-content.examples') }}
                  <ChevronDown class="size-3 transition-transform duration-200"
                    :class="{ 'rotate-180': showGuidance }" />
                </Button>
              </CollapsibleTrigger>
              <span class="text-xs tabular-nums text-muted-foreground">
                {{ formData.alt?.length || 0 }}/125
              </span>
            </div>
            <CollapsibleContent class="pt-2 text-xs leading-relaxed text-muted-foreground">
              <p>{{ $t('rich-content.image_alt_help') }}</p>
              <ul class="mt-1 list-inside list-disc space-y-1">
                <li>{{ $t('rich-content.example_photo') }}</li>
                <li>{{ $t('rich-content.example_chart') }}</li>
                <li>{{ $t('rich-content.example_decorative') }}</li>
              </ul>
            </CollapsibleContent>
          </Collapsible>

          <div class="flex items-center gap-2">
            <Checkbox id="alt-decorative" v-model="isDecorative" />
            <Label for="alt-decorative" class="text-xs font-normal text-muted-foreground">
              {{ $t('rich-content.image_is_decorative') }}
            </Label>
          </div>
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
import { ChevronDown, Info } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
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
const showGuidance = ref(false);

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
