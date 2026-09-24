<template>
  <!-- StartFM Button -->
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="ghost"
        :size="iconOnly ? 'icon' : 'sm'"
        :class="iconOnly ? socialIconButtonClass : undefined"
        :disabled="loading"
        :aria-label="iconOnly ? $t('accessibility.start_fm_toggle') : undefined"
        @click="toggleAudio"
      >
        <template v-if="loading">
          <Spinner class="size-4" :class="[!iconOnly && 'mr-2']" />
        </template>
        <template v-else-if="!isPaused && audioPlaying">
          <IFluentPause24Regular class="size-4" :class="[!iconOnly && 'mr-2']" />
        </template>
        <template v-else>
          <IFluentMusicNote24Regular class="size-4" :class="[!iconOnly && 'mr-2']" />
        </template>
        <slot v-if="!iconOnly" />
        <audio v-show="false" ref="startFM" preload="none" @canplay="onCanPlay" @ended="onEnded" @play="onPlay" @pause="onPause">
          <source src="https://eteris.startfm.lt/startfm.mp3" type="audio/mpeg">
          <source src="https://eteris.startfm.lt/startfm.m4a" type="audio/mp4">
        </audio>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-80">
      <div class="text-sm">
        {{ $t("Klausykis studentiško") }}
        <a class="font-bold transition hover:text-brand" href="https://startfm.lt" target="_blank" rel="noopener noreferrer">START FM</a>
        {{ $t("radijo") }}!
      </div>
    </PopoverContent>
  </Popover>

  <!-- Draggable Audio Player Overlay -->
  <Teleport to="body">
    <div
      v-if="audioPlaying"
      ref="playerRef"
      class="fixed z-50 flex min-w-[240px] select-none items-center gap-3 border border-border bg-popover p-3 text-popover-foreground"
      :class="{ 'cursor-grabbing': isDragging }"
      :style="{
        left: `${playerPosition.x}px`,
        top: `${playerPosition.y}px`,
        transition: isDragging ? 'none' : 'background-color 0.2s'
      }"
    >
      <!-- Drag handle -->
      <div
        class="-ml-2 -my-2 cursor-grab px-2 py-3 transition-colors hover:bg-accent active:cursor-grabbing"
        @mousedown="startDrag"
        @touchstart="startDrag"
      >
        <IFluentReOrderDotsVertical24Regular class="size-4 text-muted-foreground" />
      </div>

      <div class="flex items-center gap-2">
        <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="toggleAudio">
          <IFluentPlay24Regular v-if="isPaused" class="size-4" />
          <IFluentPause24Regular v-else class="size-4" />
        </Button>
        <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="stopAudio">
          <IFluentStop24Regular class="size-4" />
        </Button>
      </div>

      <div class="flex-1 flex items-center gap-2">
        <div class="text-sm font-medium text-foreground">
          START FM
        </div>
        <div class="flex items-center gap-1">
          <Button variant="ghost" size="sm" class="h-6 w-6 p-0" @click="adjustVolume(-0.1)">
            <IFluentSpeaker124Regular class="size-3" />
          </Button>
          <div
            class="h-1.5 w-16 cursor-pointer overflow-hidden bg-secondary"
            @click="setVolumeFromClick"
          >
            <div
              class="h-full bg-brand-fill transition-all duration-200"
              :style="{ width: `${volume * 100}%` }"
            />
          </div>
          <Button variant="ghost" size="sm" class="h-6 w-6 p-0" @click="adjustVolume(0.1)">
            <IFluentSpeaker224Regular class="size-3" />
          </Button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ref, useTemplateRef, watch, onMounted, onUnmounted, nextTick } from 'vue';

import { socialIconButtonClass } from './socialIconButtonClass';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Spinner } from '@/Components/ui/spinner';
import IFluentMusicNote24Regular from '~icons/fluent/music-note-24-regular';
import IFluentPause24Regular from '~icons/fluent/pause-24-regular';
import IFluentPlay24Regular from '~icons/fluent/play-24-regular';
import IFluentReOrderDotsVertical24Regular from '~icons/fluent/re-order-dots-vertical-24-regular';
import IFluentSpeaker124Regular from '~icons/fluent/speaker-1-24-regular';
import IFluentSpeaker224Regular from '~icons/fluent/speaker-2-24-regular';
import IFluentStop24Regular from '~icons/fluent/stop-24-regular';

defineProps<{
  size?: 'sm' | 'default' | 'lg' | 'icon' | null;
  /** Renders the square bordered treatment used in the footer's social row, icon-only. */
  iconOnly?: boolean;
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
