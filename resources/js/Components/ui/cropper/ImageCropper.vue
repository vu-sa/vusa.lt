<script setup lang="ts">
/**
 * ImageCropper - A modern, user-friendly image cropping component.
 *
 * Features:
 * - Clean, modern UI with intuitive controls
 * - Reactive zoom that syncs with mouse wheel events
 * - High-quality output from original image resolution
 * - Rotation support
 * - Aspect ratio presets with Toggle components
 * - Loading states during processing
 * - Responsive design
 */
import "cropperjs";
import { computed, ref, useTemplateRef, watch, onMounted, onUnmounted } from "vue";
import type { CropperCanvas, CropperImage, CropperSelection } from "cropperjs";

import { Button } from "@/Components/ui/button";
import { DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/Components/ui/dialog";
import { Separator } from "@/Components/ui/separator";
import { Slider } from "@/Components/ui/slider";
import { Toggle } from "@/Components/ui/toggle";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { cn } from "@/Utils/Shadcn/utils";

interface Props {
  /** Aspect ratio constraint (e.g., 16/9, 4/3, 1). Set to 0 or undefined for free crop */
  aspectRatio?: number;
  /** Maximum output width (will preserve aspect ratio) */
  maxOutputWidth?: number;
  /** Maximum output height (will preserve aspect ratio) */
  maxOutputHeight?: number;
  /** Output image quality (0-1) */
  quality?: number;
  /** Output format */
  outputFormat?: "image/jpeg" | "image/png" | "image/webp";
  /** Custom class for the container */
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  aspectRatio: 0,
  maxOutputWidth: 1920,
  maxOutputHeight: 1080,
  quality: 0.9,
  outputFormat: "image/webp",
});

const emit = defineEmits<{
  (e: "crop", data: { dataUrl: string; blob: Blob }): void;
  (e: "cancel"): void;
}>();

const src = defineModel<string>("src", { required: true });

// Refs
const canvas = useTemplateRef<CropperCanvas>("canvas");
const image = useTemplateRef<CropperImage>("image");
const selection = useTemplateRef<CropperSelection>("selection");

// State
const isProcessing = ref(false);
const zoomLevel = ref([1]);
const rotation = ref(0);
const selectedAspectRatio = ref<string>(props.aspectRatio ? String(props.aspectRatio) : "free");
const originalImageSize = ref({ width: 0, height: 0 });

// Aspect ratio presets
const aspectRatioPresets = [
  { label: "Laisvas", value: "free" },
  { label: "1:1", value: "1", ratio: 1 },
  { label: "16:9", value: "16/9", ratio: 16 / 9 },
  { label: "4:3", value: "4/3", ratio: 4 / 3 },
  { label: "3:2", value: "3/2", ratio: 3 / 2 },
];

// Computed aspect ratio value
const currentAspectRatio = computed(() => {
  if (selectedAspectRatio.value === "free") return undefined;
  const preset = aspectRatioPresets.find((p) => p.value === selectedAspectRatio.value);
  return preset?.ratio;
});

// Load original image to get its dimensions
function loadOriginalImageSize() {
  const img = new Image();
  img.onload = () => {
    originalImageSize.value = { width: img.naturalWidth, height: img.naturalHeight };
  };
  img.src = src.value;
}

// Handle zoom events from cropper canvas (mouse wheel)
function handleCanvasTransform() {
  if (!image.value) return;
  
  // Get current transform and calculate zoom level
  const transform = image.value.$getTransform();
  if (transform) {
    // The scale is in the a (scaleX) component of the matrix
    const scale = Math.sqrt(transform.a * transform.a + transform.b * transform.b);
    // Clamp to our range
    const clampedScale = Math.max(0.5, Math.min(3, scale));
    zoomLevel.value = [clampedScale];
  }
}

// Initialize cropper on mount
onMounted(() => {
  loadOriginalImageSize();
  
  setTimeout(() => {
    centerImage();
    
    // Listen for transform changes on the canvas
    if (canvas.value) {
      canvas.value.addEventListener("transform", handleCanvasTransform);
    }
  }, 100);
});

onUnmounted(() => {
  if (canvas.value) {
    canvas.value.removeEventListener("transform", handleCanvasTransform);
  }
});

// Watch for aspect ratio changes
watch(selectedAspectRatio, () => {
  updateSelectionAspectRatio();
});

function updateSelectionAspectRatio() {
  if (!selection.value) return;

  const ratio = currentAspectRatio.value;
  if (ratio) {
    selection.value.setAttribute("aspect-ratio", String(ratio));
  } else {
    selection.value.removeAttribute("aspect-ratio");
  }
}

function centerImage() {
  if (!canvas.value || !image.value) return;
  image.value.$center("contain");
  zoomLevel.value = [1];
  rotation.value = 0;
}

function handleZoom(value: number[]) {
  if (!image.value) return;
  
  const currentZoom = zoomLevel.value[0];
  const newZoom = value[0];
  const scaleFactor = newZoom / currentZoom;
  
  // Apply zoom centered on image
  image.value.$zoom(scaleFactor - 1);
  zoomLevel.value = value;
}

function zoomIn() {
  const newZoom = Math.min(zoomLevel.value[0] + 0.2, 3);
  handleZoom([newZoom]);
}

function zoomOut() {
  const newZoom = Math.max(zoomLevel.value[0] - 0.2, 0.5);
  handleZoom([newZoom]);
}

function rotate(degrees: number) {
  if (!image.value) return;
  image.value.$rotate(`${degrees}deg`);
  rotation.value = (rotation.value + degrees) % 360;
}

function resetTransforms() {
  if (!image.value) return;
  image.value.$setTransform(1, 0, 0, 1, 0, 0);
  centerImage();
}

async function handleCrop() {
  if (!selection.value || isProcessing.value) return;

  isProcessing.value = true;

  try {
    // Calculate output dimensions based on original image and selection
    // We want to output at the highest resolution possible up to max limits
    let outputWidth = props.maxOutputWidth;
    let outputHeight = props.maxOutputHeight;
    
    // If we have a fixed aspect ratio, calculate proper dimensions
    if (currentAspectRatio.value) {
      const aspectRatio = currentAspectRatio.value;
      if (outputWidth / outputHeight > aspectRatio) {
        outputWidth = Math.round(outputHeight * aspectRatio);
      } else {
        outputHeight = Math.round(outputWidth / aspectRatio);
      }
    }
    
    // Create canvas at high resolution from the selection
    const resultCanvas = await selection.value.$toCanvas({
      width: outputWidth,
      height: outputHeight,
    });

    const dataUrl = resultCanvas.toDataURL(props.outputFormat, props.quality);

    // Convert to blob
    const response = await fetch(dataUrl);
    const blob = await response.blob();

    emit("crop", { dataUrl, blob });
  } catch (error) {
    console.error("Crop failed:", error);
  } finally {
    isProcessing.value = false;
  }
}

function handleCancel() {
  emit("cancel");
}
</script>

<template>
  <div :class="cn('flex flex-col', props.class)">
    <!-- Dialog Header -->
    <DialogHeader class="px-6 pt-6">
      <DialogTitle>{{ $t("Redaguoti paveikslėlį") }}</DialogTitle>
      <DialogDescription>
        {{ $t("Naudokite pelės ratukę priartinimui, vilkite paveikslėlį arba pasirinkimo rėmelį.") }}
      </DialogDescription>
    </DialogHeader>

    <Separator class="my-4" />

    <!-- Cropper Canvas Area -->
    <div class="relative mx-6 overflow-hidden rounded-lg bg-zinc-950">
      <cropper-canvas
        ref="canvas"
        class="h-[350px] w-full md:h-[450px]"
        background
        scale-step="0.1"
      >
        <cropper-image
          ref="image"
          :src="src"
          alt="Image to be cropped"
          rotatable
          scalable
          translatable
        />
        <cropper-shade hidden />
        <cropper-handle action="select" plain hidden />
        <cropper-selection
          ref="selection"
          initial-coverage="0.8"
          movable
          resizable
          zoomable
          :aspect-ratio="currentAspectRatio"
        >
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

      <!-- Processing Overlay -->
      <div
        v-if="isProcessing"
        class="absolute inset-0 flex items-center justify-center bg-black/60"
      >
        <div class="flex flex-col items-center gap-2 text-white">
          <IFluentSpinnerIos20Filled class="h-8 w-8 animate-spin" />
          <span class="text-sm font-medium">{{ $t("Apdorojama...") }}</span>
        </div>
      </div>
    </div>

    <!-- Controls -->
    <div class="flex flex-col gap-4 px-6 py-4">
      <!-- Aspect Ratio Selection with Toggles -->
      <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm font-medium text-muted-foreground">{{ $t("Proporcijos") }}:</span>
        <div class="flex gap-1">
          <TooltipProvider>
            <Tooltip v-for="preset in aspectRatioPresets" :key="preset.value">
              <TooltipTrigger as-child>
                <Toggle
                  :pressed="selectedAspectRatio === preset.value"
                  variant="outline"
                  size="sm"
                  @update:pressed="(pressed: boolean) => pressed && (selectedAspectRatio = preset.value)"
                >
                  {{ preset.label }}
                </Toggle>
              </TooltipTrigger>
              <TooltipContent>
                <p>{{ preset.label === "Laisvas" ? $t("Laisvos proporcijos") : $t("Proporcijos") + " " + preset.label }}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      </div>

      <!-- Zoom & Transform Controls -->
      <div class="flex flex-wrap items-center gap-4">
        <!-- Zoom Slider -->
        <div class="flex flex-1 items-center gap-3">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button
                  type="button"
                  variant="outline"
                  size="icon-sm"
                  @click="zoomOut"
                >
                  <IFluentZoomOut20Regular class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>{{ $t("Nutolinti") }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <Slider
            :model-value="zoomLevel"
            :min="0.5"
            :max="3"
            :step="0.1"
            class="w-32 md:w-48"
            @update:model-value="handleZoom"
          />

          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button
                  type="button"
                  variant="outline"
                  size="icon-sm"
                  @click="zoomIn"
                >
                  <IFluentZoomIn20Regular class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>{{ $t("Priartinti") }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <!-- Rotation Controls -->
        <div class="flex items-center gap-1">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button
                  type="button"
                  variant="outline"
                  size="icon-sm"
                  @click="rotate(-90)"
                >
                  <IFluentArrowRotateCounterclockwise20Regular class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>{{ $t("Pasukti kairėn") }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button
                  type="button"
                  variant="outline"
                  size="icon-sm"
                  @click="rotate(90)"
                >
                  <IFluentArrowRotateClockwise20Regular class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>{{ $t("Pasukti dešinėn") }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <!-- Reset Button -->
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="resetTransforms"
              >
                <IFluentArrowReset20Regular class="mr-1.5 h-4 w-4" />
                {{ $t("Atstatyti") }}
              </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t("Atstatyti visus pakeitimus") }}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>
    </div>

    <!-- Dialog Footer with Actions -->
    <Separator />
    <DialogFooter class="px-6 py-4">
      <Button type="button" variant="outline" @click="handleCancel">
        {{ $t("Atšaukti") }}
      </Button>
      <Button
        type="button"
        :disabled="isProcessing"
        @click="handleCrop"
      >
        <IFluentCrop20Regular v-if="!isProcessing" class="mr-1.5 h-4 w-4" />
        <IFluentSpinnerIos20Filled v-else class="mr-1.5 h-4 w-4 animate-spin" />
        {{ isProcessing ? $t("Apdorojama...") : $t("Apkirpti ir išsaugoti") }}
      </Button>
    </DialogFooter>
  </div>
</template>

<style>
/* Cropper.js custom styling */
cropper-canvas {
  --cropper-backdrop-color: rgb(24 24 27 / 0.9);
  --cropper-overlay-color: rgb(0 0 0 / 0.5);
}

cropper-selection {
  --cropper-selection-outline-color: rgb(255 255 255);
  --cropper-selection-outline-width: 2px;
}

cropper-grid {
  --cropper-grid-border-color: rgb(255 255 255 / 0.3);
  --cropper-grid-border-width: 1px;
}

cropper-crosshair {
  --cropper-crosshair-color: rgb(255 255 255 / 0.5);
}

cropper-handle {
  --cropper-handle-background-color: rgb(255 255 255);
  --cropper-handle-border-color: rgb(59 130 246);
}

cropper-handle[action="move"] {
  --cropper-handle-background-color: transparent;
}
</style>
