<template>
  <Popover v-model:open="open">
    <PopoverAnchor as-child>
      <div ref="anchor" class="flex items-center">
        <slot :toggle="toggleFromClick" :open="open" :hover="{ onPointerenter: onPointerEnter, onPointerleave: onPointerLeave }" />
      </div>
    </PopoverAnchor>
    <PopoverContent
      side="top"
      align="end"
      :side-offset="4"
      class="w-auto p-1 shadow-none"
      data-slot="reaction-picker"
      @pointerenter="cancelClose"
      @pointerleave="onPointerLeave"
      @open-auto-focus="event => openedByHover && event.preventDefault()"
      @close-auto-focus="restoreFocus"
      @interact-outside="keepOpenForAnchor"
    >
      <div class="flex items-center gap-0.5" role="group" :aria-label="$t('discussions.reactions.add')">
        <button
          v-for="(reaction, index) in REACTIONS"
          :key="reaction.emoji"
          type="button"
          :aria-label="$t(reaction.label)"
          :aria-pressed="mine.includes(reaction.emoji)"
          :title="$t(reaction.label)"
          :data-testid="`reaction-option-${reaction.label.split('.').pop()}`"
          :style="{ animationDelay: `${index * 25}ms` }"
          :class="[
            'group/reaction flex size-9 items-center justify-center transition-colors duration-150 pointer-coarse:size-11',
            'motion-safe:animate-in motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-1 motion-safe:fill-mode-backwards',
            mine.includes(reaction.emoji) ? 'bg-brand/10 text-brand' : 'text-muted-foreground hover:bg-accent hover:text-foreground',
          ]"
          @click="choose(reaction.emoji)"
        >
          <!-- Only the icon lifts, so the highlighted square stays centred in the bar. -->
          <component
            :is="reaction.icon"
            :class="[
              'size-[1.15rem] transition-transform duration-150 ease-out',
              'motion-safe:group-hover/reaction:-translate-y-0.5 motion-safe:group-hover/reaction:scale-110 motion-safe:group-active/reaction:scale-90',
              reaction.fillWhenActive && mine.includes(reaction.emoji) ? 'fill-current' : '',
            ]"
          />
        </button>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { onBeforeUnmount, ref, useTemplateRef, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { REACTIONS } from '@/Components/Discussions/reactions';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';

defineProps<{ mine: string[] }>();
const emit = defineEmits<{ select: [emoji: string]; openChange: [open: boolean] }>();

const OPEN_DELAY = 250;
const CLOSE_DELAY = 300;

const open = ref(false);
const openedByHover = ref(false);
const anchor = useTemplateRef<HTMLElement>('anchor');
let timer: ReturnType<typeof setTimeout> | undefined;

watch(open, value => emit('openChange', value));

function cancelClose() {
  clearTimeout(timer);
}

/** A mouse resting on the react button opens the picker; touch and keyboard press it. */
function onPointerEnter(event: PointerEvent) {
  if (event.pointerType !== 'mouse') return;
  clearTimeout(timer);
  if (open.value) return;
  timer = setTimeout(() => {
    openedByHover.value = true;
    open.value = true;
  }, OPEN_DELAY);
}

function onPointerLeave(event: PointerEvent) {
  if (event.pointerType !== 'mouse') return;
  clearTimeout(timer);
  timer = setTimeout(() => {
    open.value = false;
  }, CLOSE_DELAY);
}

function toggleFromClick() {
  clearTimeout(timer);
  openedByHover.value = false;
  open.value = !open.value;
}

// The anchor's own buttons sit outside the popover; clicking them must not dismiss it first.
function keepOpenForAnchor(event: Event) {
  const target = event.target as Node | null;
  if (target && anchor.value?.contains(target)) event.preventDefault();
}

/** Without a registered trigger Reka cannot return focus itself; a hover-opened picker never took it. */
function restoreFocus(event: Event) {
  event.preventDefault();
  if (!openedByHover.value) anchor.value?.querySelector<HTMLElement>('[data-reaction-trigger]')?.focus();
}

function choose(emoji: string) {
  emit('select', emoji);
  open.value = false;
}

onBeforeUnmount(() => clearTimeout(timer));
</script>
