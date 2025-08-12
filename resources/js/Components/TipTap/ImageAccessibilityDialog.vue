<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Edit Image Accessibility</DialogTitle>
        <DialogDescription>
          Improve the accessibility of your image by providing meaningful alternative text and title.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- Preview of the image -->
        <div v-if="imageData.src" class="flex justify-center">
          <img 
            :src="imageData.src" 
            :alt="formData.alt || 'Image preview'" 
            class="max-w-full max-h-32 rounded-md object-contain"
          />
        </div>

        <!-- Alt text field -->
        <div class="space-y-2">
          <Label for="alt-text">
            Alternative Text (Alt Text) *
          </Label>
          <Input 
            id="alt-text"
            v-model="formData.alt"
            placeholder="Describe what the image shows..."
            maxlength="125"
          />
          <p class="text-xs text-muted-foreground">
            Briefly describe what the image shows. This text is read by screen readers.
            {{ formData.alt?.length || 0 }}/125 characters.
          </p>
        </div>

        <!-- Title field -->
        <div class="space-y-2">
          <Label for="title-text">
            Title (Tooltip Text)
          </Label>
          <Input 
            id="title-text"
            v-model="formData.title"
            placeholder="Additional context or caption..."
          />
          <p class="text-xs text-muted-foreground">
            Optional. Shows as a tooltip when users hover over the image.
          </p>
        </div>

        <!-- Quick examples -->
        <div class="text-xs text-muted-foreground">
          <strong>Examples:</strong>
          <ul class="mt-1 space-y-1 list-disc list-inside">
            <li>For photos: "Students celebrating graduation in the main hall"</li>
            <li>For charts: "Bar chart showing 65% increase in enrollment"</li>
            <li>For decorative images: Leave alt text empty</li>
          </ul>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="handleCancel">
          Cancel
        </Button>
        <Button @click="handleSubmit" :disabled="!canSubmit">
          Update Image
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { 
  Dialog, 
  DialogContent, 
  DialogDescription, 
  DialogFooter, 
  DialogHeader, 
  DialogTitle 
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
  title: ''
});

// Reset form when dialog opens
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    formData.value = {
      alt: props.imageData.alt || '',
      title: props.imageData.title || ''
    };
  }
});

const canSubmit = computed(() => {
  // Allow submission even with empty alt text (for decorative images)
  return true;
});

function handleSubmit() {
  emit('submit', {
    alt: formData.value.alt.trim(),
    title: formData.value.title.trim()
  });
  emit('update:open', false);
}

function handleCancel() {
  emit('update:open', false);
}
</script>