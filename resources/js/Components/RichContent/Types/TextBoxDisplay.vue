<template>
  <div class="border border-border bg-card text-card-foreground p-6 sm:p-8">
    <RCInlineText
      v-if="editable"
      as="h3"
      class="mb-4 text-xl font-bold tracking-tight text-foreground"
      :model-value="title"
      :editable
      :placeholder="$t('rich-content.title')"
      @click.stop
      @update:model-value="updateTitle"
    />
    <h3 v-else-if="title" class="mb-4 text-xl font-bold tracking-tight text-foreground">
      {{ title }}
    </h3>

    <!-- Closed state -->
    <div v-if="isClosed" class="border border-border bg-secondary/40 p-4 text-sm text-muted-foreground">
      <RCInlineText
        v-if="editable"
        as="div"
        :model-value="closedMessage || $t('rich-content.text_box_closed_default')"
        :editable
        :placeholder="$t('rich-content.text_box_closed_default')"
        @click.stop
        @update:model-value="updateClosedMessage"
      />
      <template v-else>
        {{ closedMessage || $t('rich-content.text_box_closed_default') }}
      </template>
    </div>

    <!-- Already submitted -->
    <div
      v-else-if="submitted"
      class="flex items-center gap-2 border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300"
    >
      <IFluentCheckmark12Regular class="size-4 shrink-0" />
      <span>{{ $t('rich-content.text_box_success') }}</span>
    </div>

    <!-- Form -->
    <form v-else class="flex flex-col gap-3" @submit.prevent="submit">
      <!-- Honeypot: hidden from real users, bots fill it in -->
      <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;">
        <label for="website">Website</label>
        <input id="website" v-model="http.website" type="text" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="relative">
        <Textarea
          v-model="http.text"
          :placeholder
          :disabled="editable || http.processing"
          :maxlength="MAX_LENGTH"
          class="min-h-28 resize-y"
          required
        />
        <span
          class="absolute bottom-2 right-2.5 text-xs tabular-nums"
          :class="remaining <= 100 ? 'text-amber-600 dark:text-amber-400' : 'text-muted-foreground'"
        >
          {{ remaining }} / {{ MAX_LENGTH }}
        </span>
      </div>
      <div class="flex items-center gap-3">
        <Button
          type="submit"
          variant="brand"
          size="public"
          :disabled="editable || http.processing || !http.text.trim()"
        >
          <span v-if="http.processing" class="flex items-center gap-2">
            <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-r-transparent" />
            {{ $t('rich-content.text_box_submit') }}
          </span>
          <span v-else>{{ $t('rich-content.text_box_submit') }}</span>
        </Button>
        <p v-if="errorMessage" class="text-sm text-destructive">
          {{ errorMessage }}
        </p>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { useHttp, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';

import type { TextBox } from '@/Types/contentParts';
import type { ApiResponse } from '@/Types/api.d';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';

const MAX_LENGTH = 5000;

const props = defineProps<{
  element: TextBox & { id?: number };
  editable?: boolean;
  blockKey?: string;
}>();

const emit = defineEmits<(e: 'update:element', value: TextBox) => void>();

const page = usePage();
const locale = computed(() => page.props.app?.locale ?? 'lt');

const title = computed(() => {
  const t = props.element.options?.title;
  if (!t) {
    return '';
  }
  if (typeof t === 'string') {
    return t;
  }
  return t[locale.value as 'lt' | 'en'] || t.lt || t.en || '';
});

const placeholder = computed(() => {
  const p = props.element.options?.placeholder;
  if (!p) {
    return '';
  }
  if (typeof p === 'string') {
    return p;
  }
  return p[locale.value as 'lt' | 'en'] || p.lt || p.en || '';
});

const isClosed = computed(() => props.element.options?.isClosed === true);

const closedMessage = computed(() => {
  const m = props.element.options?.closedMessage;
  if (!m) {
    return '';
  }
  if (typeof m === 'string') {
    return m;
  }
  return m[locale.value as 'lt' | 'en'] || m.lt || m.en || '';
});

const storageKey = computed(() => `text_box_submitted_${props.element.id ?? 'preview'}`);

const submitted = ref(false);
const errorMessage = ref('');

const http = useHttp({
  content_part_id: props.element.id ?? 0,
  text: '',
  website: '',
});

const remaining = computed(() => MAX_LENGTH - http.text.length);

onMounted(() => {
  if (localStorage.getItem(storageKey.value) === '1') {
    submitted.value = true;
  }
});

function updateTitle(val: string): void {
  const currentTitle = props.element.options?.title;
  const newTitle = typeof currentTitle === 'object' && currentTitle !== null
    ? { ...currentTitle, [locale.value]: val }
    : val;

  emit('update:element', {
    ...props.element,
    options: {
      ...props.element.options,
      title: newTitle,
    },
  });
}

function updateClosedMessage(val: string): void {
  const currentMsg = props.element.options?.closedMessage;
  const newMsg = typeof currentMsg === 'object' && currentMsg !== null
    ? { ...currentMsg, [locale.value]: val }
    : val;

  emit('update:element', {
    ...props.element,
    options: {
      ...props.element.options,
      closedMessage: newMsg,
    },
  });
}

async function submit(): Promise<void> {
  if (props.editable || !http.text.trim() || http.processing) {
    return;
  }

  errorMessage.value = '';

  await http.post(route('api.v1.text-box-submissions.store'), {
    onSuccess: (json) => {
      const response = json as ApiResponse<unknown>;
      if (response.success) {
        submitted.value = true;
        localStorage.setItem(storageKey.value, '1');
      }
      else {
        errorMessage.value = response.message || 'An error occurred. Please try again.';
      }
    },
    onHttpException: () => {
      errorMessage.value = 'An error occurred. Please try again.';
    },
    onNetworkError: () => {
      errorMessage.value = 'An error occurred. Please try again.';
    },
  });
}
</script>
