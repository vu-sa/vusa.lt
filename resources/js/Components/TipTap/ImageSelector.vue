<template>
  <Dialog :open="showModal" @update:open="showModal = $event">
    <DialogContent class="sm:max-w-4xl max-h-[90vh] !p-0 flex flex-col">
      <div class="px-8 pt-8">
        <DialogHeader>
          <DialogTitle>{{ $t('accessibility.select_image') || 'Pasirinkti paveikslėlį' }}</DialogTitle>
          <DialogDescription>
            {{ $t('accessibility.image_selection_help_text') || 'Pasirinkite paveikslėlį iš failų tvarkyklės ir nustatykite jo prieinamumo savybes.' }}
          </DialogDescription>
        </DialogHeader>

        <!-- Horizontal Stepper -->
        <div class="mt-4">
          <Stepper v-model="currentStep" class="w-full items-center justify-center">
            <StepperItem :step="1">
              <StepperTrigger>
                <StepperIndicator>1</StepperIndicator>
                <div>
                  <StepperTitle>{{ $t('common.select') }}</StepperTitle>
                  <StepperDescription>{{ $t('accessibility.select_file_step') || 'Pasirink failą' }}</StepperDescription>
                </div>
              </StepperTrigger>
              <StepperSeparator />
            </StepperItem>
            <StepperItem :step="2" :disabled="!selectedImageUrl">
              <StepperTrigger>
                <StepperIndicator>2</StepperIndicator>
                <div>
                  <StepperTitle>{{ $t('accessibility.details') || 'Aprašas' }}</StepperTitle>
                  <StepperDescription>{{ $t('accessibility.add_alt_title') || 'Pridėk alt ir pavadinimą' }}</StepperDescription>
                </div>
              </StepperTrigger>
            </StepperItem>
          </Stepper>
        </div>
      </div>

      <div class="space-y-4 overflow-y-auto px-8 pb-6 flex-1">
        <!-- Step 1: File selection -->
        <div v-if="currentStep === 1">
          <FileSelector 
            :upload-accept="selectionType === 'video' ? '.mp4,.webm,.ogg' : '.jpg,.jpeg,.png,.gif,.webp,.svg'"
            :upload-extensions="selectionType === 'video' 
              ? ['mp4','webm','ogg'] 
              : ['jpg','jpeg','png','gif','webp','svg']"
            @submit="handleFileSelected" 
          />
        </div>
        
        <!-- Step 2: Details form with preview -->
        <div v-else-if="currentStep === 2" class="space-y-4">
          <!-- Selected image preview - compact layout -->
          <div class="border rounded-md p-3 bg-muted">
            <div class="flex items-center gap-3">
              <img :src="selectedImageUrl" alt="Preview" class="w-12 h-12 object-cover rounded border flex-shrink-0">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-foreground truncate">{{ getImageName(selectedImageUrl) }}</p>
                <p class="text-xs text-muted-foreground">{{ $t('accessibility.selected_image') }}</p>
              </div>
              <Button 
                variant="ghost" 
                size="sm"
                @click="goBackToSelection"
              >
                {{ $t('common.change_selection') }}
              </Button>
            </div>
          </div>

          <!-- Accessibility form (vee-validate + zod) -->
          <Form
            id="image-details-form"
            :validation-schema="formSchema"
            :initial-values="{ alt: '', title: '' }"
            @submit="onSubmitDetails"
          >
            <div class="space-y-4">
              <FormField name="alt" v-slot="{ componentField }">
                <FormItem>
                  <FormLabel>
                    {{ $t('accessibility.alt_text') }}
                    <span class="text-destructive">*</span>
                  </FormLabel>
                  <!-- Alt text explanation -->
                  <div class="rounded-md bg-blue-50 border border-blue-200 p-3 dark:bg-blue-900/20 dark:border-blue-700 mb-2">
                    <p class="text-sm text-blue-800 dark:text-blue-200 font-medium mb-2">
                      {{ $t('accessibility.alt_text_required_explanation') }}
                    </p>
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                      {{ $t('accessibility.alt_text_example') }}
                    </p>
                  </div>
                  <FormControl>
                    <Input 
                      v-bind="componentField"
                      :placeholder="$t('accessibility.alt_text_placeholder')"
                    />
                  </FormControl>
                  <FormMessage />
                  <p class="text-xs text-muted-foreground">
                    {{ $t('accessibility.alt_text_help') }}
                  </p>
                </FormItem>
              </FormField>

              <FormField name="title" v-slot="{ componentField }">
                <FormItem>
                  <FormLabel>
                    {{ $t('accessibility.title_text') }}
                  </FormLabel>
                  <FormControl>
                    <Input 
                      v-bind="componentField"
                      :placeholder="$t('accessibility.title_text_placeholder')"
                    />
                  </FormControl>
                  <FormMessage />
                  <p class="text-xs text-muted-foreground">
                    {{ $t('accessibility.title_text_help') }}
                  </p>
                </FormItem>
              </FormField>
            </div>
          </Form>
        </div>
  </div>
      <DialogFooter class="px-8 pb-6">
        <Button variant="outline" @click="resetForm">
          {{ $t('common.cancel') }}
        </Button>
        <template v-if="currentStep === 1">
          <Button 
            variant="default"
            @click="goToDetails"
            :disabled="!selectedImageUrl"
          >
            {{ $t('common.next') || 'Toliau' }}
          </Button>
        </template>
        <template v-else>
          <Button variant="secondary" @click="goBackToSelection">
            {{ $t('common.back') || 'Atgal' }}
          </Button>
          <Button 
            type="submit"
            form="image-details-form"
            :disabled="!selectedImageUrl"
          >
            {{ $t('common.insert') }}
          </Button>
        </template>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue';
import { trans as $t } from "laravel-vue-i18n";

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { 
  Stepper,
  StepperItem,
  StepperTrigger,
  StepperIndicator,
  StepperSeparator,
  StepperTitle,
  StepperDescription,
} from '@/Components/ui/stepper';
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from '@/Components/ui/form';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';
import FileSelector from '@/Features/Admin/FileManager/FileSelector.vue';

const props = defineProps<{
  // only specific values for selectionType are allowed
  selectionType?: 'image' | 'video';
}>()

const showModal = defineModel<boolean>('showModal', { default: false });

const emit = defineEmits<{
  (e: 'submit', imageData: { src: string; alt: string; title: string }): void
}>()

const selectedImageUrl = ref<string>('');
const currentStep = ref<number>(1);

// Validation schema for accessibility details
const formSchema = toTypedSchema(z.object({
  alt: z.string().trim().min(1, { message: $t('accessibility.alt_text_required') || 'Alt tekstas privalomas' }),
  title: z.string().max(200, { message: $t('validation.max') || 'Per ilgas pavadinimas' }).optional().or(z.literal('')),
}));

// Watch for modal close to reset form
watch(showModal, (isOpen) => {
  if (!isOpen) {
    resetForm();
  }
});

// Prevent going to step 2 without a selection
watch(currentStep, (step) => {
  if (step === 2 && !selectedImageUrl.value) {
    currentStep.value = 1;
  }
});

function handleFileSelected(filePath: string) {
  // Convert from public/files path to uploads URL
  selectedImageUrl.value = filePath.replace('public/', '/uploads/');
}

function goToDetails() {
  if (selectedImageUrl.value) currentStep.value = 2;
}

function goBackToSelection() {
  currentStep.value = 1;
}

function onSubmitDetails(values: { alt: string; title?: string }) {
  if (!selectedImageUrl.value) return;
  emit('submit', {
    src: selectedImageUrl.value,
    alt: values.alt.trim(),
    title: (values.title ?? '').trim(),
  });
  showModal.value = false;
}

function resetForm() {
  selectedImageUrl.value = '';
  showModal.value = false;
  currentStep.value = 1;
}

function resetImageSelection() {
  selectedImageUrl.value = '';
  currentStep.value = 1;
}

function getImageName(url: string): string {
  return url.split('/').pop() || 'Unknown image';
}
</script>
