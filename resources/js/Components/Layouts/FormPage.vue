<template>
  <AdminContentPage>
    <Head>
      <title>{{ headTitle }}</title>
    </Head>

    <!-- Editing is unmistakable (.ai/rules/js-pages-admin.md): tinted paper canvas + eyebrow. -->
    <div
      :class="[
        'mx-auto w-full bg-secondary px-4 pt-6 sm:px-6',
        mode === 'view' ? 'pb-8' : 'pb-24',
        maxWidthClass,
      ]"
      data-slot="form-page"
    >
      <!-- Form Header -->
      <header class="space-y-4 border-b border-border pb-6">
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <Button
              v-if="backHref"
              as-child
              variant="ghost"
              size="sm"
              class="u-touch -ml-2 text-muted-foreground hover:text-foreground"
            >
              <Link :href="backHref">
                <ArrowLeft class="size-4" />
                <span>{{ backLabel ?? $t('Grįžti') }}</span>
              </Link>
            </Button>
            <EntityTypeMark v-if="entityType" :type="entityType" size="sm" />
          </div>

          <div class="flex items-center gap-3">
            <!-- Language toggle if multi-lingual -->
            <div v-if="availableLocales.length > 1" class="flex items-center gap-1.5">
              <div class="inline-flex border border-border bg-secondary p-0.5">
                <button
                  v-for="loc in availableLocales"
                  :key="loc"
                  type="button"
                  class="u-touch px-2.5 py-1 text-xs font-semibold uppercase tracking-wider transition-colors"
                  :class="[
                    currentLocale === loc
                      ? 'bg-card text-foreground'
                      : 'text-muted-foreground hover:text-foreground',
                  ]"
                  @click="setLocale(loc)"
                >
                  {{ loc.toUpperCase() }}
                  <span
                    v-if="missingLocaleCounts && missingLocaleCounts[loc]"
                    class="ml-1.5 inline-block size-1.5 bg-destructive align-middle"
                    :title="$t('Trūksta vertimų')"
                  >
                    <span class="sr-only">{{ $t('Trūksta vertimų') }}</span>
                  </span>
                </button>
              </div>
            </div>

            <slot name="header-actions" />
          </div>
        </div>

        <div class="space-y-1">
          <p class="text-[11px] font-semibold uppercase tracking-wider text-primary" data-testid="form-page-eyebrow">
            {{ mode === 'create' ? $t('Kuri naują') : mode === 'view' ? $t('Peržiūri') : $t('Redaguoji') }}
          </p>
          <h1 class="u-display text-3xl leading-tight text-foreground lg:text-4xl">
            {{ title }}
          </h1>
          <p v-if="lead" class="text-sm text-muted-foreground">
            {{ lead }}
          </p>
        </div>

        <!-- Missing translation banner if applicable -->
        <div
          v-if="missingInCurrentLocale"
          :class="[
            'flex items-center gap-2 border px-3 py-2 text-xs font-medium',
            'border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] text-[var(--status-attention)]',
          ]"
        >
          <Languages class="size-3.5 shrink-0" />
          <span>{{ $t('Šia kalba trūksta laukų (:count)', { count: String(missingInCurrentLocale) }) }}</span>
        </div>
      </header>

      <!-- Validation Error Summary (Rules/pages.md -> Forms, 10) -->
      <div
        v-if="hasErrors"
        ref="summary"
        role="alert"
        tabindex="-1"
        class="mt-6 border border-[var(--status-danger-border)] bg-[var(--status-danger-surface)] p-4 text-[var(--status-danger)] outline-none"
        data-testid="form-page-errors"
      >
        <p class="text-sm font-semibold">
          {{ $t('Formoje yra klaidų:') }}
        </p>
        <ul class="mt-2 space-y-1 text-xs">
          <li v-for="(error, key) in errors" :key>
            <button
              type="button"
              class="u-touch text-left underline underline-offset-2"
              @click="focusField(String(key))"
            >
              {{ error }}
            </button>
          </li>
        </ul>
      </div>

      <!-- Main Form Body -->
      <form
        :id="formId"
        ref="formEl"
        class="mt-8 space-y-8"
        @submit.prevent="mode !== 'view' && emit('submit')"
      >
        <slot />

        <!-- Optional Collapsible Advanced Settings -->
        <details
          v-if="$slots['advanced']"
          class="group border-t border-border pt-6"
        >
          <summary class="u-touch flex cursor-pointer items-center justify-between text-sm font-semibold text-foreground select-none">
            <span>{{ $t('Papildomi nustatymai') }}</span>
            <ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" />
          </summary>
          <div class="mt-6 space-y-6">
            <slot name="advanced" />
          </div>
        </details>

        <!-- Optional Danger Zone -->
        <div v-if="$slots['danger-zone']" class="border-t border-destructive/20 pt-8">
          <slot name="danger-zone" />
        </div>
      </form>
    </div>

    <!-- Sticky Bottom Save Bar (Rules/pages.md -> Forms, 13) -->
    <div
      v-if="mode !== 'view'"
      :class="[
        'fixed bottom-(--shell-bottom-bar,0px) left-0 right-0 z-40',
        'border-t border-border bg-card/95 px-4 py-3 backdrop-blur-sm',
      ]"
    >
      <div :class="['mx-auto flex items-center justify-between gap-4', maxWidthClass]">
        <!-- Status Indicator -->
        <div class="flex items-center gap-2 text-sm">
          <Transition name="fade" mode="out-in">
            <div v-if="processing" key="saving" class="flex items-center gap-2 text-muted-foreground">
              <Loader2 class="size-4 animate-spin" />
              <span class="hidden sm:inline">{{ $t('Saugoma…') }}</span>
            </div>
            <div v-else-if="dirty" key="dirty" class="flex items-center gap-2 text-[var(--status-attention)]">
              <span class="size-2 bg-[var(--status-attention)] motion-safe:animate-pulse" />
              <span class="hidden sm:inline">{{ $t('Neišsaugota') }}</span>
            </div>
            <!-- A create form has nothing saved yet, so "all saved" would be a lie. -->
            <div v-else-if="mode === 'edit'" key="saved" class="flex items-center gap-2 text-muted-foreground">
              <span class="hidden sm:inline">{{ $t('Visi pakeitimai išsaugoti') }}</span>
            </div>
          </Transition>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <slot name="footer-extra" />

          <Button
            v-if="backHref"
            variant="ghost"
            type="button"
            class="u-touch"
            @click="handleCancel"
          >
            {{ $t('Atšaukti') }}
          </Button>

          <Button
            variant="brand"
            type="submit"
            :form="formId"
            class="u-touch uppercase"
            :disabled="processing || disabled"
          >
            <Loader2 v-if="processing" class="size-4 animate-spin" />
            {{ saveLabel ?? $t('Išsaugoti') }}
          </Button>
        </div>
      </div>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowLeft, ChevronDown, Languages, Loader2 } from 'lucide-vue-next';
import { useEventListener } from '@vueuse/core';
import { computed, nextTick, ref, useId, watch } from 'vue';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { Button } from '@/Components/ui/button';
import type { ModelEnum } from '@/Types/enums';

const props = withDefaults(defineProps<{
  title: string;
  headTitle?: string;
  lead?: string;
  entityType?: ModelEnum | string;
  backHref?: string;
  backLabel?: string;
  saveLabel?: string;
  processing?: boolean;
  dirty?: boolean;
  disabled?: boolean;
  errors?: Record<string, string>;
  /** Maps an error key (`name.lt`) to the id of the field it belongs to, when they differ. */
  fieldIds?: Record<string, string>;
  /** `create` says "Kuri naują" and never claims anything is already saved. */
  mode?: 'create' | 'edit' | 'view';
  locale?: 'lt' | 'en';
  availableLocales?: Array<'lt' | 'en'>;
  missingLocaleCounts?: Record<string, number>;
  maxWidth?: '2xl' | '4xl' | '5xl' | 'full';
}>(), {
  headTitle: undefined,
  lead: undefined,
  entityType: undefined,
  backHref: undefined,
  backLabel: undefined,
  saveLabel: undefined,
  errors: () => ({}),
  fieldIds: () => ({}),
  mode: 'edit',
  locale: 'lt',
  availableLocales: () => ['lt', 'en'],
  missingLocaleCounts: () => ({}),
  maxWidth: '2xl',
});

const emit = defineEmits<{
  (e: 'submit'): void;
  (e: 'cancel'): void;
  (e: 'update:locale', locale: 'lt' | 'en'): void;
}>();

const headTitle = computed(() => props.headTitle ?? props.title);

const maxWidthClass = computed(() => {
  switch (props.maxWidth) {
    case '4xl':
      return 'max-w-4xl';
    case '5xl':
      return 'max-w-5xl';
    case 'full':
      return 'max-w-full';
    case '2xl':
    default:
      return 'max-w-2xl';
  }
});

const currentLocale = computed(() => props.locale);

const setLocale = (loc: 'lt' | 'en') => {
  emit('update:locale', loc);
};

const missingInCurrentLocale = computed(() => {
  return props.missingLocaleCounts?.[currentLocale.value] ?? 0;
});

const hasErrors = computed(() => {
  return props.errors && Object.keys(props.errors).length > 0;
});

const formId = `form-page-${useId()}`;
const summary = ref<HTMLElement | null>(null);

const focusField = (key: string) => {
  const id = props.fieldIds[key] ?? key;
  const field = document.getElementById(id) ?? document.querySelector<HTMLElement>(`[name="${CSS.escape(key)}"]`);

  if (field) {
    field.scrollIntoView({ block: 'center' });
    field.focus({ preventScroll: true });
  }
};

// On a failed submit the user is looking at the sticky bar; bring the problem to them.
watch(() => Object.keys(props.errors).join('|'), async (keys, previous) => {
  if (!keys || keys === previous) {
    return;
  }

  await nextTick();
  summary.value?.scrollIntoView({ block: 'start', behavior: 'smooth' });
  summary.value?.focus({ preventScroll: true });
});

const formEl = ref<HTMLFormElement | null>(null);

// On the form, not `document`, so Esc inside a portaled Select or Popover never cancels it.
useEventListener(formEl, 'keydown', (event: KeyboardEvent) => {
  if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
    event.preventDefault();

    if (props.mode !== 'view' && !props.processing && !props.disabled) {
      emit('submit');
    }
  }
  else if (event.key === 'Escape' && !event.defaultPrevented) {
    handleCancel();
  }
});

const handleCancel = () => {
  emit('cancel');
  if (props.backHref) {
    router.visit(props.backHref);
  }
};
</script>
