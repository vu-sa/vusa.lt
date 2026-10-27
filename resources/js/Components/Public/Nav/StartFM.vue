<template>
  <Button
    voice="sentence"
    variant="ghost"
    :size="iconOnly ? 'icon' : (size ?? 'sm')"
    :class="iconOnly ? socialIconButtonClass : undefined"
    :aria-label="$t('radio.listen')"
    @click="player.open"
  >
    <IFluentMusicNote24Regular class="size-4" aria-hidden="true" />
    <slot v-if="!iconOnly" />
  </Button>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { socialIconButtonClass } from './socialIconButtonClass';

import { Button } from '@/Components/ui/button';
import { useStartFm } from '@/Composables/useStartFm';
import IFluentMusicNote24Regular from '~icons/fluent/music-note-24-regular';

defineProps<{ size?: 'sm' | 'default' | 'lg' | 'icon' | null; iconOnly?: boolean }>();
const player = useStartFm();
</script>
