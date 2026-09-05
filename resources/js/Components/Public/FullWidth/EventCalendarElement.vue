<template>
  <!--
    The events band: a ruled strip, chrome entirely driven by `band` (bandLayout.ts) — same
    derived ground/padding/bleed every other band-capable block gets (auto-alternating tint,
    or `presentation: 'plain'` with an authored `plainPadding`), via `CalendarBlockToolbar.vue`'s
    RCPresentationPicker. Not an RCSection (keeps its own header layout — sync button beside the
    title, a footer CTA row), so `band.classes` is bound directly rather than through that
    component.
  -->
  <section :class="bandClasses">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
      <div class="flex flex-wrap items-end justify-between gap-4 border-b border-border pb-5">
        <div>
          <EyebrowLabel>
            <RCInlineText
              as="span" :model-value="editable ? (element?.json_content?.eyebrow ?? '') : eyebrowText"
              :editable :placeholder="$t('Renginių kalendorius')"
              @update:model-value="updateEyebrow"
            />
          </EyebrowLabel>
          <!-- eslint-disable-next-line vuejs-accessibility/heading-has-content -- RCInlineText renders the real text at runtime; eslint can't see through the child component. -->
          <h2 class="u-display mt-2 text-3xl text-foreground sm:text-4xl">
            <RCInlineText
              as="span" :model-value="editable ? (element?.json_content?.title ?? '') : heading"
              :editable :placeholder="$t('Artimiausi renginiai')"
              @update:model-value="updateTitle"
            />
          </h2>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-foreground transition-colors hover:text-brand"
          @click="showModal = true"
        >
          <IFluentArrowSync16Regular class="size-4" />
          {{ $t('Sinchronizuoti kalendorių') }}
        </button>
      </div>

      <CalendarSyncModal v-model:show-modal="showModal" @close="showModal = false" />

      <div v-if="loading" class="mt-4">
        <div v-for="i in 4" :key="i" class="flex items-center gap-4 border-b border-border py-5 sm:gap-6">
          <Skeleton class="h-14 w-14 shrink-0 sm:w-16" />
          <div class="flex-1 space-y-2">
            <Skeleton class="h-4 w-2/3" />
            <Skeleton class="h-3 w-1/3" />
          </div>
        </div>
      </div>

      <div v-else-if="error" class="flex flex-col items-center justify-center py-12 text-center">
        <IFluentWarning20Regular class="mb-4 size-8 text-destructive" />
        <p class="mb-2 font-medium text-destructive">
          {{ $t("Nepavyko užkrauti kalendoriaus įvykių") }}
        </p>
        <Button variant="brand-outline" size="public-sm" @click="refresh">
          <IFluentArrowSync16Regular class="size-4" />
          {{ $t("Bandyti dar kartą") }}
        </Button>
      </div>

      <p v-else-if="upcomingEvents.length === 0" class="mt-8 text-muted-foreground">
        {{ $t('Nėra renginių') }}
      </p>

      <!--
        A scannable list, not a timeline. The horizontal timeline was a bespoke interaction that
        answered "what is happening across the next month" — but a reader arriving at the homepage
        wants the next few events and a way into the calendar, which is what the design gives them.
      -->
      <ul v-else class="mt-4">
        <li v-for="event in upcomingEvents" :key="event.id">
          <SmartLink
            :href="eventHref(event)"
            class="group -mx-4 flex items-center gap-4 border-b border-border px-4 py-6 transition-colors hover:bg-background sm:gap-6"
          >
            <div class="flex w-14 shrink-0 flex-col items-center justify-center border border-border bg-background py-2 text-foreground transition-colors group-hover:border-brand sm:w-16">
              <span class="text-2xl font-bold leading-none tabular-nums">{{ dayOfMonth(event.date) }}</span>
              <span class="mt-1 text-[0.625rem] font-bold uppercase tracking-wide text-muted-foreground">
                {{ formatMonthAbbr(new Date(event.date), locale) }}
              </span>
            </div>

            <div class="min-w-0 flex-1">
              <span v-if="event.category?.name" class="text-[0.6875rem] font-bold uppercase tracking-[0.18em] text-brand">
                {{ event.category.name }}
              </span>
              <h3 class="mt-1 text-pretty font-bold leading-snug text-foreground transition-colors group-hover:text-brand">
                {{ event.title }}
              </h3>
              <div class="mt-1.5 flex flex-col gap-1 text-sm text-muted-foreground sm:flex-row sm:items-center sm:gap-4">
                <span class="flex items-center gap-1.5">
                  <IFluentCalendarLtr20Regular class="size-3.5 shrink-0" />
                  {{ eventDateLabel(event) }}
                </span>
                <span v-if="event.location" class="flex items-center gap-1.5">
                  <IFluentLocation20Regular class="size-3.5 shrink-0" />
                  <span class="truncate">{{ event.location }}</span>
                </span>
              </div>
            </div>

            <IFluentArrowRight16Regular class="hidden size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-brand sm:block" />
          </SmartLink>
        </li>
      </ul>

      <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <Button as="a" variant="brand" size="public" :href="route('calendar.list', { lang: locale })">
          <IFluentCalendarLtr20Regular class="size-4" />
          {{ $t('Visi renginiai') }}
        </Button>
        <Button variant="brand-outline" size="public" @click="showModal = true">
          <IFluentArrowSync16Regular class="size-4" />
          {{ $t('Sinchronizuoti kalendorių') }}
        </Button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { Button } from '@/Components/ui/button';
import { EyebrowLabel } from '@/Components/Public/Base';
import CalendarSyncModal from '@/Components/Dialogs/CalendarSyncModal.vue';
import Skeleton from '@/Components/ui/skeleton/Skeleton.vue';
import { useCalendarFetch } from '@/Services/ContentService';
import { formatEventDateSpan, formatMonthAbbr } from '@/Utils/IntlTime';
import type { Calendar } from '@/Types/contentParts';
import { LocaleEnum } from '@/Types/enums';
import type { BandResolution } from '@/Components/RichContent/bandLayout';
import { BAND_GROUND_CLASS, BAND_PADDING } from '@/Components/RichContent/sectionClasses';
import RCInlineText from '@/Components/RichContent/Editor/Fullscreen/RCInlineText.vue';

interface CalendarEvent {
  id: number;
  title: string;
  date: string;
  end_date?: string | null;
  location?: string | null;
  is_all_day?: boolean;
  category: { id: number; name: string } | null;
  images: Array<{ url: string }>;
  [key: string]: unknown;
}

const props = defineProps<{
  element?: { json_content: Calendar['json_content']; options: Calendar['options'] };
  /** Server-resolved payload (ContentPartResolver, via RichContentParser's `resolved` prop). */
  resolved?: { type: string; items: CalendarEvent[] } | null;
  /** @deprecated Superseded by `resolved` — only HomePage still supplies this directly. */
  prefetchedCalendar?: CalendarEvent[];
  /** Full-screen editor mode: the title and eyebrow become click-to-edit. Undefined/false
   *  elsewhere. The fetch configuration (limit/category/tenantScope) is edited through
   *  `CalendarBlockToolbar.vue`'s options popover instead. */
  editable?: boolean;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — this type has no per-field claiming, but an
   *  undeclared non-undefined prop would otherwise land on the root as a stray attribute. */
  blockKey?: string;
  /** @see blockKey */
  activeInlineField?: string | null;
  /** This block's resolved chrome (bandLayout.ts) — ground/padding/bleed, and whether
   *  `presentation: 'plain'` dropped the band entirely. See `RCSection.vue`'s identical
   *  prop for the full contract; this display binds `band.classes` straight to its root
   *  instead of forwarding through that component. */
  band?: BandResolution;
}>();

const emit = defineEmits<(e: 'update:element', value: { json_content: Calendar['json_content']; options: Calendar['options'] }) => void>();

const heading = computed(() => props.element?.json_content?.title || $t('Artimiausi renginiai'));
const eyebrowText = computed(() => props.element?.json_content?.eyebrow || $t('Renginių kalendorius'));
// Fallback only matters standalone (no surrounding document to resolve a band from —
// e.g. Storybook, a unit test that doesn't pass `band`); every real render path always
// supplies it once this type is registered `bandRole: 'band'` (Types/index.ts).
const bandClasses = computed(() => props.band?.classes
  ?? ['rc-band', 'relative', 'scroll-mt-32', BAND_PADDING, BAND_GROUND_CLASS.tint, 'rc-viewport']);

function updateTitle(title: string): void {
  if (!props.element) return;
  emit('update:element', { ...props.element, json_content: { ...props.element.json_content, title } });
}

function updateEyebrow(eyebrow: string): void {
  if (!props.element) return;
  emit('update:element', { ...props.element, json_content: { ...props.element.json_content, eyebrow } });
}

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const showModal = ref(false);

const serverCalendar = computed<CalendarEvent[] | undefined>(() => props.resolved?.items ?? props.prefetchedCalendar);

/**
 * Presence, not emptiness: `[]` from the resolver means "the server looked and there is nothing
 * on", which must not send the component off to fetch the same empty answer.
 */
const hasPrefetchedCalendar = computed(() => serverCalendar.value !== undefined);

const {
  calendar: apiCalendar,
  loading: apiLoading,
  error: apiError,
  refresh,
  initializeWithData,
} = useCalendarFetch({
  // Matches CalendarBlockResolver's own default ('all') — this client fetch is a
  // fallback for the (effectively unreachable in normal operation) case where no
  // server-resolved payload arrived at all; `options.tenantScope` only reaches the
  // resolver, not this endpoint.
  allTenants: true,
  skipInitialFetch: hasPrefetchedCalendar.value,
});

// Reactive, not a one-time call at setup: `props.resolved` changes whenever the editor
// re-fetches this block's preview (e.g. after a limit/category change in
// CalendarBlockToolbar.vue) — a plain `if (...) initializeWithData(...)` here only ran
// once at mount, so the display stayed frozen on whatever data it first received and
// never picked up a later options change. This is the actual "changing the amount does
// nothing" bug.
watch(serverCalendar, (events) => {
  if (events) initializeWithData(events);
}, { immediate: true });

const loading = computed(() => !hasPrefetchedCalendar.value && apiLoading.value);
const error = computed(() => !hasPrefetchedCalendar.value && apiError.value);

/**
 * The next few events, soonest first. The band is a way *into* the calendar, not the calendar —
 * a reader who wants the rest follows "Visi renginiai".
 *
 * When server-resolved (the normal case — see `CalendarBlockResolver`), the source is
 * already exactly "upcoming, ascending, capped at `options.limit`"; no client-side
 * re-filtering/re-sorting/re-capping here, since that used to silently override
 * whatever count the author configured (always showing at most a hardcoded 5,
 * regardless of `options.limit`) and, worse, could show the wrong events entirely —
 * `apiCalendar` from the old prefetch path was a large pool ordered newest-first, not
 * upcoming-first. This filter+sort stays only as a safety net for the (effectively
 * unreachable today) live client-fetch fallback, whose own date window may not be
 * pre-sorted.
 */
const upcomingEvents = computed<CalendarEvent[]>(() => {
  const startOfToday = new Date();
  startOfToday.setHours(0, 0, 0, 0);

  return ((apiCalendar.value ?? []) as CalendarEvent[])
    .filter(event => new Date(event.end_date ?? event.date) >= startOfToday)
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
});

const dayOfMonth = (date: string) => new Date(date).getDate();

const eventDateLabel = (event: CalendarEvent) => formatEventDateSpan(
  event.date,
  event.end_date ?? undefined,
  { allDay: event.is_all_day, locale: locale.value },
).primary;

const eventHref = (event: CalendarEvent) => route('calendar.event', { calendar: event.id, lang: locale.value });
</script>
