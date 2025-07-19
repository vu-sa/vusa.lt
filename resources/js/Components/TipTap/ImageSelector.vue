<template>
  <Dialog :open="showModal" @update:open="showModal = $event">
    <DialogContent class="sm:max-w-4xl max-h-[90vh] !p-8">
      <DialogHeader>
        <DialogTitle>{{ $t('accessibility.select_image') || 'Pasirinkti paveikslėlį' }}</DialogTitle>
        <DialogDescription>
          {{ $t('accessibility.image_selection_help_text') || 'Pasirinkite paveikslėlį iš failų tvarkyklės ir nustatykite jo prieinamumo savybes.' }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 overflow-y-auto px-1">
        <Suspense>
          <FileSelector :file-extensions @submit="handleFileSelected" />
          <template #fallback>
            <div class="flex h-32 items-center justify-center">
              <Spinner size="sm" />
            </div>
          </template>
        </Suspense>
        
        <!-- Selected image preview - compact layout -->
        <div v-if="selectedImageUrl" class="border rounded-md p-3 bg-muted">
          <div class="flex items-center gap-3">
            <img :src="selectedImageUrl" alt="Preview" class="w-12 h-12 object-cover rounded border flex-shrink-0">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-foreground truncate">{{ getImageName(selectedImageUrl) }}</p>
              <p class="text-xs text-muted-foreground">{{ $t('accessibility.selected_image') }}</p>
            </div>
            <Button 
              variant="ghost" 
              size="sm"
              @click="resetImageSelection"
              class="text-destructive hover:text-destructive"
            >
              {{ $t('common.change_selection') }}
            </Button>
          </div>
        </div>
        
        <!-- Accessibility form - shown after image is selected -->
        <div v-if="selectedImageUrl" class="space-y-4">
          <div class="space-y-4">
            <div class="space-y-3">
              <Label :for="'alt-text'">
                {{ $t('accessibility.alt_text') }}
                <span class="text-destructive">*</span>
              </Label>
              
              <!-- Alt text explanation -->
              <div class="rounded-md bg-blue-50 border border-blue-200 p-3 dark:bg-blue-900/20 dark:border-blue-700">
                <p class="text-sm text-blue-800 dark:text-blue-200 font-medium mb-2">
                  {{ $t('accessibility.alt_text_required_explanation') }}
                </p>
                <p class="text-xs text-blue-700 dark:text-blue-300">
                  {{ $t('accessibility.alt_text_example') }}
                </p>
              </div>
              
              <Input 
                id="alt-text"
                v-model="altText"
                :placeholder="$t('accessibility.alt_text_placeholder')"
                required
                :class="{ 'border-destructive': !altText.trim() && selectedImageUrl }"
              />
              <p class="text-xs text-muted-foreground">
                {{ $t('accessibility.alt_text_help') }}
              </p>
            </div>
            
            <div class="space-y-2">
              <Label :for="'title-text'">
                {{ $t('accessibility.title_text') }}
              </Label>
              <Input 
                id="title-text"
                v-model="titleText"
                :placeholder="$t('accessibility.title_text_placeholder')"
              />
              <p class="text-xs text-muted-foreground">
                {{ $t('accessibility.title_text_help') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="resetForm">
          {{ $t('common.cancel') }}
        </Button>
        <Button 
          @click="submitImage"
          :disabled="!selectedImageUrl || !altText.trim()"
        >
          {{ $t('common.insert') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from "laravel-vue-i18n";

import { Spinner } from '@/Components/ui/spinner';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import FileSelector from '@/Features/Admin/FileManager/FileSelector.vue';

const props = defineProps<{
  // only specific values for selectionType are allowed
  selectionType?: 'image' | 'video';
}>()

const showModal = defineModel<boolean>('showModal', { default: false });

const fileExtensions = computed(() => {
  if (props.selectionType === 'video') {
    return ['mp4', 'webm', 'ogg', 'MP4', 'WEBM', 'OGG'];
  }

    return ['jpg', 'jpeg', 'png', 'gif', 'JPG', 'PNG', 'webp', 'GIF', 'WEBP', 'JPEG', 'svg', 'SVG'];
});

const emit = defineEmits<{
  (e: 'submit', imageData: { src: string; alt: string; title: string }): void
}>()

const selectedImageUrl = ref<string>('');
const altText = ref<string>('');
const titleText = ref<string>('');

// Watch for modal close to reset form
watch(showModal, (isOpen) => {
  if (!isOpen) {
    resetForm();
  }
});

function handleFileSelected(filePath: string) {
  // Convert from public/files path to uploads URL
  selectedImageUrl.value = filePath.replace('public/', '/uploads/');
}

function submitImage() {
  if (selectedImageUrl.value && altText.value.trim()) {
    emit('submit', {
      src: selectedImageUrl.value,
      alt: altText.value.trim(),
      title: titleText.value.trim(),
    });
    showModal.value = false;
  }
}

function resetForm() {
  selectedImageUrl.value = '';
  altText.value = '';
  titleText.value = '';
  showModal.value = false;
}

function resetImageSelection() {
  selectedImageUrl.value = '';
  altText.value = '';
  titleText.value = '';
}

function getImageName(url: string): string {
  return url.split('/').pop() || 'Unknown image';
}
</script>
