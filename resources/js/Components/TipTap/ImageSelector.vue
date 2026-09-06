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
                  <StepperDescription class="hidden sm:block">{{ $t('accessibility.select_file_step') || 'Pasirink failą' }}</StepperDescription>
                </div>
              </StepperTrigger>
              <StepperSeparator />
            </StepperItem>
            <StepperItem :step="2" :disabled="!selectedImageUrl">
              <StepperTrigger>
                <StepperIndicator>2</StepperIndicator>
                <div>
                  <StepperTitle>{{ $t('accessibility.details') || 'Aprašas' }}</StepperTitle>
                  <StepperDescription class="hidden sm:block">{{ $t('accessibility.add_alt_title') || 'Pridėk alt ir pavadinimą' }}</StepperDescription>
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
                <p class="text-sm font-medium text-foreground truncate">
                  {{ getImageName(selectedImageUrl) }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ $t('accessibility.selected_image') }}
                </p>
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
              <FormField v-slot="{ componentField, value }" name="alt">
                <FormItem>
                  <FormLabel>
                    {{ $t('accessibility.alt_text') }}
                    <span class="text-destructive">*</span>
                  </FormLabel>
                  <FormControl>
                    <Input
                      v-bind="componentField"
                      :placeholder="$t('accessibility.alt_text_placeholder')"
                      :disabled="isDecorative"
                      maxlength="125"
                    />
                  </FormControl>
                  <FormMessage />

                  <!-- The rationale costs one line until asked for: an author who
                       already knows it inserts images all day and shouldn't scroll
                       past a paragraph every time. -->
                  <Collapsible v-model:open="altHelpOpen">
                    <div class="flex items-center justify-between gap-2">
                      <CollapsibleTrigger as-child>
                        <!-- Explicit type: shadcn's Button sets none, so inside a form this
                             toggle would submit it. -->
                        <Button type="button" variant="ghost" size="sm"
                          class="h-auto gap-2 p-0 text-xs font-normal has-[>svg]:p-0 text-muted-foreground hover:bg-transparent hover:text-foreground">
                          <Info class="size-4" />
                          {{ $t('accessibility.why_alt_required') }}
                          <ChevronDown class="size-3 transition-transform duration-200"
                            :class="{ 'rotate-180': altHelpOpen }" />
                        </Button>
                      </CollapsibleTrigger>
                      <span class="text-xs tabular-nums text-muted-foreground">
                        {{ (value as string | undefined)?.length ?? 0 }}/125
                      </span>
                    </div>
                    <CollapsibleContent class="space-y-1 pt-2 text-xs leading-relaxed text-muted-foreground">
                      <p>{{ $t('accessibility.alt_text_required_explanation') }}</p>
                      <p>{{ $t('accessibility.alt_text_example') }}</p>
                    </CollapsibleContent>
                  </Collapsible>

                  <!-- Matches ImageAccessibilityDialog: a purely decorative image is
                       better served by an empty alt than by a description a screen
                       reader has to read out for nothing. -->
                  <div class="flex items-center gap-2">
                    <Checkbox id="image-decorative" v-model="isDecorative" />
                    <Label for="image-decorative" class="text-xs font-normal text-muted-foreground">
                      {{ $t('accessibility.image_is_decorative') }}
                    </Label>
                  </div>
                </FormItem>
              </FormField>

              <FormField v-slot="{ componentField }" name="title">
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
            :disabled="!selectedImageUrl"
            @click="goToDetails"
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
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, Info } from 'lucide-vue-next';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
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
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import FileSelector from '@/Features/Admin/FileManager/FileSelector.vue';

const props = defineProps<{
  // only specific values for selectionType are allowed
  selectionType?: 'image' | 'video';
}>();

const showModal = defineModel<boolean>('showModal', { default: false });

const emit = defineEmits<(e: 'submit', imageData: { src: string; alt: string; title: string }) => void>();

const selectedImageUrl = ref<string>('');
const currentStep = ref<number>(1);
const altHelpOpen = ref(false);
const isDecorative = ref(false);

// Validation schema for accessibility details. Recomputed so ticking "decorative"
// lifts the alt requirement instead of leaving the form unsubmittable.
const formSchema = computed(() => toTypedSchema(z.object({
  alt: isDecorative.value
    ? z.string().optional()
    : z.string().trim().min(1, { message: $t('accessibility.alt_text_required') || 'Alt tekstas privalomas' }),
  title: z.string().max(200, { message: $t('validation.max') || 'Per ilgas pavadinimas' }).optional().or(z.literal('')),
})));

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

function handleFileSelected(filePath: string, source: 'browse' | 'upload' = 'browse') {
  // Convert from public/files path to uploads URL
  selectedImageUrl.value = filePath.replace('public/', '/uploads/');

  // A just-uploaded file needs no confirming step in the listing — the author picked it by
  // uploading it, so skip straight to the details they still have to fill in.
  if (source === 'upload') {
    currentStep.value = 2;
  }
}

function goToDetails() {
  if (selectedImageUrl.value) currentStep.value = 2;
}

function goBackToSelection() {
  currentStep.value = 1;
}

function onSubmitDetails(values: { alt?: string; title?: string }) {
  if (!selectedImageUrl.value) return;
  emit('submit', {
    src: selectedImageUrl.value,
    alt: isDecorative.value ? '' : (values.alt ?? '').trim(),
    title: (values.title ?? '').trim(),
  });
  showModal.value = false;
}

function resetForm() {
  selectedImageUrl.value = '';
  showModal.value = false;
  currentStep.value = 1;
  isDecorative.value = false;
}


function getImageName(url: string): string {
  return url.split('/').pop() || 'Unknown image';
}
</script>
