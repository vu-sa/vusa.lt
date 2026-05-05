<template>
  <!-- StartFM Button -->
  <Popover>
    <PopoverTrigger as-child>
      <Button variant="ghost" size="sm" :disabled="loading" @click="toggleAudio">
        <template v-if="loading">
          <Loader2 class="w-4 h-4 mr-2 animate-spin" />
        </template>
        <template v-else-if="!isPaused && audioPlaying">
          <Pause class="w-4 h-4 mr-2" />
        </template>
        <template v-else>
          <Radio class="w-4 h-4 mr-2" />
        </template>
        <slot />
        <audio v-show="false" ref="startFM" preload="none" @canplay="onCanPlay" @ended="onEnded" @play="onPlay" @pause="onPause">
          <source src="https://eteris.startfm.lt/startfm.mp3" type="audio/mpeg">
          <source src="https://eteris.startfm.lt/startfm.m4a" type="audio/mp4">
        </audio>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-80">
      <div class="text-sm">
        {{ $t("Klausykis studentiško") }}
        <a class="font-bold transition hover:text-vusa-red" href="https://startfm.lt" target="_blank">START FM</a>
        {{ $t("radijo") }}!
      </div>
    </PopoverContent>
  </Popover>

  <!-- Draggable Audio Player Overlay -->
  <Teleport to="body">
    <div
      v-if="audioPlaying"
      ref="playerRef"
      class="fixed z-50 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg p-3 flex items-center gap-3 min-w-[240px] select-none"
      :class="{ 'cursor-grabbing': isDragging }"
      :style="{
        left: `${playerPosition.x}px`,
        top: `${playerPosition.y}px`,
        transition: isDragging ? 'none' : 'box-shadow 0.2s'
      }"
    >
      <!-- Drag handle -->
      <div
        class="cursor-grab active:cursor-grabbing px-2 py-3 -ml-2 -my-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-l-lg transition-colors"
        @mousedown="startDrag"
        @touchstart="startDrag"
      >
        <GripVertical class="w-4 h-4 text-zinc-400" />
      </div>

      <div class="flex items-center gap-2">
        <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="toggleAudio">
          <Play v-if="isPaused" class="w-4 h-4" />
          <Pause v-else class="w-4 h-4" />
        </Button>
        <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="stopAudio">
          <Square class="w-4 h-4" />
        </Button>
      </div>

      <div class="flex-1 flex items-center gap-2">
        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
          START FM
        </div>
        <div class="flex items-center gap-1">
          <Button variant="ghost" size="sm" class="h-6 w-6 p-0" @click="adjustVolume(-0.1)">
            <Volume1 class="w-3 h-3" />
          </Button>
          <div
            class="w-16 h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden cursor-pointer"
            @click="setVolumeFromClick"
          >
            <div
              class="h-full bg-vusa-red transition-all duration-200"
              :style="{ width: `${volume * 100}%` }"
            />
          </div>
          <Button variant="ghost" size="sm" class="h-6 w-6 p-0" @click="adjustVolume(0.1)">
            <Volume2 class="w-3 h-3" />
          </Button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ref, useTemplateRef, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Radio, Play, Pause, Square, Volume1, Volume2, GripVertical, Loader2 } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

defineProps<{
  size?: 'sm' | 'default' | 'lg' | 'icon' | null;
}>();

const startFM = useTemplateRef<HTMLAudioElement>('startFM');

const audioPlaying = ref(false);
const loading = ref(false);
const isPaused = ref(true);
const volume = ref(0.35);

// Draggable player position
const playerPosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const playerRef = ref<HTMLElement | null>(null);
const isPositioned = ref(false);

const onCanPlay = () => {
  loading.value = false;
};

const onEnded = () => {
  audioPlaying.value = false;
  isPaused.value = true;
};

const onPlay = () => {
  audioPlaying.value = true;
  isPaused.value = false;
  loading.value = false;
};

const onPause = () => {
  isPaused.value = true;
  // Keep overlay visible when paused - don't set audioPlaying to false
};

const toggleAudio = () => {
  if (!startFM.value) return;

  if (startFM.value.paused) {
    startFM.value.play();

    if (startFM.value.readyState !== 4) {
      loading.value = true;
    }
  }
  else {
    startFM.value.pause();
  }
};

const stopAudio = () => {
  if (!startFM.value) return;

  startFM.value.pause();
  startFM.value.currentTime = 0;
  audioPlaying.value = false;
  isPaused.value = true;
};

const adjustVolume = (delta: number) => {
  if (!startFM.value) return;

  const newVolume = Math.max(0, Math.min(1, volume.value + delta));
  volume.value = newVolume;
  startFM.value.volume = newVolume;
};

const setVolumeFromClick = (event: MouseEvent) => {
  const target = event.currentTarget as HTMLElement;
  const rect = target.getBoundingClientRect();
  const clickX = event.clientX - rect.left;
  const newVolume = Math.max(0, Math.min(1, clickX / rect.width));

  volume.value = newVolume;
  if (startFM.value) {
    startFM.value.volume = newVolume;
  }
};

// Dragging logic
const startDrag = (event: MouseEvent | TouchEvent) => {
  if (!playerRef.value) return;

  isDragging.value = true;
  const rect = playerRef.value.getBoundingClientRect();

  if (event instanceof MouseEvent) {
    dragOffset.value = {
      x: event.clientX - rect.left,
      y: event.clientY - rect.top,
    };
  }
  else if (event.touches.length > 0) {
    dragOffset.value = {
      x: event.touches[0].clientX - rect.left,
      y: event.touches[0].clientY - rect.top,
    };
  }

  event.preventDefault();
};

const onDrag = (event: MouseEvent | TouchEvent) => {
  if (!isDragging.value) return;

  let clientX: number, clientY: number;

  if (event instanceof MouseEvent) {
    clientX = event.clientX;
    clientY = event.clientY;
  }
  else if (event.touches.length > 0) {
    clientX = event.touches[0].clientX;
    clientY = event.touches[0].clientY;
  }
  else {
    return;
  }

  const newX = clientX - dragOffset.value.x;
  const newY = clientY - dragOffset.value.y;

  // Constrain to viewport
  const maxX = window.innerWidth - (playerRef.value?.offsetWidth || 200);
  const maxY = window.innerHeight - (playerRef.value?.offsetHeight || 60);

  playerPosition.value = {
    x: Math.max(0, Math.min(maxX, newX)),
    y: Math.max(0, Math.min(maxY, newY)),
  };

  isPositioned.value = true;
};

const endDrag = () => {
  isDragging.value = false;
};

// Set initial position when player becomes visible
watch(audioPlaying, (playing) => {
  if (playing && !isPositioned.value) {
    // Position at bottom-right by default, with safe margin
    requestAnimationFrame(async () => {
      await nextTick();
      const margin = 24;
      const playerWidth = playerRef.value?.offsetWidth ?? 260;
      const playerHeight = playerRef.value?.offsetHeight ?? 60;
      playerPosition.value = {
        x: Math.max(margin, window.innerWidth - playerWidth - margin),
        y: Math.max(margin, window.innerHeight - playerHeight - margin),
      };
    });
  }
});

onMounted(() => {
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', endDrag);
  document.addEventListener('touchmove', onDrag, { passive: false });
  document.addEventListener('touchend', endDrag);
});

onUnmounted(() => {
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', endDrag);
  document.removeEventListener('touchmove', onDrag);
  document.removeEventListener('touchend', endDrag);
});

// Watch volume changes and apply to audio element
watch(volume, (newVolume) => {
  if (startFM.value) {
    startFM.value.volume = newVolume;
  }
}, { immediate: true });
</script>
