<template>
  <!-- Live radio has no caption feed; playback controls are provided below. -->
  <!-- eslint-disable-next-line vuejs-accessibility/media-has-caption -->
  <audio ref="audio" preload="none" @playing="player.onPlaying" @waiting="player.onWaiting" @pause="player.onPause" @error="player.onError" @ended="player.onError" />
  <aside
    v-if="isOpen"
    ref="panel"
    data-slot="start-fm-player"
    data-nosnippet
    :aria-label="$t('radio.player')"
    :class="[
      'grid grid-cols-[2.75rem_minmax(0,1fr)_2.75rem_2.75rem] items-center gap-x-2 border-t border-border bg-popover p-2 text-popover-foreground',
      'md:fixed md:bottom-6 md:right-6 md:z-40 md:w-[25rem] md:grid-cols-[2.75rem_minmax(0,1fr)_auto_2.75rem] md:border',
    ]"
  >
    <Button variant="ghost" voice="sentence" size="icon" class="col-start-1 row-start-2 size-11 md:row-start-1" :aria-label="playLabel" @click="player.toggle">
      <Spinner v-if="status === 'connecting'" class="size-4 motion-reduce:animate-none" />
      <component :is="icons.pause" v-else-if="status === 'playing'" class="size-4" aria-hidden="true" />
      <component :is="icons.retry" v-else-if="status === 'error'" class="size-4" aria-hidden="true" />
      <component :is="icons.play" v-else class="size-4" aria-hidden="true" />
    </Button>

    <div class="col-start-2 row-start-2 min-w-0 md:row-start-1">
      <a href="https://startfm.lt" target="_blank" rel="noopener noreferrer" class="flex min-h-11 flex-col justify-center underline-offset-4 hover:text-brand focus-visible:outline-ring">
        <span class="text-sm font-semibold">START FM</span>
        <span role="status" class="text-xs text-muted-foreground">{{ $t(`radio.${status}`) }}</span>
      </a>
    </div>

    <Button
      variant="ghost" voice="sentence" size="icon"
      class="col-start-3 row-start-2 size-11 md:hidden"
      :aria-label="$t('radio.volume')" :aria-expanded="volumeExpanded" :aria-controls="volumeId"
      @click="volumeExpanded = !volumeExpanded"
    >
      <component :is="muted || volume === 0 ? icons.muted : icons.volume" class="size-4" aria-hidden="true" />
    </Button>

    <div :id="volumeId" :class="[
      volumeExpanded ? 'flex' : 'hidden',
      'col-span-4 col-start-1 row-start-1 items-center justify-end gap-2 border-b border-border pb-2',
      'md:col-span-1 md:col-start-3 md:flex md:border-0 md:p-0',
    ]">
      <Button variant="ghost" voice="sentence" size="icon" class="size-11" :aria-label="$t(muted ? 'radio.unmute' : 'radio.mute')" :aria-pressed="muted" @click="player.toggleMute">
        <component :is="muted || volume === 0 ? icons.muted : icons.volume" class="size-4" aria-hidden="true" />
      </Button>
      <input
        v-if="volumeSupported"
        type="range" min="0" max="1" step="0.05" :value="volume"
        :aria-label="$t('radio.volume')" :aria-valuetext="`${Math.round(volume * 100)}%`"
        class="h-11 w-28 cursor-pointer accent-brand md:w-20"
        @input="player.setVolume(Number(($event.target as HTMLInputElement).value))"
      >
      <p v-else class="max-w-44 text-xs text-muted-foreground">
        {{ $t('radio.device_volume') }}
      </p>
    </div>

    <Button variant="ghost" voice="sentence" size="icon" class="col-start-4 row-start-2 size-11 md:row-start-1" :aria-label="$t('radio.close')" @click="player.close">
      <component :is="icons.close" class="size-4" aria-hidden="true" />
    </Button>
  </aside>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useId, useTemplateRef, watch } from 'vue';
import { useElementSize, useMediaQuery } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Pause, Play, RotateCcw, Volume2, VolumeX, X } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { useStartFm } from '@/Composables/useStartFm';
import IFluentPause from '~icons/fluent/pause-24-regular';
import IFluentPlay from '~icons/fluent/play-24-regular';
import IFluentRetry from '~icons/fluent/arrow-clockwise-24-regular';
import IFluentVolume from '~icons/fluent/speaker-2-24-regular';
import IFluentMuted from '~icons/fluent/speaker-off-24-regular';
import IFluentClose from '~icons/fluent/dismiss-24-regular';

const props = withDefaults(defineProps<{ surface?: 'public' | 'admin' }>(), { surface: 'public' });
const player = useStartFm();
const { isOpen, status, volume, muted, volumeSupported } = player;
const audio = useTemplateRef<HTMLAudioElement>('audio');
const panel = useTemplateRef<HTMLElement>('panel');
const volumeExpanded = ref(false);
const volumeId = `start-fm-volume-${useId()}`;
const desktop = useMediaQuery('(min-width: 768px)');
const { height } = useElementSize(panel, undefined, { box: 'border-box' });
const icons = computed(() => props.surface === 'admin'
  ? { play: Play, pause: Pause, retry: RotateCcw, volume: Volume2, muted: VolumeX, close: X }
  : { play: IFluentPlay, pause: IFluentPause, retry: IFluentRetry, volume: IFluentVolume, muted: IFluentMuted, close: IFluentClose });
const playLabel = computed(() => $t(status.value === 'error' ? 'radio.retry' : ['playing', 'connecting'].includes(status.value) ? 'radio.pause' : 'radio.listen'));

watch([height, desktop, isOpen], () => {
  player.desktopHeight.value = desktop.value && isOpen.value ? height.value + 24 : 0;
});
watch(isOpen, (open) => {
  if (!open) volumeExpanded.value = false;
});
onMounted(() => player.attachAudio(audio.value));
onBeforeUnmount(() => {
  player.attachAudio(null);
  player.desktopHeight.value = 0;
});
</script>
