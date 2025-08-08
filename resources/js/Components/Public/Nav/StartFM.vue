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
          <Play class="w-4 h-4 mr-2" />
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

  <!-- Minimalistic Audio Player Overlay -->
  <Teleport to="body">
    <div v-if="audioPlaying" class="fixed bottom-4 right-4 z-50 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg p-3 flex items-center gap-3 min-w-[200px]">
      <div class="flex items-center gap-2">
        <Button variant="ghost" size="sm" @click="toggleAudio">
          <Play v-if="isPaused" class="w-4 h-4" />
          <Pause v-else class="w-4 h-4" />
        </Button>
        <Button variant="ghost" size="sm" @click="stopAudio">
          <Square class="w-4 h-4" />
        </Button>
      </div>
      
      <div class="flex-1 flex items-center gap-2">
        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">START FM</div>
        <div class="flex items-center gap-1">
          <Button variant="ghost" size="sm" @click="adjustVolume(-0.1)">
            <Volume1 class="w-3 h-3" />
          </Button>
          <div class="w-16 h-1 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden cursor-pointer" @click="setVolumeFromClick">
            <div 
              class="h-full bg-vusa-red transition-all duration-200" 
              :style="{ width: `${volume * 100}%` }"
            />
          </div>
          <Button variant="ghost" size="sm" @click="adjustVolume(0.1)">
            <Volume2 class="w-3 h-3" />
          </Button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { ref, useTemplateRef, watch } from "vue";
import { Button } from "@/Components/ui/button";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { Play, Pause, Square, Volume1, Volume2, VolumeX, Loader2 } from "lucide-vue-next";

defineProps<{
  size?: 'sm' | 'default' | 'lg' | 'icon' | null;
}>();

const startFM = useTemplateRef<HTMLAudioElement>('startFM');

const audioPlaying = ref(false);
const loading = ref(false);
const isPaused = ref(true); // Track paused state reactively
const volume = ref(0.7); // Default volume at 70%

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

const changeLoading = () => {
  loading.value = false;
  audioPlaying.value = true;
};

const toggleAudio = () => {
  if (!startFM.value) return;
  
  if (startFM.value.paused) {
    startFM.value.play();
    
    if (startFM.value.readyState !== 4) {
      loading.value = true;
    }
  } else {
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

// Watch volume changes and apply to audio element
watch(volume, (newVolume) => {
  if (startFM.value) {
    startFM.value.volume = newVolume;
  }
}, { immediate: true });
</script>
