<template>
  <div class="relative">
    <p v-if="variant === 'banner'" class="absolute bottom-full left-0 mb-2 text-xs text-zinc-500 dark:text-zinc-400">
      {{ $t('rich-content.hero_banner_buttons_hint') }}
    </p>
    <div
      :class="[
        'relative flex flex-col items-start gap-3 sm:flex-row sm:flex-wrap sm:items-center 2xl:gap-4',
        buttons?.length ? props.class : undefined,
      ]"
    >
      <HeroButtonHotspot
        v-for="(button, index) in buttons ?? []" :key="index"
        :button :index :block-key="blockKey"
        @update:button="updateButton(index, $event)"
        @remove="removeButton(index)"
      />
      <RCAddPlaceholder
        v-if="(buttons?.length ?? 0) < MAX_BUTTONS"
        :label="$t('rich-content.add_button')"
        :class="buttons?.length ? 'right-0 top-1/2 -translate-y-1/2' : 'left-0 top-0'"
        @click="addButton"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Editable-mode replacement for `HeroButtons.vue`: every button is a `HeroButtonHotspot`
 * (not just the first, even on `banner` — see the hint text below), plus an overlaid
 * add affordance that never changes the published row's alignment or height.
 */
import { nextTick } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import HeroButtonHotspot from './HeroButtonHotspot.vue';
import RCAddPlaceholder from '../Editor/Fullscreen/RCAddPlaceholder.vue';
import { injectActiveHotspot } from '../Editor/Fullscreen/useActiveHotspot';
import type { Hero } from '@/Types/contentParts';

type HeroButton = NonNullable<Hero['json_content']['buttons']>[number];

// Matches `HeroForm.vue`'s `DynamicListInput` `:max="2"` — the regular form and the
// hotspot editor must agree on the same cap, or one surface would let an author create a
// row the other can't add.
const MAX_BUTTONS = 2;

const props = defineProps<{
  buttons?: HeroButton[];
  blockKey: string;
  variant?: Hero['options']['variant'];
  class?: string;
}>();

const emit = defineEmits<{
  (e: 'update:buttons', value: HeroButton[]): void;
}>();

const hotspots = injectActiveHotspot();

function updateButton(index: number, value: HeroButton): void {
  const next = [...(props.buttons ?? [])];
  next[index] = value;
  emit('update:buttons', next);
}

function removeButton(index: number): void {
  emit('update:buttons', (props.buttons ?? []).filter((_, buttonIndex) => buttonIndex !== index));
}

async function addButton(): Promise<void> {
  if ((props.buttons?.length ?? 0) >= MAX_BUTTONS) return;

  const next = [...(props.buttons ?? []), { text: '', link: '', variant: 'default' as const }];
  emit('update:buttons', next);
  await nextTick();
  // reka-ui's Popover moves focus into its content on open (we don't prevent
  // `open-auto-focus`), so this also satisfies "focuses its text input" for free.
  hotspots.openPopover(`${props.blockKey}:buttons:${next.length - 1}`);
}
</script>
