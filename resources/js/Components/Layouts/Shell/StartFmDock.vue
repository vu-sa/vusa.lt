<template>
  <aside v-if="isOpen" data-slot="start-fm-dock" class="fixed bottom-4 right-4 z-50 w-[min(24rem,calc(100vw-2rem))] border border-border bg-card p-4 shadow-none">
    <audio ref="audio" preload="none" @play="isPlaying = true" @pause="isPlaying = false" @ended="isPlaying = false">
      <source src="https://eteris.startfm.lt/startfm.mp3" type="audio/mpeg">
      <source src="https://eteris.startfm.lt/startfm.m4a" type="audio/mp4">
      <track kind="captions">
    </audio>
    <div class="flex items-center gap-3">
      <Radio class="size-5 text-brand" />
      <div class="min-w-0 flex-1">
        <p class="font-semibold">START FM</p>
        <p class="text-sm text-muted-foreground">Studentų radijas 94.2</p>
      </div>
      <Button variant="ghost" size="icon" class="u-touch" :aria-label="$t('shell.account.close')" @click="close">
        <X class="size-4" />
      </Button>
    </div>
    <div class="mt-4 flex items-center gap-2">
      <Button variant="brand" class="flex-1" @click="toggle">
        <Pause v-if="isPlaying" class="size-4" />
        <Play v-else class="size-4" />
        {{ $t(isPlaying ? 'shell.account.pause' : 'shell.account.listen') }}
      </Button>
      <a href="https://startfm.lt" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-brand underline-offset-4 hover:underline">startfm.lt</a>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Pause, Play, Radio, X } from 'lucide-vue-next';
import { ref, useTemplateRef } from 'vue';
import { Button } from '@/Components/ui/button';
import { useStartFm } from '@/Composables/useStartFm';

const { isOpen, close } = useStartFm();
const audio = useTemplateRef<HTMLAudioElement>('audio');
const isPlaying = ref(false);
async function toggle(): Promise<void> {
  if (!audio.value) {
    return;
  }
  if (audio.value.paused) {
    await audio.value.play();
  }
  else {
    audio.value.pause();
  }
}
</script>
