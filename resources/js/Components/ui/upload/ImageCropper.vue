<template>
  <div :class="cn('flex flex-col', props.class)" data-slot="image-cropper">
    <DialogHeader class="gap-1 px-4 pt-5 text-left sm:px-6 sm:pt-6">
      <DialogTitle>{{ $t('Redaguoti nuotrauką') }}</DialogTitle>
      <DialogDescription>{{ $t('Vilk nuotrauką arba kadro rėmelį. Mastelį keisk ratuku arba valdikliais.') }}</DialogDescription>
    </DialogHeader>

    <div class="relative mx-4 mt-4 overflow-hidden bg-ink sm:mx-6">
      <cropper-canvas ref="canvas" class="h-[clamp(220px,40dvh,480px)] w-full" background scale-step="0.1">
        <cropper-image ref="image" :src :alt="$t('Apkerpama nuotrauka')" rotatable scalable translatable />
        <cropper-shade theme-color="rgba(0, 0, 0, 0.6)" />
        <cropper-handle action="select" plain hidden />
        <cropper-selection ref="selection" initial-coverage="0.8" movable resizable zoomable>
          <cropper-grid role="grid" covered />
          <cropper-crosshair centered />
          <cropper-handle action="move" theme-color="rgba(255, 255, 255, 0.35)" />
          <cropper-handle action="n-resize" />
          <cropper-handle action="e-resize" />
          <cropper-handle action="s-resize" />
          <cropper-handle action="w-resize" />
          <cropper-handle action="ne-resize" />
          <cropper-handle action="nw-resize" />
          <cropper-handle action="se-resize" />
          <cropper-handle action="sw-resize" />
        </cropper-selection>
      </cropper-canvas>
      <div v-if="isProcessing" class="absolute inset-0 flex items-center justify-center bg-ink/70" role="status">
        <div class="flex items-center gap-2 text-white">
          <LoaderCircle class="size-5 animate-spin" aria-hidden="true" />
          <span class="text-sm font-medium">{{ $t('Apdorojama...') }}</span>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-5 px-4 py-5 sm:px-6">
      <div class="flex flex-col gap-2">
        <span class="text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('Proporcijos') }}</span>
        <div role="group" :aria-label="$t('Proporcijos')" class="flex flex-wrap gap-1.5">
          <button
            v-for="preset in aspectRatioPresets"
            :key="preset.value"
            type="button"
            :aria-pressed="selectedAspectRatio === preset.value"
            :class="controlVariants({ active: selectedAspectRatio === preset.value })"
            class="pointer-coarse:min-h-11"
            @click="selectedAspectRatio = preset.value"
          >
            {{ $t(preset.label) }}
          </button>
        </div>
      </div>

      <div class="flex flex-wrap items-end justify-between gap-4 border-t border-border pt-4">
        <div class="flex min-w-44 flex-1 flex-col gap-2">
          <label for="cropper-zoom" class="text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('Mastelis') }}</label>
          <div class="flex items-center gap-2">
            <Button type="button" variant="outline" size="icon-sm" :aria-label="$t('Atitolinti')" @click="zoomOut">
              <ZoomOut aria-hidden="true" />
            </Button>
            <Slider
              id="cropper-zoom"
              :model-value="zoomLevel"
              :min="0.5"
              :max="3"
              :step="0.1"
              :aria-label="$t('Mastelis')"
              class="min-w-24 flex-1"
              @update:model-value="handleZoom"
            />
            <Button type="button" variant="outline" size="icon-sm" :aria-label="$t('Priartinti')" @click="zoomIn">
              <ZoomIn aria-hidden="true" />
            </Button>
          </div>
        </div>
        <div class="flex items-center gap-1.5">
          <Button type="button" variant="outline" size="icon-sm" :aria-label="$t('Pasukti į kairę')" @click="rotate(-90)">
            <RotateCcw aria-hidden="true" />
          </Button>
          <Button type="button" variant="outline" size="icon-sm" :aria-label="$t('Pasukti į dešinę')" @click="rotate(90)">
            <RotateCw aria-hidden="true" />
          </Button>
          <Button type="button" variant="ghost" size="sm" @click="resetEditor">
            <Undo2 aria-hidden="true" />{{ $t('Atstatyti') }}
          </Button>
        </div>
      </div>
      <p v-if="cropError" role="alert" class="text-sm text-destructive">
        {{ cropError }}
      </p>
    </div>

    <DialogFooter class="sticky bottom-0 gap-2 border-t border-border bg-popover px-4 py-4 sm:px-6">
      <Button type="button" variant="outline" @click="emit('cancel')">
        {{ $t('Atšaukti') }}
      </Button>
      <Button type="button" variant="brand" :disabled="isProcessing || !isReady" @click="handleCrop">
        <LoaderCircle v-if="isProcessing" class="animate-spin" aria-hidden="true" />
        <Crop v-else aria-hidden="true" />
        {{ isProcessing ? $t('Apdorojama...') : $t('Išsaugoti kadrą') }}
      </Button>
    </DialogFooter>
  </div>
</template>

<script setup lang="ts">
import 'cropperjs';
import type { CropperCanvas, CropperImage, CropperSelection } from 'cropperjs';
import { Crop, LoaderCircle, RotateCcw, RotateCw, Undo2, ZoomIn, ZoomOut } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';

import { Button } from '@/Components/ui/button';
import { controlVariants } from '@/Components/ui/control';
import { DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Slider } from '@/Components/ui/slider';
import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  aspectRatio?: number;
  maxOutputWidth?: number;
  maxOutputHeight?: number;
  quality?: number;
  outputFormat?: 'image/jpeg' | 'image/png' | 'image/webp';
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  aspectRatio: 0,
  maxOutputWidth: 2048,
  maxOutputHeight: 2048,
  quality: 0.9,
  outputFormat: 'image/webp',
  class: undefined,
});

const emit = defineEmits<{
  crop: [data: { dataUrl: string; blob: Blob }];
  cancel: [];
}>();

const src = defineModel<string>('src', { required: true });
const canvas = useTemplateRef<CropperCanvas>('canvas');
const image = useTemplateRef<CropperImage>('image');
const selection = useTemplateRef<CropperSelection>('selection');

const aspectRatioPresets = [
  { label: 'Laisvai', value: 'free', ratio: NaN },
  { label: '1:1', value: '1', ratio: 1 },
  { label: '16:9', value: '16/9', ratio: 16 / 9 },
  { label: '4:3', value: '4/3', ratio: 4 / 3 },
  { label: '3:2', value: '3/2', ratio: 3 / 2 },
] as const;

function ratioChoice(ratio: number): string {
  if (!Number.isFinite(ratio) || ratio <= 0) return 'free';
  return aspectRatioPresets.find(preset => Math.abs(preset.ratio - ratio) < 0.0001)?.value ?? String(ratio);
}

const selectedAspectRatio = ref(ratioChoice(props.aspectRatio));
const currentAspectRatio = computed(() => {
  if (selectedAspectRatio.value === 'free') return 0;
  return aspectRatioPresets.find(preset => preset.value === selectedAspectRatio.value)?.ratio
    ?? Number(selectedAspectRatio.value);
});
const isProcessing = ref(false);
const isReady = ref(false);
const cropError = ref('');
const zoomLevel = ref([1]);
const baseScale = ref(1);
let naturalImageSize = { width: 0, height: 0 };

function imageScale(): number {
  if (!image.value) return 1;
  const [a, b] = image.value.$getTransform();
  return Math.hypot(a, b);
}

function syncZoom() {
  const scale = imageScale() / baseScale.value;
  zoomLevel.value = [Math.max(0.5, Math.min(3, scale))];
}

function applyAspectRatio() {
  if (selection.value) selection.value.aspectRatio = currentAspectRatio.value;
}

watch(selectedAspectRatio, applyAspectRatio);
watch(() => props.aspectRatio, (ratio) => {
  selectedAspectRatio.value = ratioChoice(ratio);
});

function fitImage() {
  if (!image.value || !canvas.value || !naturalImageSize.width || !naturalImageSize.height) return;
  const canvasWidth = canvas.value.clientWidth;
  const canvasHeight = canvas.value.clientHeight;
  if (!canvasWidth || !canvasHeight) return;

  const scale = Math.min(canvasWidth / naturalImageSize.width, canvasHeight / naturalImageSize.height);
  image.value.$setTransform(
    scale, 0, 0, scale,
    (canvasWidth - naturalImageSize.width) / 2,
    (canvasHeight - naturalImageSize.height) / 2,
  );
  baseScale.value = scale;
  zoomLevel.value = [1];
}

function placeSelectionWithinImage() {
  if (!canvas.value || !selection.value || !naturalImageSize.width || !naturalImageSize.height) return;
  const canvasWidth = canvas.value.clientWidth;
  const canvasHeight = canvas.value.clientHeight;

  let width = naturalImageSize.width * baseScale.value * 0.9;
  let height = naturalImageSize.height * baseScale.value * 0.9;
  const ratio = currentAspectRatio.value;
  if (ratio > 0) {
    if (width / height > ratio) width = height * ratio;
    else height = width / ratio;
  }
  selection.value.$change(
    (canvasWidth - width) / 2,
    (canvasHeight - height) / 2,
    width,
    height,
    ratio,
  );
}

function handleCanvasTransform() {
  if (isReady.value) syncZoom();
}

onMounted(async () => {
  if (!image.value) return;
  try {
    const loadedImage = await image.value.$ready();
    naturalImageSize = { width: loadedImage.naturalWidth, height: loadedImage.naturalHeight };
    fitImage();
    applyAspectRatio();
    await new Promise<void>(resolve => requestAnimationFrame(() => resolve()));
    placeSelectionWithinImage();
    canvas.value?.addEventListener('transform', handleCanvasTransform);
    isReady.value = true;
  }
  catch {
    cropError.value = $t('Nepavyko atverti nuotraukos. Bandyk dar kartą.');
  }
});

onUnmounted(() => canvas.value?.removeEventListener('transform', handleCanvasTransform));

function handleZoom(value: number[]) {
  if (!image.value || !isReady.value || !value[0]) return;
  const relativeScale = value[0] / (imageScale() / baseScale.value);
  image.value.$zoom(relativeScale >= 1 ? relativeScale - 1 : 1 - 1 / relativeScale);
  syncZoom();
}

function zoomIn() {
  handleZoom([Math.min(zoomLevel.value[0] + 0.2, 3)]);
}

function zoomOut() {
  handleZoom([Math.max(zoomLevel.value[0] - 0.2, 0.5)]);
}

function rotate(degrees: number) {
  image.value?.$rotate(`${degrees}deg`);
}

function resetEditor() {
  selectedAspectRatio.value = ratioChoice(props.aspectRatio);
  fitImage();
  selection.value?.$reset();
  applyAspectRatio();
  requestAnimationFrame(placeSelectionWithinImage);
  cropError.value = '';
}

async function handleCrop() {
  if (!selection.value || !isReady.value || isProcessing.value) return;
  isProcessing.value = true;
  cropError.value = '';
  try {
    const scale = imageScale();
    const sourceWidth = selection.value.width / scale;
    const sourceHeight = selection.value.height / scale;
    if (!Number.isFinite(sourceWidth) || !Number.isFinite(sourceHeight) || sourceWidth <= 0 || sourceHeight <= 0) {
      throw new Error('Invalid crop area');
    }

    const outputScale = Math.min(1, props.maxOutputWidth / sourceWidth, props.maxOutputHeight / sourceHeight);
    const outputWidth = Math.max(1, Math.round(sourceWidth * outputScale));
    const resultCanvas = await selection.value.$toCanvas({ width: outputWidth });
    const blob = await new Promise<Blob>((resolve, reject) => {
      resultCanvas.toBlob(value => value ? resolve(value) : reject(new Error('Image encoding failed')), props.outputFormat, props.quality);
    });
    const dataUrl = await new Promise<string>((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result));
      reader.onerror = () => reject(reader.error);
      reader.readAsDataURL(blob);
    });
    emit('crop', { dataUrl, blob });
  }
  catch {
    cropError.value = $t('Nepavyko apkirpti nuotraukos. Bandyk dar kartą.');
  }
  finally {
    isProcessing.value = false;
  }
}
</script>

<style>
cropper-canvas {
  --cropper-backdrop-color: rgb(0 0 0 / 0.9);
  --cropper-overlay-color: rgb(0 0 0 / 0.5);
}

cropper-selection {
  --cropper-selection-outline-color: white;
  --cropper-selection-outline-width: 2px;
}

cropper-grid {
  --cropper-grid-border-color: rgb(255 255 255 / 0.3);
}

cropper-crosshair {
  --cropper-crosshair-color: rgb(255 255 255 / 0.5);
}

cropper-handle {
  --cropper-handle-background-color: white;
  --cropper-handle-border-color: white;
}
</style>
