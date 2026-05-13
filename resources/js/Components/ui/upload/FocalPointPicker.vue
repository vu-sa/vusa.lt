<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <Label class="text-sm font-medium text-muted-foreground">
        {{ $t('Fokuso taškas') }}
      </Label>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        class="h-7 text-xs"
        @click="resetToDefault"
      >
        {{ $t('Atstatyti') }}
      </Button>
    </div>

    <div
      ref="containerRef"
      class="relative cursor-crosshair overflow-hidden rounded-lg border-2 border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800"
      @mousedown="handleMouseDown"
      @mousemove="handleMouseMove"
      @mouseup="handleMouseUp"
      @mouseleave="handleMouseUp"
    >
      <img
        :src="imageUrl"
        alt="Focal point preview"
        class="block h-auto w-full select-none"
        draggable="false"
        @load="handleImageLoad"
      >

      <!-- Simple circle indicator -->
      <div
        class="pointer-events-none absolute size-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white/90 bg-vusa-red/90 shadow-lg ring-2 ring-black/20"
        :style="{
          left: `${currentX}%`,
          top: `${currentY}%`,
        }"
      />
    </div>

    <p class="text-xs text-muted-foreground">
      {{ $t('Spustelėkite arba vilkite paveikslėlį, kad nustatytumėte fokuso tašką.') }}
      <span class="font-mono text-foreground">{{ displayValue }}</span>
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

const props = defineProps<{
  imageUrl: string;
  modelValue?: string | null;
}>();

const emit = defineEmits<(e: 'update:modelValue', value: string) => void>();

const DEFAULT_VALUE = '50% 30%';

const containerRef = ref<HTMLDivElement | null>(null);
const imageLoaded = ref(false);
const isDragging = ref(false);

function parseValue(value?: string | null): { x: number; y: number } {
  if (!value) {
    return { x: 50, y: 30 };
  }

  const parts = value.trim().split(/\s+/);
  if (parts.length !== 2) {
    return { x: 50, y: 30 };
  }

  const x = parseFloat(parts[0].replace('%', ''));
  const y = parseFloat(parts[1].replace('%', ''));

  return {
    x: Number.isFinite(x) ? Math.max(0, Math.min(100, x)) : 50,
    y: Number.isFinite(y) ? Math.max(0, Math.min(100, y)) : 30,
  };
}

const currentX = computed(() => parseValue(props.modelValue).x);
const currentY = computed(() => parseValue(props.modelValue).y);

const displayValue = computed(() => {
  const { x, y } = parseValue(props.modelValue);
  return `${Math.round(x)}% ${Math.round(y)}%`;
});

function handleImageLoad() {
  imageLoaded.value = true;
}

function updatePositionFromEvent(event: MouseEvent) {
  const container = containerRef.value;
  if (!container) return;

  const rect = container.getBoundingClientRect();
  const x = ((event.clientX - rect.left) / rect.width) * 100;
  const y = ((event.clientY - rect.top) / rect.height) * 100;

  const clampedX = Math.max(0, Math.min(100, x));
  const clampedY = Math.max(0, Math.min(100, y));

  emit('update:modelValue', `${Math.round(clampedX)}% ${Math.round(clampedY)}%`);
}

function handleMouseDown(event: MouseEvent) {
  isDragging.value = true;
  updatePositionFromEvent(event);
}

function handleMouseMove(event: MouseEvent) {
  if (!isDragging.value) return;
  updatePositionFromEvent(event);
}

function handleMouseUp() {
  isDragging.value = false;
}

function resetToDefault() {
  emit('update:modelValue', DEFAULT_VALUE);
}
</script>
