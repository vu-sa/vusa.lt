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
      <!-- Steps management -->
      <div class="flex items-center justify-between border-b border-border pb-2.5">
        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ $t('rich-content.steps') }} ({{ steps.length }})
        </span>
        <Button variant="outline" size="sm" data-rc-toolbar-add-step @click="addStep">
          <IFluentAdd12Regular class="mr-1 size-3.5" />
          {{ $t('rich-content.add_step') }}
        </Button>
      </div>

      <!-- Steps items list -->
      <div v-if="steps.length > 0" class="flex flex-col gap-1.5 max-h-48 overflow-y-auto pr-0.5">
        <div
          v-for="(step, index) in steps"
          :key="index"
          class="flex items-center justify-between gap-2 rounded-md border border-border bg-muted/40 p-1.5 text-xs"
        >
          <div class="flex items-center gap-2 min-w-0 flex-1">
            <span class="flex size-7 shrink-0 items-center justify-center rounded border border-border bg-muted font-bold text-brand">
              {{ String(index + 1).padStart(2, '0') }}
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate font-medium text-foreground">
                {{ step.title || `${$t('rich-content.steps')} ${index + 1}` }}
              </p>
              <p v-if="step.text" class="truncate text-xs text-muted-foreground">
                {{ step.text }}
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
              @click="moveStep(index, index - 1)"
            >
              <IFluentArrowUp24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :disabled="index === steps.length - 1"
              :title="$t('rich-content.move_down')"
              @click="moveStep(index, index + 1)"
            >
              <IFluentArrowDown24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :title="$t('rich-content.step_title')"
              @click="editingStepIndex = index"
            >
              <IFluentEdit24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7 text-muted-foreground hover:text-destructive"
              :disabled="steps.length <= 1"
              :title="$t('rich-content.remove_step')"
              @click="removeStep(index)"
            >
              <IFluentDelete24Regular class="size-3.5" />
            </Button>
          </div>
        </div>
      </div>

      <!-- Columns selector -->
      <div class="flex items-center justify-between border-t border-border pt-3">
        <FieldLabel>{{ $t('rich-content.steps_columns') }}</FieldLabel>
        <ToggleGroup
          :model-value="String(columns)"
          type="single"
          class="justify-start"
          @update:model-value="(v) => v && setColumns(Number(v))"
        >
          <ToggleGroupItem value="2">
            2
          </ToggleGroupItem>
          <ToggleGroupItem value="3">
            3
          </ToggleGroupItem>
          <ToggleGroupItem value="4">
            4
          </ToggleGroupItem>
        </ToggleGroup>
      </div>

      <!-- Width picker -->
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between border-t border-border pt-3">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <!-- Section Options -->
      <RCSectionToolbarOptions v-model="sectionOptions" :presentation-disabled />
    </div>

    <!-- Step Edit Dialog -->
    <Dialog :open="editingStepIndex !== null" @update:open="(val) => { if (!val) editingStepIndex = null; }">
      <DialogContent v-if="editingStepIndex !== null && steps[editingStepIndex]">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.step_title') }}</DialogTitle>
        </DialogHeader>
        <div class="flex flex-col gap-4 py-2">
          <Field>
            <FieldLabel>{{ $t('rich-content.step_title') }}</FieldLabel>
            <Input
              :model-value="steps[editingStepIndex].title"
              type="text"
              :placeholder="$t('rich-content.enter_step_title')"
              @update:model-value="updateEditingStep({ title: $event })"
            />
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.step_text') }}</FieldLabel>
            <Textarea
              :model-value="steps[editingStepIndex].text"
              :rows="3"
              :placeholder="$t('rich-content.enter_step_text')"
              @update:model-value="updateEditingStep({ text: $event })"
            />
          </Field>
        </div>
      </DialogContent>
    </Dialog>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCSectionToolbarOptions from '../Editor/RCSectionToolbarOptions.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';

import type { ProcessSteps, SectionOptions } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentEdit24Regular from '~icons/fluent/edit24-regular';

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

const steps = computed<ProcessSteps['json_content']>(() => (props.content.json_content as ProcessSteps['json_content']) ?? []);

const sectionOptions = computed<SectionOptions>({
  get: () => (props.content.options ?? {}) as SectionOptions,
  set: val => emit('update:content', { ...props.content, options: { ...(props.content.options ?? {}), ...val } }),
});

const columns = computed(() => {
  const n = Number(props.content.options?.columns);
  return n === 2 || n === 4 ? n : 3;
});

function setColumns(col: number): void {
  emit('update:content', {
    ...props.content,
    options: {
      ...(props.content.options ?? {}),
      columns: col as 2 | 3 | 4,
    },
  });
}

const contentType = computed(() => getContentType('process-steps'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (props.content.options?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

const editingStepIndex = ref<number | null>(null);

function addStep(): void {
  const newSteps = [...steps.value, { title: '', text: '' }];
  emit('update:content', { ...props.content, json_content: newSteps });
  editingStepIndex.value = newSteps.length - 1;
}

function removeStep(index: number): void {
  if (steps.value.length <= 1) {
    return;
  }
  const newSteps = steps.value.filter((_, i) => i !== index);
  emit('update:content', { ...props.content, json_content: newSteps });
  if (editingStepIndex.value === index) {
    editingStepIndex.value = null;
  }
  else if (editingStepIndex.value !== null && editingStepIndex.value > index) {
    editingStepIndex.value -= 1;
  }
}

function moveStep(from: number, to: number): void {
  const newSteps = [...steps.value];
  if (to < 0 || to >= newSteps.length) {
    return;
  }
  const [moved] = newSteps.splice(from, 1);
  if (!moved) {
    return;
  }
  newSteps.splice(to, 0, moved);
  emit('update:content', { ...props.content, json_content: newSteps });
  if (editingStepIndex.value === from) {
    editingStepIndex.value = to;
  }
  else if (editingStepIndex.value === to) {
    editingStepIndex.value = from;
  }
}

function updateEditingStep(patch: Partial<ProcessSteps['json_content'][number]>): void {
  if (editingStepIndex.value === null) {
    return;
  }
  const newSteps = [...steps.value];
  if (!newSteps[editingStepIndex.value]) {
    return;
  }
  newSteps[editingStepIndex.value] = { ...newSteps[editingStepIndex.value], ...patch };
  emit('update:content', { ...props.content, json_content: newSteps });
}
</script>
