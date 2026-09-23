<template>
  <div class="space-y-3" data-ordered-list-input>
    <!-- List of existing items -->
    <ol v-if="modelValue.length > 0" class="flex flex-col gap-2">
      <li
        v-for="(item, index) in modelValue"
        :key="index"
        :class="[
          'flex items-start gap-3 border border-border bg-secondary/50 p-3 text-sm text-foreground transition-colors',
          'focus-within:bg-background focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20',
        ]"
      >
        <span
          class="mt-0.5 flex size-5 shrink-0 items-center justify-center bg-brand text-[11px] font-bold text-brand-foreground select-none"
          aria-hidden="true"
        >
          {{ index + 1 }}
        </span>

        <textarea
          v-if="inputType === 'textarea'"
          :value="item"
          rows="1"
          class="min-h-[1.5rem] w-full resize-y bg-transparent p-0 text-sm leading-relaxed text-foreground outline-none placeholder:text-muted-foreground focus:ring-0"
          :placeholder="placeholder?.replace('{n}', String(index + 1)) || `${$t('Punktas')} ${index + 1}...`"
          @input="updateItem(index, ($event.target as HTMLTextAreaElement).value)"
          @keydown.enter.prevent="focusDraftOrNext(index)"
          @keydown.backspace="handleBackspace(index, item)"
        />
        <input
          v-else
          type="text"
          :value="item"
          class="w-full bg-transparent p-0 text-sm leading-relaxed text-foreground outline-none placeholder:text-muted-foreground focus:ring-0"
          :placeholder="placeholder?.replace('{n}', String(index + 1)) || `${$t('Punktas')} ${index + 1}...`"
          @input="updateItem(index, ($event.target as HTMLInputElement).value)"
          @keydown.enter.prevent="focusDraftOrNext(index)"
          @keydown.backspace="handleBackspace(index, item)"
        >

        <button
          type="button"
          class="shrink-0 text-muted-foreground transition-colors hover:text-destructive"
          :aria-label="`${$t('Šalinti')} ${index + 1}`"
          @click="removeItem(index)"
        >
          <X class="size-4" />
        </button>
      </li>
    </ol>

    <!-- Inline draft input bar when list is not full -->
    <div
      v-if="!max || modelValue.length < max"
      :class="[
        'flex min-h-12 items-center border border-border bg-secondary/50 transition-colors',
        'focus-within:bg-background focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20',
      ]"
    >
      <input
        ref="draftInputRef"
        v-model="draftText"
        type="text"
        data-testid="ordered-list-draft-input"
        :placeholder="modelValue.length === 0
          ? (placeholder?.replace('{n}', '1') || $t('Pridėk akcentą ir spausk Enter…'))
          : $t('Pridėk kitą akcentą ir spausk Enter…')"
        class="w-full bg-transparent px-3 text-sm text-foreground outline-none placeholder:text-muted-foreground"
        @keydown.enter.prevent="addDraft"
      >
      <button
        type="button"
        data-testid="ordered-list-add-draft"
        :class="[
          'flex h-12 shrink-0 items-center gap-1.5 border-l border-border px-4',
          'text-xs font-bold uppercase tracking-wide text-brand transition-colors',
          'hover:bg-secondary/60 disabled:cursor-not-allowed disabled:opacity-40',
        ]"
        :disabled="!draftText.trim()"
        @click="addDraft"
      >
        <Plus class="size-4" />
        {{ addText }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, type Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, X } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
  /** Maximum number of items allowed */
  max?: number;
  /** Placeholder text for input. Use {n} for item number. */
  placeholder?: string;
  /** Type of input field */
  inputType?: 'input' | 'textarea';
  /** Text for empty state */
  emptyText?: string;
  /** Text for "Add first" button */
  addFirstText?: string;
  /** Text for "Add" button */
  addText?: string;
  /** Icon for empty state */
  emptyIcon?: Component;
}>(), {
  inputType: 'input',
  emptyText: 'Dar nepridėta jokių punktų',
  addFirstText: 'Pridėti pirmą punktą',
  addText: 'Pridėti',
});

const modelValue = defineModel<string[]>({ required: true });

const draftText = ref('');
const draftInputRef = ref<HTMLInputElement | null>(null);

function updateItem(index: number, val: string) {
  const next = [...modelValue.value];
  next[index] = val;
  modelValue.value = next;
}

function removeItem(index: number) {
  const next = [...modelValue.value];
  next.splice(index, 1);
  modelValue.value = next;
}

function addDraft() {
  const text = draftText.value.trim();
  if (!text) return;
  if (props.max && modelValue.value.length >= props.max) return;

  modelValue.value = [...modelValue.value, text];
  draftText.value = '';

  nextTick(() => {
    draftInputRef.value?.focus();
  });
}

function focusDraftOrNext(index: number) {
  if (index + 1 < modelValue.value.length) {
    const list = document.querySelector('[data-ordered-list-input]');
    const inputs = list?.querySelectorAll('input, textarea');
    (inputs?.[index + 1] as HTMLElement)?.focus();
  }
  else if (!props.max || modelValue.value.length < props.max) {
    draftInputRef.value?.focus();
  }
}

function handleBackspace(index: number, value: string) {
  if (value === '' && modelValue.value.length > 0) {
    removeItem(index);
    nextTick(() => {
      if (index > 0) {
        const list = document.querySelector('[data-ordered-list-input]');
        const inputs = list?.querySelectorAll('input, textarea');
        (inputs?.[index - 1] as HTMLElement)?.focus();
      }
      else {
        draftInputRef.value?.focus();
      }
    });
  }
}
</script>
