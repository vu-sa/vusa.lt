<template>
  <canvas
    v-if="intensity > 0 && reducedMotion !== 'reduce'"
    ref="canvas"
    class="pointer-events-none absolute inset-0 h-full w-full"
    aria-hidden="true"
    data-slot="snow-fall"
  />
</template>

<script setup lang="ts">
import { usePreferredReducedMotion } from '@vueuse/core';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(defineProps<{
  /** 0 none, 1 light, 2 moderate, 3 heavy — as the weather reports it. */
  intensity: number;
  /** Wind in m/s; flakes drift sideways with it. */
  wind?: number;
  /** Share of the flakes to draw: a working page gets fewer than the tree. */
  density?: number;
  /** Start with an empty sky that fills from the top, instead of flakes already all over. */
  fromTop?: boolean;
}>(), {
  wind: 0,
  density: 1,
});

interface Flake {
  x: number;
  y: number;
  radius: number;
  speed: number;
  phase: number;
}

const FLAKES_PER_INTENSITY = [0, 45, 110, 200] as const;

/** Small soft dots gain nothing from a full Retina canvas, which is four times the pixels to clear and composite. */
const MAX_PIXEL_RATIO = 1.5;
/** How often the flake colour is read again (it follows the theme); reading styles every frame forces a recalc. */
const COLOUR_MS = 2000;

const reducedMotion = usePreferredReducedMotion();
const canvas = ref<HTMLCanvasElement | null>(null);
// Read straight from the element each frame: flakes spread before layout would all start in one corner.
const width = { value: 0 };
const height = { value: 0 };

let flakes: Flake[] = [];
let frame: number | null = null;
let lastTime = 0;
let colour = '#ffffff';
let colourReadAt = -Infinity;

/** `scatter` spreads flakes over the sky; `above` staggers them in a band just over it, so they arrive soon but not as one line. */
const spawn = (start: 'scatter' | 'above' | 'edge'): Flake => ({
  x: Math.random() * width.value,
  y: { scatter: Math.random() * height.value, above: -4 - Math.random() * height.value * 0.25, edge: -4 }[start],
  radius: 0.8 + Math.random() * 2,
  speed: 25 + Math.random() * 45,
  phase: Math.random() * Math.PI * 2,
});

const tick = (time: number) => {
  const context = canvas.value?.getContext('2d');
  if (!context || !canvas.value) {
    frame = requestAnimationFrame(tick);
    return;
  }
  const dt = lastTime ? Math.min((time - lastTime) / 1000, 1 / 20) : 0;
  lastTime = time;

  const resized = canvas.value.clientWidth !== width.value || canvas.value.clientHeight !== height.value;
  width.value = canvas.value.clientWidth;
  height.value = canvas.value.clientHeight;
  if (width.value === 0 || height.value === 0) {
    frame = requestAnimationFrame(tick);
    return;
  }
  if (resized) {
    flakes = [];
  }

  const ratio = Math.min(window.devicePixelRatio || 1, MAX_PIXEL_RATIO);
  if (canvas.value.width !== Math.round(width.value * ratio)) {
    canvas.value.width = Math.round(width.value * ratio);
    canvas.value.height = Math.round(height.value * ratio);
  }
  context.setTransform(ratio, 0, 0, ratio, 0, 0);
  context.clearRect(0, 0, width.value, height.value);
  if (time - colourReadAt > COLOUR_MS) {
    colour = getComputedStyle(canvas.value).getPropertyValue('--season-snowflake') || '#ffffff';
    colourReadAt = time;
  }
  context.fillStyle = colour;

  const target = Math.round((FLAKES_PER_INTENSITY[Math.min(3, Math.max(0, Math.round(props.intensity)))] ?? 0) * props.density);
  const start = flakes.length === 0 ? (props.fromTop ? 'above' : 'scatter') : 'edge';
  while (flakes.length < target) {
    flakes.push(spawn(start));
  }
  flakes.length = Math.min(flakes.length, target);

  for (const flake of flakes) {
    // Bigger flakes are nearer: they fall faster and drift further in the wind.
    flake.y += flake.speed * (0.6 + flake.radius * 0.25) * dt;
    flake.x += (props.wind * 6 * (0.5 + flake.radius * 0.3) + Math.sin(time / 900 + flake.phase) * 8) * dt;
    if (flake.y > height.value + 4) {
      Object.assign(flake, spawn('edge'));
    }
    if (flake.x > width.value + 4) {
      flake.x = -4;
    }
    else if (flake.x < -4) {
      flake.x = width.value + 4;
    }
    context.globalAlpha = 0.35 + flake.radius * 0.18;
    context.beginPath();
    context.arc(flake.x, flake.y, flake.radius, 0, Math.PI * 2);
    context.fill();
  }

  frame = requestAnimationFrame(tick);
};

watch(canvas, (element) => {
  if (element && frame === null) {
    frame = requestAnimationFrame(tick);
  }
  if (!element && frame !== null) {
    cancelAnimationFrame(frame);
    frame = null;
    flakes = [];
  }
});

onBeforeUnmount(() => {
  if (frame !== null) {
    cancelAnimationFrame(frame);
  }
});
</script>
