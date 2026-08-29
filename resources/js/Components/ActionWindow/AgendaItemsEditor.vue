<template>
  <div data-slot="agenda-items-editor" class="flex flex-col gap-2">
    <!--
      Deliberately not AdminForms/Special/AgendaItemsForm: that one carries templates,
      drag-and-drop reordering and per-item times, which is the right tool on the meeting
      page and far too much on a phone. Here a question is a line of text.
    -->
    <div
      v-for="(item, index) in items"
      :key="index"
      class="flex items-center gap-2"
    >
      <span class="w-5 shrink-0 text-right text-sm tabular-nums text-muted-foreground">{{ index + 1 }}.</span>
      <Input
        :ref="(el: unknown) => setInputRef(el, index)"
        :model-value="item"
        :placeholder="$t('action_window.meeting.agenda.placeholder')"
        class="h-11 flex-1 rounded-xl"
        @update:model-value="(value: string | number) => update(index, String(value))"
        @keydown.enter.prevent="addAfter(index)"
        @keydown.backspace="removeIfEmpty(index, $event)"
      />
      <Button
        variant="ghost"
        size="icon"
        class="size-9 shrink-0 text-muted-foreground hover:text-destructive"
        :aria-label="$t('action_window.meeting.agenda.remove')"
        :disabled="items.length === 1 && !item"
        @click="remove(index)"
      >
        <X class="size-4" />
      </Button>
    </div>

    <Button variant="ghost" size="sm" class="self-start gap-1.5" @click="addAfter(items.length - 1)">
      <Plus class="size-4" />
      {{ $t('action_window.meeting.agenda.add_another') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { Plus, X } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const items = defineModel<string[]>({ required: true });

const inputs = ref<Array<HTMLInputElement | null>>([]);

const setInputRef = (el: unknown, index: number) => {
  // shadcn's Input forwards attrs onto a real <input>, so the ref is either that
  // element or the component instance wrapping it.
  const instance = el as { $el?: HTMLElement } | HTMLElement | null;
  const root = instance && '$el' in instance ? instance.$el : instance;

  inputs.value[index] = (root as HTMLElement | null)?.querySelector?.('input')
    ?? (root as HTMLInputElement | null);
};

const focus = (index: number) => nextTick(() => inputs.value[index]?.focus());

const update = (index: number, value: string) => {
  items.value = items.value.map((item, i) => (i === index ? value : item));
};

/** Enter behaves like a list editor: it opens the next line rather than submitting. */
const addAfter = (index: number) => {
  const next = [...items.value];
  next.splice(index + 1, 0, '');
  items.value = next;
  focus(index + 1);
};

const remove = (index: number) => {
  const next = items.value.filter((_, i) => i !== index);
  items.value = next.length > 0 ? next : [''];
  focus(Math.max(0, index - 1));
};

/** Backspace on an empty line removes it, the way every list editor behaves. */
const removeIfEmpty = (index: number, event: KeyboardEvent) => {
  if (items.value[index] === '' && items.value.length > 1) {
    event.preventDefault();
    remove(index);
  }
};
</script>
