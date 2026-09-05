<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined" :title="element.options?.title" :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow" :band
    :align="element.options?.align ?? 'center'" :heading-level="element.options?.headingLevel"
    :show-separator="element.options?.showSeparator" inner="wide"
    :editable @update:header="updateOptions"
  >
    <div class="relative max-w-lg mx-auto">
      <!-- Stack of Cards -->
      <div ref="stackRef" class="relative h-80 perspective-1000">
        <RCAddPlaceholder v-if="editable" :label="$t('rich-content.add_card')" class="right-0 top-1/2 -translate-y-1/2 translate-x-full" @click="addCard" />
        <div
          v-for="(card, index) in element.json_content"
          :key="index"
          class="group/card absolute inset-0 cursor-pointer transition-all duration-700 ease-in-out transform-gpu"
          :data-rc-interactive="editable ? '' : undefined"
          :style="getCardStyle(index)"
          @click="onCardClick(index)"
        >
          <!-- Fully opaque (`bg-card`, not a tint) — a translucent fill lets the cards
               stacked underneath show through
               the front card wherever they peek out past its edges. -->
          <div class="relative flex h-full flex-col p-6 md:p-8 bg-card border border-border hover:border-brand rounded-xl transition-colors duration-300">
            <button v-if="editable && index === currentCardIndex && element.json_content.length > 1" type="button"
              :class="[
                'absolute right-3 top-3 z-10 flex size-7 shrink-0 items-center justify-center rounded-md text-muted-foreground',
                'opacity-0 transition-opacity group-hover/card:opacity-100',
                'hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400',
              ]"
              data-rc-interactive
              data-rc-card-stack-remove-item
              :aria-label="$t('rich-content.remove_card')"
              :title="$t('rich-content.remove_card')"
              @click.stop="removeCard(index)"
            >
              <IFluentDelete24Regular class="size-3.5" />
            </button>
            <div v-if="card.icon" class="mb-4 flex size-12 shrink-0 items-center justify-center border border-border md:mb-6">
              <RCIcon :name="card.icon" class="size-6 text-brand" />
            </div>
            <!-- No icon: the text group fills the remaining height and centers within
                 it instead of sitting bunched at the top of a tall, mostly-empty card. -->
            <div :class="['flex flex-col', !card.icon && 'flex-1 justify-center']">
              <h3 class="text-xl sm:text-xl font-bold mb-3 md:mb-4 text-foreground">
                <RCInlineText v-if="editable" as="span" :model-value="card.title" :editable :placeholder="$t('rich-content.enter_title')" @click.stop @update:model-value="updateCard(index, { ...card, title: $event })" />
                <template v-else>{{ card.title }}</template>
              </h3>
              <p class="text-[14.5px] sm:text-base text-muted-foreground leading-relaxed">
                <RCInlineText v-if="editable" as="span" :model-value="card.description" :editable :placeholder="$t('rich-content.enter_description')" @click.stop @update:model-value="updateCard(index, { ...card, description: $event })" />
                <template v-else>{{ card.description }}</template>
              </p>
            </div>
          </div>
        </div>
      </div>

      <Popover v-if="editable" :open="!!hotspots?.isPopoverOpen(cardHotspotId)" @update:open="onCardPopoverOpenChange">
        <PopoverAnchor :reference="stackRef ?? undefined" />
        <PopoverContent
          v-if="hotspots?.isPopoverOpen(cardHotspotId) && currentCard"
          data-surface="public"
          class="w-64"
          @close-auto-focus.prevent
        >
          <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between gap-2">
              <Button type="button" variant="outline" size="icon-sm" :disabled="element.json_content.length <= 1" :aria-label="$t('rich-content.previous_card')" @click="switchCard(-1)">
                <IFluentChevronLeft24Regular class="size-4" />
              </Button>
              <span class="text-sm text-muted-foreground">{{ currentCardIndex + 1 }} / {{ element.json_content.length }}</span>
              <Button type="button" variant="outline" size="icon-sm" :disabled="element.json_content.length <= 1" :aria-label="$t('rich-content.next_card')" @click="switchCard(1)">
                <IFluentChevronRight24Regular class="size-4" />
              </Button>
            </div>
            <Field>
              <FieldLabel>{{ $t('rich-content.icon') }}</FieldLabel>
              <RCIconSelect allow-none :model-value="currentCard.icon" @update:model-value="updateCard(currentCardIndex, { ...currentCard, icon: $event })" />
            </Field>
          </div>
        </PopoverContent>
      </Popover>

      <!-- Navigation Indicators -->
      <div class="flex justify-center mt-8 space-x-2">
        <button
          v-for="(card, index) in element.json_content"
          :key="index"
          class="h-1 w-4 transition-all duration-300"
          :class="index === currentCardIndex ? 'w-8 bg-brand-fill' : 'bg-border hover:bg-muted-foreground'"
          @click="handleIndicatorClick(index)"
        />
      </div>

      <!-- Control Hint -->
      <div v-if="element.options?.hintText" class="text-center mt-4">
        <p class="text-sm text-muted-foreground">
          {{ element.options.hintText }}
        </p>
      </div>
    </div>
  </RCSection>
</template>

<script setup lang="ts">
import { computed, inject, onMounted, onUnmounted, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCSection from '../RCSection.vue';
import RCIcon from '../RCIcon.vue';
import RCIconSelect from '../RCIconSelect.vue';
import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import RCAddPlaceholder from '../Editor/Fullscreen/RCAddPlaceholder.vue';
import { ACTIVE_HOTSPOT_KEY } from '../Editor/Fullscreen/useActiveHotspot';
import { asBoolean } from '../booleanish';
import type { BandResolution } from '../bandLayout';

import type { CardStack } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentChevronLeft24Regular from '~icons/fluent/chevron-left-24-regular';
import IFluentChevronRight24Regular from '~icons/fluent/chevron-right-24-regular';

const props = defineProps<{
  element: CardStack;
  anchorId?: number | null;
  band?: BandResolution;
  /** Full-screen editor mode: the optional title/subtitle/eyebrow header becomes
   *  click-to-edit, and every card's title/description becomes click-to-edit plain
   *  text (RCInlineText — neither field is a Tiptap doc). Clicking anywhere else on
   *  the card (like pressing Hero's image hotspot button) brings it to the front and
   *  opens a popover with its icon picker and prev/next controls to switch cards
   *  without hunting for a peeking background card to click. Undefined/false in
   *  every other context (public rendering, forms-mode preview, the block picker). */
  editable?: boolean;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — RCInlineText fields here need no hotspot claim
   *  (unlike a mounted TiptapEditor, several can be live at once); the icon popover
   *  uses the injected hotspot state directly (like Hero's image popover), not this
   *  prop's `activeInlineField` contract. */
  blockKey?: string;
  /** @see blockKey */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<(e: 'update:element', value: CardStack) => void>();

// CardStackDisplay renders both publicly and inside the full-screen editor, so —
// unlike a leaf hotspot that only ever mounts when editable — this injection must
// tolerate having no provider (public rendering) rather than throwing.
const hotspots = inject(ACTIVE_HOTSPOT_KEY, undefined);
const stackRef = ref<HTMLElement | null>(null);
const cardHotspotId = computed(() => `${props.blockKey ?? ''}:card`);

function updateOptions(patch: { title?: string; subtitle?: string; eyebrow?: string }): void {
  emit('update:element', { ...props.element, options: { ...props.element.options, ...patch } });
}

type CardStackItem = CardStack['json_content'][number];

function updateCard(index: number, value: CardStackItem): void {
  const next = [...props.element.json_content];
  next[index] = value;
  emit('update:element', { ...props.element, json_content: next });
}

function removeCard(index: number): void {
  emit('update:element', { ...props.element, json_content: props.element.json_content.filter((_, cardIndex) => cardIndex !== index) });
  if (currentCardIndex.value >= index && currentCardIndex.value > 0) currentCardIndex.value -= 1;
}

function addCard(): void {
  const newCard: CardStackItem = { icon: '', title: '', description: '' };
  emit('update:element', { ...props.element, json_content: [...props.element.json_content, newCard] });
  currentCardIndex.value = props.element.json_content.length;
}

// Card stack state
const currentCardIndex = ref(0);
const isRotating = ref(false);
let autoplayInterval: NodeJS.Timeout | null = null;

const currentCard = computed(() => props.element.json_content[currentCardIndex.value]);

// Function to get card styles for stack effect
const getCardStyle = (index: number) => {
  const totalCards = props.element.json_content.length;
  if (totalCards === 0) return { zIndex: 0, transform: '', opacity: 0, transformOrigin: 'center center' };
  const relativeIndex = (index - currentCardIndex.value + totalCards) % totalCards;

  // Stack configuration
  const baseZIndex = 10;
  const rotateStep = 4; // degrees
  const translateStep = 8; // pixels
  const scaleStep = 0.05;
  const opacityStep = 0.15;

  const zIndex = baseZIndex - relativeIndex;
  const rotate = relativeIndex * rotateStep;
  const translateY = relativeIndex * translateStep;
  const scale = 1 - (relativeIndex * scaleStep);
  const opacity = 1 - (relativeIndex * opacityStep);

  return {
    zIndex,
    transform: `
      translateY(${translateY}px)
      scale(${scale})
      rotateZ(${rotate}deg)
    `,
    opacity: Math.max(0.3, opacity),
    transformOrigin: 'center center',
  };
};

// Function to rotate cards (move current to back)
const rotateCards = () => {
  if (isRotating.value || props.element.json_content.length === 0) return;

  isRotating.value = true;
  currentCardIndex.value = (currentCardIndex.value + 1) % props.element.json_content.length;

  setTimeout(() => {
    isRotating.value = false;
  }, 700); // Match transition duration
};

// Function to set specific card as current
const setCurrentCard = (index: number) => {
  if (isRotating.value || index === currentCardIndex.value) return;

  isRotating.value = true;
  currentCardIndex.value = index;

  setTimeout(() => {
    isRotating.value = false;
  }, 700);
};

// Autoplay functionality — never runs while editable: an author mid-edit should not
// have the stack rotate the card they're typing into out from under them.
const startAutoplay = () => {
  if (props.editable || !asBoolean(props.element.options?.autoplay) || autoplayInterval) return;

  autoplayInterval = setInterval(() => {
    if (!isRotating.value) {
      rotateCards();
    }
  }, props.element.options?.autoplayDelay || 5000);
};

const stopAutoplay = () => {
  if (autoplayInterval) {
    clearInterval(autoplayInterval);
    autoplayInterval = null;
  }
};

const restartAutoplay = () => {
  stopAutoplay();
  if (!props.editable && asBoolean(props.element.options?.autoplay)) {
    startAutoplay();
  }
};

// Handle user interactions - pause autoplay temporarily
const handleUserInteraction = (callback: () => void) => {
  stopAutoplay();
  callback();

  // Restart autoplay after user interaction
  if (!props.editable && asBoolean(props.element.options?.autoplay)) {
    setTimeout(startAutoplay, props.element.options?.autoplayDelay || 5000);
  }
};

// Override click handlers to include autoplay management
const handleCardClick = () => {
  handleUserInteraction(rotateCards);
};

const handleIndicatorClick = (index: number) => {
  handleUserInteraction(() => setCurrentCard(index));
};

// Editable mode: clicking a card (like pressing Hero's image hotspot button) brings
// it to the front and opens its icon popover, instead of rotating the stack.
function onCardClick(index: number): void {
  if (!props.editable) {
    handleCardClick();
    return;
  }
  setCurrentCard(index);
  hotspots?.openPopover(cardHotspotId.value);
}

function onCardPopoverOpenChange(open: boolean): void {
  if (open) hotspots?.openPopover(cardHotspotId.value);
  else hotspots?.close(cardHotspotId.value);
}

// Prev/next controls inside the popover — lets an author browse every card without
// hunting for a background card's thin peeking edge to click.
function switchCard(direction: 1 | -1): void {
  const total = props.element.json_content.length;
  if (total < 2) return;
  setCurrentCard((currentCardIndex.value + direction + total) % total);
}

// Lifecycle
onMounted(() => {
  if (!props.editable && asBoolean(props.element.options?.autoplay)) {
    startAutoplay();
  }
});

onUnmounted(() => {
  stopAutoplay();
});
</script>

<style scoped>
.perspective-1000 {
  perspective: 1000px;
}

.transform-gpu {
  transform-style: preserve-3d;
}
</style>
