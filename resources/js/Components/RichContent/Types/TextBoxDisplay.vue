<template>
  <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900/50">
    <h3 v-if="title" class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
      {{ title }}
    </h3>

    <!-- Closed state -->
    <div v-if="isClosed" class="rounded-md bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-400">
      {{ closedMessage || $t('rich-content.text_box_closed_default') }}
    </div>

    <!-- Already submitted -->
    <div v-else-if="submitted" class="rounded-md bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
      {{ $t('rich-content.text_box_success') }}
    </div>

    <!-- Form -->
    <form v-else class="flex flex-col gap-3" @submit.prevent="submit">
      <!-- Honeypot: hidden from real users, bots fill it in -->
      <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;">
        <label for="website">Website</label>
        <input id="website" v-model="honeypot" type="text" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="relative">
        <Textarea
          v-model="text"
          :placeholder
          :disabled="isSubmitting"
          :maxlength="MAX_LENGTH"
          class="min-h-28 resize-y"
          required
        />
        <span
          class="absolute bottom-2 right-2.5 text-xs tabular-nums"
          :class="remaining <= 100 ? 'text-amber-500 dark:text-amber-400' : 'text-zinc-400 dark:text-zinc-500'"
        >
          {{ remaining }} / {{ MAX_LENGTH }}
        </span>
      </div>
      <div class="flex items-center gap-3">
        <Button type="submit" :disabled="isSubmitting || !text.trim()">
          <span v-if="isSubmitting" class="flex items-center gap-2">
            <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-r-transparent" />
            {{ $t('rich-content.text_box_submit') }}
          </span>
          <span v-else>{{ $t('rich-content.text_box_submit') }}</span>
        </Button>
        <p v-if="errorMessage" class="text-sm text-red-600 dark:text-red-400">
          {{ errorMessage }}
        </p>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { TextBox } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';

const MAX_LENGTH = 5000;

const props = defineProps<{
  element: {
    id: number;
    options: TextBox['options'];
  };
}>();

const page = usePage();
const locale = computed(() => page.props.app?.locale ?? 'lt');

const title = computed(() => {
  const t = props.element.options?.title;
  if (!t) { return ''; }
  return t[locale.value as 'lt' | 'en'] || t.lt || t.en || '';
});

const placeholder = computed(() => {
  const p = props.element.options?.placeholder;
  if (!p) { return ''; }
  return p[locale.value as 'lt' | 'en'] || p.lt || p.en || '';
});

const isClosed = computed(() => props.element.options?.isClosed === true);

const closedMessage = computed(() => {
  const m = props.element.options?.closedMessage;
  if (!m) { return ''; }
  return m[locale.value as 'lt' | 'en'] || m.lt || m.en || '';
});

const storageKey = computed(() => `text_box_submitted_${props.element.id}`);

const text = ref('');
const honeypot = ref('');
const submitted = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');

const remaining = computed(() => MAX_LENGTH - text.value.length);

onMounted(() => {
  if (localStorage.getItem(storageKey.value) === '1') {
    submitted.value = true;
  }
});

async function submit(): Promise<void> {
  if (!text.value.trim() || isSubmitting.value) { return; }

  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const csrfToken = (page.props.csrf_token as string) || '';

    const response = await fetch(route('api.v1.text-box-submissions.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        content_part_id: props.element.id,
        text: text.value,
        website: honeypot.value,
      }),
    });

    const data = await response.json();

    if (data.success) {
      submitted.value = true;
      localStorage.setItem(storageKey.value, '1');
    }
    else {
      errorMessage.value = data.message || 'An error occurred. Please try again.';
    }
  }
  catch {
    errorMessage.value = 'An error occurred. Please try again.';
  }
  finally {
    isSubmitting.value = false;
  }
}
</script>
