<template>
  <p v-if="segments.length === 0" class="italic text-zinc-400 dark:text-zinc-500">
    {{ $t('activity.empty_value') }}
  </p>
  <p v-else class="break-words">
    <template v-for="segment in segments" :key="segment.id">
      <ins v-if="segment.type === 'added'" class="rounded bg-green-100 text-green-800 no-underline dark:bg-green-950/40 dark:text-green-400">
        <span class="sr-only">{{ $t('activity.diff.added') }}</span>{{ segment.text }}</ins>
      <del v-else-if="segment.type === 'removed'" class="rounded bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400">
        <span class="sr-only">{{ $t('activity.diff.removed') }}</span>{{ segment.text }}</del>
      <template v-else-if="segment.type === 'common-collapsed'">
        {{ segment.head }}<button
          type="button"
          class="mx-1 rounded px-1 text-xs text-zinc-400 underline decoration-dotted hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
          :aria-expanded="expanded.has(segment.id)"
          @click="toggle(segment.id)"
        >
          {{ expanded.has(segment.id) ? $t('activity.diff.show_less') : $t('activity.diff.show_more') }}
        </button>{{ expanded.has(segment.id) ? segment.middle : '' }}{{ segment.tail }}
      </template>
      <template v-else>
        {{ segment.text }}
      </template>
    </template>
  </p>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { diffWords } from 'diff';

// Unchanged runs longer than this many words collapse behind a toggle -- the
// activity sheet is `sm:max-w-xl` (see ActivityLogSheet.vue), so an
// unabridged paragraph with a few changed words in the middle is still
// "find the change yourself", which is the complaint this component fixes.
const COLLAPSE_THRESHOLD = 30;
const CONTEXT_WORDS = 8;

// Above this many characters per side, diffWords's Myers-diff cost grows
// large enough to matter inside a synchronous render; degrade to a plain
// before/after instead of hanging the tab. The backend's own DIFF_CHAR_CAP
// (ActivityChangeFormatter) already keeps normal payloads far under this --
// this is a client-side backstop, not the primary guard.
const MAX_EDIT_LENGTH = 400;

type Segment
  = | { id: number; type: 'common' | 'added' | 'removed'; text: string }
    | { id: number; type: 'common-collapsed'; head: string; middle: string; tail: string };

const props = defineProps<{
  old: string | null;
  new: string | null;
}>();

const expanded = reactive(new Set<number>());

function toggle(id: number): void {
  if (expanded.has(id)) {
    expanded.delete(id);
  }
  else {
    expanded.add(id);
  }
}

const segments = computed<Segment[]>(() => {
  const oldText = props.old ?? '';
  const newText = props.new ?? '';

  if (oldText === '' && newText === '') {
    return [];
  }

  const changes = diffWords(oldText, newText, { maxEditLength: MAX_EDIT_LENGTH });

  // diffWords() returns undefined when maxEditLength is exceeded (the
  // abortable overload) -- fall back to a flat before/after rather than
  // hanging on a pathologically different pair of strings.
  if (changes === undefined) {
    const fallback: Segment[] = [];

    if (oldText !== '') fallback.push({ id: 0, type: 'removed', text: oldText });
    if (newText !== '') fallback.push({ id: 1, type: 'added', text: newText });

    return fallback;
  }

  return changes.flatMap((change, index) => {
    if (change.added) return [{ id: index, type: 'added' as const, text: change.value }];
    if (change.removed) return [{ id: index, type: 'removed' as const, text: change.value }];

    return [collapseIfLong(index, change.value)];
  });
});

function collapseIfLong(id: number, text: string): Segment {
  const words = text.split(/(\s+)/);
  // Every other token is a whitespace separator from the split above, so the
  // actual word count is half the token count.
  if (words.length / 2 < COLLAPSE_THRESHOLD) {
    return { id, type: 'common', text };
  }

  const headEnd = CONTEXT_WORDS * 2;
  const tailStart = words.length - CONTEXT_WORDS * 2;

  return {
    id,
    type: 'common-collapsed',
    head: words.slice(0, headEnd).join(''),
    middle: words.slice(headEnd, tailStart).join(''),
    tail: words.slice(tailStart).join(''),
  };
}
</script>
