<template>
  <AdminContentPage>
    <Head>
      <title>{{ headTitle }}</title>
    </Head>

    <div class="mx-auto w-full max-w-2xl pb-24">
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
                    class="ml-1 text-[10px] text-destructive"
                    :title="$t('Trūksta vertimų')"
                  >
                    •
                  </span>
                </button>
              </div>
            </div>

            <slot name="header-actions" />
          </div>
        </div>

        <div class="space-y-1">
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
        role="alert"
        class="mt-6 border border-[var(--status-danger-border)] bg-[var(--status-danger-surface)] p-4 text-[var(--status-danger)]"
      >
        <p class="text-sm font-semibold">
          {{ $t('Formoje yra klaidų:') }}
        </p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
          <li v-for="(error, key) in errors" :key>
            {{ error }}
          </li>
        </ul>
      </div>

      <!-- Main Form Body -->
      <form class="mt-8 space-y-8" @submit.prevent="emit('submit')">
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
      :class="[
        'fixed bottom-(--shell-bottom-bar,0px) left-0 right-0 z-40',
        'border-t border-border bg-card/95 px-4 py-3 backdrop-blur-sm md:left-(--sidebar-width,0px)',
      ]"
    >
      <div class="mx-auto flex max-w-2xl items-center justify-between gap-4">
        <!-- Status Indicator -->
        <div class="flex items-center gap-2 text-sm">
          <Transition name="fade" mode="out-in">
            <div v-if="processing" key="saving" class="flex items-center gap-2 text-muted-foreground">
              <Loader2 class="size-4 animate-spin" />
              <span class="hidden sm:inline">{{ $t('Saugoma…') }}</span>
            </div>
            <div v-else-if="dirty" key="dirty" class="flex items-center gap-2 text-[var(--status-attention)]">
              <span class="size-2 animate-pulse bg-[var(--status-attention)]" />
              <span class="hidden sm:inline">{{ $t('Neišsaugota') }}</span>
            </div>
            <div v-else key="saved" class="flex items-center gap-2 text-muted-foreground">
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
            class="u-touch uppercase"
            :disabled="processing || disabled"
            @click="emit('submit')"
          >
            <Loader2 v-if="processing" class="mr-2 size-4 animate-spin" />
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
import { computed } from 'vue';

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
  locale?: 'lt' | 'en';
  availableLocales?: Array<'lt' | 'en'>;
  missingLocaleCounts?: Record<string, number>;
}>(), {
  headTitle: undefined,
  lead: undefined,
  entityType: undefined,
  backHref: undefined,
  backLabel: undefined,
  saveLabel: undefined,
  errors: () => ({}),
  locale: 'lt',
  availableLocales: () => ['lt', 'en'],
  missingLocaleCounts: () => ({}),
});

const emit = defineEmits<{
  (e: 'submit'): void;
  (e: 'cancel'): void;
  (e: 'update:locale', locale: 'lt' | 'en'): void;
}>();

const headTitle = computed(() => props.headTitle ?? props.title);

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

const handleCancel = () => {
  emit('cancel');
  if (props.backHref) {
    router.visit(props.backHref);
  }
};
</script>
