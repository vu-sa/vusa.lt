<template>
  <div
    :class="[
      'mx-auto w-full',
      mode === 'view' ? 'pb-8' : 'pb-28 md:pb-12',
      containerWidthClass,
    ]"
    data-slot="form-page"
  >
    <Head>
      <title>{{ headTitle }}</title>
    </Head>

    <!-- In the admin shell this replaces the navigation chrome (useShellFocus); elsewhere it renders inline. -->
    <Teleport defer :to="`#${SHELL_FORM_BAR_ID}`" :disabled="!shellFocus">
      <div class="flex min-w-0 flex-1 items-center gap-2 md:gap-3" data-testid="form-page-bar">
        <Link
          v-if="backHref"
          :href="backHref"
          class="group inline-flex shrink-0 items-center gap-2 text-sm font-bold text-foreground transition-colors hover:text-brand"
        >
          <span class="flex size-9 items-center justify-center border border-border transition-colors group-hover:border-brand pointer-coarse:size-11">
            <ArrowLeft class="size-4" />
          </span>
          <span :class="shellFocus ? 'sr-only lg:not-sr-only' : ''">{{ backLabel ?? $t('Grįžti') }}</span>
        </Link>

        <div class="min-w-0 flex-1 border-l border-border pl-3">
          <div class="flex items-center gap-2 min-w-0">
            <p class="truncate text-sm font-bold text-foreground" data-testid="form-page-bar-title">
              {{ barTitle ?? title }}
            </p>
            <slot name="title-status" />
          </div>
          <SaveState v-if="saveState" :state="saveState" class="hidden md:flex" />
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <div v-if="$slots['footer-extra']" class="hidden xl:block">
            <slot name="footer-extra" />
          </div>
          <ActivityLogSheet
            v-if="activitySubject"
            :subject-type="activitySubject.type"
            :subject-id="String(activitySubject.id)"
          />
          <Button v-if="publicUrl" as-child variant="outline" size="sm" class="hidden sm:inline-flex">
            <a :href="publicUrl" target="_blank" rel="noopener noreferrer">
              <Eye class="size-4" />
              {{ $t('Peržiūrėti viešai') }}
            </a>
          </Button>
          <slot name="header-actions" />

          <Button
            v-if="mode !== 'view'"
            variant="brand"
            size="sm"
            type="submit"
            :form="formId"
            class="hidden md:inline-flex"
            :disabled="processing || disabled"
          >
            <Loader2 v-if="processing" class="size-4 animate-spin" />
            <Save v-else class="size-4" />
            {{ saveLabel ?? $t('Išsaugoti') }}
          </Button>
        </div>
      </div>
    </Teleport>

    <!-- Heading band: editing is unmistakable (.ai/rules/js-pages-admin.md) — tinted canvas + eyebrow. -->
    <header
      :class="[
        'space-y-3 border-b border-border pt-6 sm:pt-10',
        // The language row already closes the band; the full gap under it read as a stray margin.
        availableLocales.length > 1 ? 'pb-6 sm:pb-8' : 'pb-8 sm:pb-12',
      ]"
    >
      <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <EntityTypeMark v-if="entityType" :type="entityType" size="sm" class="text-xs font-bold uppercase tracking-[0.2em]" />
        <span v-if="entityType" class="h-3 border-l border-border" aria-hidden="true" />
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand" data-testid="form-page-eyebrow">
          {{ mode === 'create' ? $t('Kuri naują') : mode === 'view' ? $t('Peržiūri') : $t('Redaguoji') }}
        </p>
      </div>

      <h1 class="u-display text-balance text-4xl leading-[0.95] text-foreground sm:text-5xl">
        {{ title }}
      </h1>
      <p v-if="lead" class="max-w-xl text-pretty leading-relaxed text-muted-foreground">
        {{ lead }}
      </p>

      <div v-if="availableLocales.length > 1 || $slots['locale-addon']" class="flex flex-wrap items-center gap-3 pt-2">
        <div v-if="availableLocales.length > 1" :class="segmentGroupClass" role="group" :aria-label="$t('Kalba')">
          <button
            v-for="loc in availableLocales"
            :key="loc"
            type="button"
            :class="segmentVariants({ active: currentLocale === loc })"
            :aria-pressed="currentLocale === loc"
            @click="setLocale(loc)"
          >
            <LocaleFlag :locale="loc" />
            <span>{{ loc.toUpperCase() }}</span>
            <span
              v-if="missingLocaleCounts && missingLocaleCounts[loc]"
              class="inline-block size-1.5 bg-destructive"
              :title="$t('Trūksta vertimų')"
            >
              <span class="sr-only">{{ $t('Trūksta vertimų') }}</span>
            </span>
          </button>
        </div>

        <!-- Beside the switch, and holding its place while hidden, so switching language never
             moves the form. -->
        <div
          v-if="availableLocales.length > 1"
          :class="[
            'flex items-center gap-2 border px-3 py-2 text-xs font-medium',
            'border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] text-[var(--status-attention)]',
            !missingInCurrentLocale && 'invisible',
          ]"
          :aria-hidden="!missingInCurrentLocale"
          data-testid="form-page-missing-locale"
        >
          <Languages class="size-3.5 shrink-0" />
          <span>{{ $t('Šia kalba trūksta laukų (:count)', { count: String(missingInCurrentLocale || 0) }) }}</span>
        </div>

        <slot name="locale-addon" />
      </div>
    </header>

    <div
      v-if="hasErrors"
      ref="summary"
      role="alert"
      tabindex="-1"
      class="mt-8 border border-[var(--status-danger-border)] bg-[var(--status-danger-surface)] p-4 text-[var(--status-danger)] outline-none"
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

    <form
      :id="formId"
      ref="formEl"
      :class="[
        'mt-8',
        // Two-column editors are dense; full-contrast labels keep the field names scannable.
        $slots.aside ? 'grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:gap-12 [&_[data-slot=form-field]_label]:text-foreground' : 'space-y-8',
      ]"
      @submit.prevent="mode !== 'view' && emit('submit')"
    >
      <div :class="$slots.aside ? 'flex min-w-0 flex-col gap-8' : 'contents'">
        <slot />

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

        <div v-if="$slots['danger-zone'] && !$slots.aside" class="border-t border-destructive/20 pt-8">
          <slot name="danger-zone" />
        </div>
      </div>

      <aside v-if="$slots.aside" class="flex min-w-0 flex-col gap-6" data-testid="form-page-aside">
        <slot name="aside" />

        <dl v-if="createdAt || updatedAt" class="border border-border bg-background text-sm" data-testid="form-page-meta">
          <div v-if="createdAt" class="flex items-center justify-between gap-4 border-b border-border px-4 py-3 last:border-b-0">
            <dt class="text-muted-foreground">
              {{ $t('Sukurta') }}
            </dt>
            <dd class="font-bold text-foreground">
              {{ formatDate(createdAt) }}
            </dd>
          </div>
          <div v-if="updatedAt" class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-muted-foreground">
              {{ $t('Atnaujinta') }}
            </dt>
            <dd class="font-bold text-foreground">
              {{ formatDate(updatedAt) }}
            </dd>
          </div>
        </dl>

        <div v-if="$slots['danger-zone']" class="flex flex-col gap-3" data-testid="form-page-danger-zone">
          <slot name="danger-zone" />
        </div>
      </aside>
    </form>

    <!-- Phones: the save bar takes the bottom nav's place, in thumb reach. -->
    <div
      v-if="mode !== 'view'"
      :class="[
        'fixed inset-x-0 bottom-(--shell-bottom-bar,0px) z-40 md:hidden',
        'flex items-center justify-between gap-4 border-t border-border bg-background px-4 pt-3',
        'pb-[calc(0.75rem_+_env(safe-area-inset-bottom,0px))]',
      ]"
      data-testid="form-page-mobile-save"
    >
      <SaveState v-if="saveState" :state="saveState" />
      <span v-else />

      <Button
        variant="brand"
        type="submit"
        :form="formId"
        :disabled="processing || disabled"
      >
        <Loader2 v-if="processing" class="size-4 animate-spin" />
        <Save v-else class="size-4" />
        {{ saveLabel ?? $t('Išsaugoti') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowLeft, ChevronDown, Eye, Languages, Loader2, Save } from 'lucide-vue-next';
import { useEventListener } from '@vueuse/core';
import { computed, h, nextTick, onUnmounted, ref, useId, watch, type FunctionalComponent } from 'vue';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import { Button } from '@/Components/ui/button';
import { segmentGroupClass, segmentVariants } from '@/Components/ui/control';
import { SHELL_FORM_BAR_ID, useShellFocus } from '@/Composables/useShellFocus';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { formatDate } from '@/Utils/dateTime';
import type { ModelEnum } from '@/Types/enums';

const props = withDefaults(defineProps<{
  title: string;
  /** The bar's (and tab's) title when it should state the saved record while `title` follows the fields. */
  barTitle?: string;
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
  /** Width of a single-column form; a form with an `#aside` is always `6xl`. */
  maxWidth?: '2xl' | '4xl' | '5xl' | 'full';
  /** Where visitors see this record; adds "Peržiūrėti viešai" to the bar. */
  publicUrl?: string;
  /** Adds the change-history sheet to the bar. */
  activitySubject?: { type: string; id: number | string };
  /** Record facts listed at the end of the `#aside`. */
  createdAt?: string | null;
  updatedAt?: string | null;
}>(), {
  barTitle: undefined,
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
  publicUrl: undefined,
  activitySubject: undefined,
  createdAt: undefined,
  updatedAt: undefined,
});

const emit = defineEmits<{
  (e: 'submit'): void;
  (e: 'cancel'): void;
  (e: 'update:locale', locale: 'lt' | 'en'): void;
}>();

const slots = defineSlots<{
  'default'?: () => unknown;
  'aside'?: () => unknown;
  'advanced'?: () => unknown;
  /** Below the fields; in a two-column form, at the end of the `#aside`. */
  'danger-zone'?: () => unknown;
  'header-actions'?: () => unknown;
  /** Quiet context beside the actions (e.g. last sign-in); wide screens only. */
  'footer-extra'?: () => unknown;
}>();

const shellFocus = useShellFocus();

if (shellFocus) {
  onUnmounted(shellFocus.enter());
}

const headTitle = computed(() => props.headTitle ?? props.barTitle ?? props.title);

const containerWidthClass = computed(() => {
  if (slots.aside) {
    return 'max-w-6xl';
  }

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

type SaveStateKind = 'saving' | 'dirty' | 'saved';

const saveState = computed<SaveStateKind | null>(() => {
  if (props.mode === 'view') {
    return null;
  }
  if (props.processing) {
    return 'saving';
  }
  if (props.dirty) {
    return 'dirty';
  }
  // A create form has nothing saved yet, so "all saved" would be a lie.
  return props.mode === 'edit' ? 'saved' : null;
});

const SaveState: FunctionalComponent<{ state: SaveStateKind }> = ({ state }) => {
  const label = {
    saving: $t('Saugoma…'),
    dirty: $t('Neišsaugota'),
    saved: $t('Visi pakeitimai išsaugoti'),
  }[state];

  return h('span', {
    'class': [
      'flex items-center gap-1.5 text-xs',
      state === 'dirty' ? 'text-[var(--status-attention)]' : 'text-muted-foreground',
    ],
    'role': 'status',
    'aria-live': 'polite',
  }, [
    state === 'saving' && h(Loader2, { class: 'size-3 animate-spin' }),
    state === 'dirty' && h('span', { class: 'size-1.5 bg-[var(--status-attention)] motion-safe:animate-pulse' }),
    label,
  ]);
};

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

// On a failed submit the user is looking at the save button; bring the problem to them.
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
