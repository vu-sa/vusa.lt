<template>
  <component :is="as" v-if="!editable">{{ modelValue }}</component>
  <component
    :is="as"
    v-else
    :ref="setElRef"
    contenteditable="plaintext-only"
    data-rc-interactive
    class="rc-inline-editable outline-none focus-visible:ring-2 focus-visible:ring-brand/50 focus-visible:ring-offset-2 rounded-sm"
    :data-placeholder="placeholder"
    @input="onInput"
    @focus="onFocus"
    @blur="onBlur"
  />
</template>

<script setup lang="ts">
/**
 * The one inline-editable text primitive for the full-screen editor. Two mechanisms
 * cover every field shape in the registry: this one for a plain string (title, eyebrow,
 * button text, …); a real mounted TiptapEditor, gated the same way, for genuine Tiptap
 * doc/HTML fields (card body, hero title, …) — that second one is NOT this component,
 * it's mounted directly at each call site.
 *
 * Zero-cost when `editable` is false: it is embedded directly in public display
 * components (RichContentCard.vue, HeroElement.vue, …), so the non-editable branch must
 * carry no listeners, no template ref, no watcher — public rendering is unaffected by
 * this component existing at all.
 *
 * The editable branch carries no `{{ modelValue }}` interpolation at all — deliberately.
 * After mount, this element's text content is owned by the DOM (the user is typing into
 * it) and by the manual `setElRef`/`watch` below (initial value and external changes);
 * a reactive interpolation would fight both, resetting the caret and/or clobbering
 * in-flight keystrokes on every re-render. `v-once` looks like the obvious fix but
 * doesn't compose with `v-else` here — verified against this Vue version, the
 * interpolation keeps re-rendering on every prop change regardless — so this component
 * skips it entirely rather than reach for a workaround (two independent `v-if`s instead
 * of `v-if`/`v-else` "fixes" `v-once` but loses single-root `$el` resolution; nesting a
 * `v-once` span inside breaks the `:empty` CSS placeholder below, which requires the
 * element's only child, when blank, to be nothing at all, not an empty element).
 */
import { onBeforeUnmount, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';

const props = withDefaults(defineProps<{
  modelValue: string;
  /** Rendered tag — must match the semantic element the public display uses (h1/h2/span/…). */
  as?: string;
  editable?: boolean;
  placeholder?: string;
}>(), {
  as: 'span',
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
  (e: 'focus'): void;
  (e: 'blur'): void;
}>();

const elRef = ref<HTMLElement | null>(null);
const isFocused = ref(false);

// A function ref, not a static one: `v-if`/`v-else` swaps this element out for a fresh
// DOM node every time `editable` toggles (view → edit → view → edit, …), but `onMounted`
// only fires once for the whole component. A static ref left every re-entry into edit
// mode after the first with an empty, unseeded contenteditable — the value round-tripped
// fine, but nothing was ever visible again. Seed on every (re)attach instead.
function setElRef(el: Element | null): void {
  elRef.value = el as HTMLElement | null;
  // Vue re-invokes a function ref on every patch, not just mount — guard identically to
  // the `watch` below (skip while focused, skip when already correct) so an unrelated
  // re-render can't reset the caret to position 0 mid-keystroke.
  if (!el || isFocused.value) return;
  if (el.textContent !== props.modelValue) el.textContent = props.modelValue;
}

// Debounced write-back on every keystroke, not per-character — a contenteditable has no
// controlled-value concept, so committing on every input already avoids fighting the
// DOM; debouncing further just spares the parent (and any watcher below) redundant work.
const commit = useDebounceFn(() => {
  if (!elRef.value) return;
  emit('update:modelValue', elRef.value.textContent ?? '');
}, 150);

function onInput(): void {
  commit();
}

function onFocus(): void {
  isFocused.value = true;
  emit('focus');
}

function onBlur(): void {
  isFocused.value = false;
  // Flush immediately on blur — the debounce above must not be allowed to fire after
  // the field (and possibly the whole block) has already been deselected.
  if (elRef.value) emit('update:modelValue', elRef.value.textContent ?? '');
  emit('blur');
}

// Switching the full-screen canvas to preview unmounts inline fields. Preserve a
// keystroke that is still inside the debounce window before that teardown happens.
onBeforeUnmount(() => {
  if (elRef.value && isFocused.value) emit('update:modelValue', elRef.value.textContent ?? '');
});

// Guarded against writing into the DOM while focused: a contenteditable's DOM text node
// is the source of truth while the user types, and resetting `textContent` mid-focus
// (e.g. from a debounced round-trip through the parent) would reset the caret to
// position 0 on every keystroke.
watch(() => props.modelValue, (value) => {
  if (!elRef.value || isFocused.value) return;
  if (elRef.value.textContent !== value) elRef.value.textContent = value;
});
</script>

<style scoped>
.rc-inline-editable:empty:before {
  content: attr(data-placeholder);
  opacity: 0.4;
  pointer-events: none;
}
</style>
