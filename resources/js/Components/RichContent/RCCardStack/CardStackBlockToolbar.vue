<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @update:content="$emit('update:content', $event)"
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <!-- Card management -->
      <div class="flex items-center justify-between border-b border-border pb-2.5">
        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ $t('rich-content.cards') }} ({{ cards.length }})
        </span>
        <Button variant="outline" size="sm" data-rc-toolbar-add-card @click="addCard">
          <IFluentAdd12Regular class="mr-1 size-3.5" />
          {{ $t('rich-content.add_card') }}
        </Button>
      </div>

      <!-- Card items list -->
      <div v-if="cards.length > 0" class="flex flex-col gap-1.5 max-h-48 overflow-y-auto pr-0.5">
        <div
          v-for="(card, index) in cards"
          :key="index"
          class="flex items-center justify-between gap-2 rounded-md border border-border bg-muted/40 p-1.5 text-xs"
        >
          <div class="flex items-center gap-2 min-w-0 flex-1">
            <button
              type="button"
              class="relative size-8 shrink-0 overflow-hidden rounded border border-border bg-muted flex items-center justify-center hover:opacity-80 transition-opacity"
              :title="$t('rich-content.icon')"
              @click="activeCardIndexForIcon = index"
            >
              <RCIcon v-if="card.icon" :name="card.icon" class="size-4 text-brand" />
              <IFluentStack24Regular v-else class="size-4 text-muted-foreground" />
            </button>
            <div class="min-w-0 flex-1">
              <p class="truncate font-medium text-foreground">
                {{ card.title || `${$t('rich-content.cards')} ${index + 1}` }}
              </p>
              <p v-if="card.description" class="truncate text-xs text-muted-foreground">
                {{ card.description }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1 shrink-0">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :disabled="index === 0"
              :title="$t('rich-content.move_up')"
              @click="moveCard(index, index - 1)"
            >
              <IFluentArrowUp24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :disabled="index === cards.length - 1"
              :title="$t('rich-content.move_down')"
              @click="moveCard(index, index + 1)"
            >
              <IFluentArrowDown24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :title="$t('rich-content.icon')"
              @click="activeCardIndexForIcon = index"
            >
              <IFluentStar24Regular class="size-3.5" />
            </Button>
            <Button
              v-if="cards.length > 1"
              type="button"
              variant="ghost"
              size="icon"
              class="size-7 text-muted-foreground hover:text-destructive"
              :title="$t('rich-content.remove_card')"
              @click="removeCard(index)"
            >
              <IFluentDelete24Regular class="size-3.5" />
            </Button>
          </div>
        </div>
      </div>

      <!-- Card Stack Options -->
      <div class="space-y-2 pt-1 border-t border-border">
        <div class="flex items-center justify-between">
          <span class="text-xs text-muted-foreground">{{ $t('rich-content.enable_autoplay') }}</span>
          <Switch :model-value="asBoolean(stackOptions.autoplay)" @update:model-value="setOption('autoplay', $event)" />
        </div>
        <div v-if="asBoolean(stackOptions.autoplay)" class="flex items-center justify-between">
          <span class="text-xs text-muted-foreground">{{ $t('rich-content.autoplay_delay') }}</span>
          <Input
            :model-value="stackOptions.autoplayDelay ?? 5000"
            type="number"
            min="2000"
            max="30000"
            step="1000"
            class="h-7 w-20 text-xs"
            @update:model-value="setOption('autoplayDelay', Number($event))"
          />
        </div>
        <Field>
          <FieldLabel class="text-xs">
            {{ $t('rich-content.control_hint') }}
          </FieldLabel>
          <Input
            :model-value="stackOptions.hintText ?? ''"
            type="text"
            class="h-7 text-xs"
            :placeholder="$t('rich-content.enter_control_hint')"
            @update:model-value="setOption('hintText', $event)"
          />
        </Field>
      </div>

      <!-- Width picker -->
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between border-t border-border pt-3">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <!-- Section Options -->
      <RCSectionToolbarOptions v-model="sectionOptions" :presentation-disabled />
    </div>

    <!-- Icon selection dialog -->
    <Dialog :open="activeCardIndexForIcon !== null" @update:open="(open) => { if (!open) activeCardIndexForIcon = null; }">
      <DialogContent class="max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.icon') }}</DialogTitle>
        </DialogHeader>
        <div v-if="activeCardIndexForIcon !== null && cards[activeCardIndexForIcon]" class="py-2">
          <RCIconSelect
            :model-value="cards[activeCardIndexForIcon].icon"
            allow-none
            @update:model-value="updateCardIcon"
          />
        </div>
      </DialogContent>
    </Dialog>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { asBoolean } from '../booleanish';
import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCSectionToolbarOptions from '../Editor/RCSectionToolbarOptions.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import RCIcon from '../RCIcon.vue';
import RCIconSelect from '../RCIconSelect.vue';

import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import type { CardStack, SectionOptions } from '@/Types/contentParts';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentStack24Regular from '~icons/fluent/stack24-regular';
import IFluentStar24Regular from '~icons/fluent/star24-regular';

type CardItem = CardStack['json_content'][number];

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
  presentationDisabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const cards = computed<CardItem[]>(() => (Array.isArray(props.content.json_content) ? props.content.json_content : []));
const stackOptions = computed<CardStack['options']>(() => (props.content.options ?? {}) as CardStack['options']);
const activeCardIndexForIcon = ref<number | null>(null);

const contentType = computed(() => getContentType('card-stack'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (stackOptions.value?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

const sectionOptions = computed<SectionOptions>({
  get: () => (props.content.options ?? {}) as SectionOptions,
  set: val => emit('update:content', { ...props.content, options: val }),
});

function setOption(key: keyof CardStack['options'], value: unknown): void {
  emit('update:content', {
    ...props.content,
    options: {
      ...stackOptions.value,
      [key]: value,
    },
  });
}

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

function updateCardIcon(icon: string): void {
  if (activeCardIndexForIcon.value === null) return;
  const newCards = [...cards.value];
  newCards[activeCardIndexForIcon.value] = {
    ...newCards[activeCardIndexForIcon.value],
    icon,
  };
  emit('update:content', {
    ...props.content,
    json_content: newCards,
  });
  activeCardIndexForIcon.value = null;
}

function removeCard(index: number): void {
  if (cards.value.length <= 1) return;
  const newCards = cards.value.filter((_, i) => i !== index);
  emit('update:content', {
    ...props.content,
    json_content: newCards,
  });
}

function moveCard(from: number, to: number): void {
  const newCards = [...cards.value];
  const [card] = newCards.splice(from, 1);
  if (!card) return;
  newCards.splice(to, 0, card);
  emit('update:content', {
    ...props.content,
    json_content: newCards,
  });
}

function addCard(): void {
  const newCard: CardItem = {
    icon: '',
    title: '',
    description: '',
  };

  emit('update:content', {
    ...props.content,
    json_content: [...cards.value, newCard],
  });
}
</script>
