<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined" :title="element.options?.title"
    :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow" :band
    :align="element.options?.align ?? 'start'" :heading-level="element.options?.headingLevel"
    :show-separator="element.options?.showSeparator" inner="wide"
    :editable @update:header="updateOptions"
  >
    <div v-if="steps.length > 0">
      <ol :class="['grid gap-8 sm:gap-10', COLUMN_CLASS[columns]]">
        <li v-for="(step, index) in steps" :key="index" class="relative border-t-2 border-brand pt-5">
          <div v-if="editable && steps.length > 1" class="absolute right-0 top-2 z-10">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-6 text-muted-foreground hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400"
              :title="$t('rich-content.remove_step')"
              data-rc-interactive
              data-rc-step-remove
              @click.stop="removeStep(index)"
            >
              <IFluentDelete24Regular class="size-3.5" />
            </Button>
          </div>

          <!-- The numeral is decoration for the ordered list the markup already is, so it is
               `aria-hidden`: a screen reader announcing "zero one, one, Užpildyk anketą" reads
               the same number twice. -->
          <span class="u-display block text-3xl text-brand" aria-hidden="true">
            {{ String(index + 1).padStart(2, '0') }}
          </span>

          <RCInlineText
            v-if="editable"
            as="h3"
            :model-value="step.title ?? ''"
            :editable
            :placeholder="$t('rich-content.enter_step_title')"
            class="mt-3 text-lg font-bold text-foreground"
            data-rc-step-title
            @update:model-value="updateStep(index, { title: $event })"
          />
          <h3 v-else class="mt-3 text-lg font-bold text-foreground">
            {{ step.title }}
          </h3>

          <RCInlineText
            v-if="editable"
            as="p"
            :model-value="step.text ?? ''"
            :editable
            :placeholder="$t('rich-content.enter_step_text')"
            class="mt-2 text-pretty leading-relaxed text-muted-foreground"
            data-rc-step-text
            @update:model-value="updateStep(index, { text: $event })"
          />
          <p v-else-if="step.text" class="mt-2 text-pretty leading-relaxed text-muted-foreground">
            {{ step.text }}
          </p>
        </li>
      </ol>

      <div v-if="editable" class="mt-8 flex justify-center">
        <RCAddPlaceholder :label="$t('rich-content.add_step')" data-rc-step-add @click="addStep" />
      </div>
    </div>

    <!-- Zero steps empty state in edit mode -->
    <div
      v-else-if="editable"
      class="flex min-h-[200px] flex-col items-center justify-center rounded-2xl border border-dashed border-border p-8 text-center"
      data-rc-step-empty
    >
      <p class="mb-4 text-sm text-muted-foreground">
        {{ $t('rich-content.no_steps') }}
      </p>
      <Button variant="outline" size="sm" @click="addStep">
        <IFluentAdd12Regular class="mr-1 size-3.5" />
        {{ $t('rich-content.add_step') }}
      </Button>
    </div>
  </RCSection>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCSection from '../RCSection.vue';
import type { BandResolution } from '../bandLayout';

import type { ProcessSteps } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';

// Lazy-loaded: only ever mounted while `editable` — a static import would bundle the
// full-screen editor's inline-text/add-placeholder controls into every public page
// that renders a process-steps block, which never reaches these branches at all.
const RCInlineText = defineAsyncComponent(() => import('../Editor/Fullscreen/RCInlineText.vue'));
const RCAddPlaceholder = defineAsyncComponent(() => import('../Editor/Fullscreen/RCAddPlaceholder.vue'));

/**
 * A numbered process — "how you join", "how a request is handled".
 *
 * Each step hangs off a brand rule along its top edge rather than sitting in a card, and the
 * numeral is set in the display face: the sequence is the content, so it is what carries the
 * weight. Renders as a real `<ol>`, which is what it is.
 */
const props = defineProps<{
  element: ProcessSteps;
  anchorId?: number | null;
  band?: BandResolution;
  /** Full-screen editor mode: the optional title/subtitle/eyebrow header becomes
   *  click-to-edit. Undefined/false in every other context. */
  editable?: boolean;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — this block has no per-field claiming, but an
   *  undeclared non-undefined prop would otherwise land on the root as a stray attribute. */
  blockKey?: string;
  /** @see blockKey */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<(e: 'update:element', value: ProcessSteps) => void>();

function updateOptions(patch: { title?: string; subtitle?: string; eyebrow?: string }): void {
  emit('update:element', { ...props.element, options: { ...props.element.options, ...patch } });
}

const COLUMN_CLASS: Record<number, string> = {
  2: 'sm:grid-cols-2',
  3: 'sm:grid-cols-2 lg:grid-cols-3',
  4: 'sm:grid-cols-2 lg:grid-cols-4',
};

const steps = computed(() => props.element.json_content ?? []);

// Coerced once here: the value arrives from a Select, so it can be the string "3".
const columns = computed(() => {
  const n = Number(props.element.options?.columns);

  return n === 2 || n === 4 ? n : 3;
});

function updateStep(index: number, patch: Partial<ProcessSteps['json_content'][number]>): void {
  const newSteps = [...steps.value];
  if (newSteps[index]) {
    newSteps[index] = { ...newSteps[index], ...patch };
    emit('update:element', { ...props.element, json_content: newSteps });
  }
}

function removeStep(index: number): void {
  if (steps.value.length <= 1) {
    return;
  }
  const newSteps = steps.value.filter((_, i) => i !== index);
  emit('update:element', { ...props.element, json_content: newSteps });
}

function addStep(): void {
  emit('update:element', {
    ...props.element,
    json_content: [...steps.value, { title: '', text: '' }],
  });
}
</script>
