<template>
  <!-- Minimal variant: no photo, clean editorial title header -->
  <header
    v-if="variant === 'minimal'"
    data-slot="event-hero"
    class="band-masthead rc-viewport -mt-4 border-b border-border md:-mt-6 lg:-mt-8"
  >
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-6 sm:py-12 lg:px-8 lg:py-16">
      <!-- Inline breadcrumbs -->
      <div
        v-if="$slots.breadcrumbs || showBreadcrumbs"
        class="mb-8 max-w-full"
      >
        <slot name="breadcrumbs">
          <PublicBreadcrumbs variant="inline" />
        </slot>
      </div>

      <div class="flex items-end gap-5">
        <!-- Date plate: hidden on mobile, visible from sm up -->
        <div
          v-if="event.date"
          class="hidden shrink-0 flex-col items-center border border-border bg-secondary/60 px-5 py-3 text-foreground sm:flex"
          data-slot="hero-date-plate"
        >
          <span class="text-4xl font-bold leading-none tabular-nums lg:text-5xl">{{ dayNumber }}</span>
          <span class="mt-1.5 font-mono text-[0.6875rem] font-bold uppercase tracking-[0.18em] text-brand">
            {{ monthAbbr }}
          </span>
        </div>

        <!-- Title lockup -->
        <div class="border-l-2 border-brand pl-5 sm:pl-7">
          <div class="flex flex-wrap items-center gap-2">
            <span
              v-if="tagLabel"
              class="text-[0.6875rem] font-bold uppercase tracking-[0.2em] text-brand"
              data-slot="hero-tag"
            >
              {{ tagLabel }}
            </span>
            <span v-if="tagLabel && statusLabel" class="text-xs text-muted-foreground/60">·</span>
            <span
              v-if="statusLabel"
              class="inline-flex items-center gap-1.5 font-mono text-[0.6875rem] font-semibold uppercase tracking-wider text-muted-foreground"
              data-slot="hero-status"
            >
              <span
                class="size-1.5 rounded-full"
                :class="isLive ? 'bg-emerald-500 animate-pulse' : (isPast ? 'bg-zinc-400' : 'bg-brand')"
              />
              {{ statusLabel }}
            </span>
          </div>
          <h1 class="u-display mt-2 max-w-4xl text-pretty text-3xl font-bold text-foreground sm:text-4xl lg:text-5xl">
            {{ eventTitle }}
          </h1>

          <div v-if="$slots.actions" class="pt-4">
            <slot name="actions" :on-image="false" />
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Split variant: photo beside content -->
  <header
    v-else-if="variant === 'split'"
    data-slot="event-hero"
    class="band-masthead rc-viewport -mt-4 border-b border-border md:-mt-6 lg:-mt-8"
  >
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-6 sm:py-12 lg:px-8 lg:py-16">
      <!-- Inline breadcrumbs -->
      <div
        v-if="$slots.breadcrumbs || showBreadcrumbs"
        class="mb-8 max-w-full"
      >
        <slot name="breadcrumbs">
          <PublicBreadcrumbs variant="inline" />
        </slot>
      </div>

      <div class="grid gap-8 md:grid-cols-2 md:items-center">
        <div class="flex items-end gap-5">
          <!-- Date plate: hidden on mobile, visible from sm up -->
          <div
            v-if="event.date"
            class="hidden shrink-0 flex-col items-center border border-border bg-secondary/60 px-5 py-3 text-foreground sm:flex"
            data-slot="hero-date-plate"
          >
            <span class="text-4xl font-bold leading-none tabular-nums lg:text-5xl">{{ dayNumber }}</span>
            <span class="mt-1.5 font-mono text-[0.6875rem] font-bold uppercase tracking-[0.18em] text-brand">
              {{ monthAbbr }}
            </span>
          </div>

          <!-- Title lockup -->
          <div class="border-l-2 border-brand pl-5 sm:pl-7">
            <div class="flex flex-wrap items-center gap-2">
              <span
                v-if="tagLabel"
                class="text-[0.6875rem] font-bold uppercase tracking-[0.2em] text-brand"
                data-slot="hero-tag"
              >
                {{ tagLabel }}
              </span>
              <span v-if="tagLabel && statusLabel" class="text-xs text-muted-foreground/60">·</span>
              <span
                v-if="statusLabel"
                class="inline-flex items-center gap-1.5 font-mono text-[0.6875rem] font-semibold uppercase tracking-wider text-muted-foreground"
                data-slot="hero-status"
              >
                <span
                  class="size-1.5 rounded-full"
                  :class="isLive ? 'bg-emerald-500 animate-pulse' : (isPast ? 'bg-zinc-400' : 'bg-brand')"
                />
                {{ statusLabel }}
              </span>
            </div>
            <h1 class="u-display mt-2 text-pretty text-3xl font-bold text-foreground sm:text-4xl lg:text-5xl">
              {{ eventTitle }}
            </h1>

            <div v-if="$slots.actions" class="pt-4">
              <slot name="actions" :on-image="false" />
            </div>
          </div>
        </div>

        <!-- Photo beside content -->
        <div
          v-if="heroImageUrl"
          class="relative aspect-[16/10] overflow-hidden border border-border bg-secondary"
        >
          <img
            :src="heroImageUrl"
            :alt="eventTitle"
            class="size-full object-cover grayscale"
            :style="{ objectPosition: event.main_image_focal_point ?? '50% 30%' }"
            loading="eager"
          >
        </div>
      </div>
    </div>
  </header>

  <!-- Card variant: full-bleed editorial photo hero with dual scrims -->
  <header
    v-else
    data-slot="event-hero"
    class="rc-viewport relative isolate -mt-4 overflow-hidden border-b border-border bg-secondary md:-mt-6 lg:-mt-8"
  >
    <div class="relative flex min-h-[22rem] w-full flex-col justify-end overflow-hidden sm:min-h-[24rem] lg:min-h-[28rem]">
      <!-- Background photo -->
      <img
        v-if="heroImageUrl"
        :src="heroImageUrl"
        :alt="eventTitle"
        class="absolute inset-0 size-full object-cover grayscale"
        :style="{ objectPosition: event.main_image_focal_point ?? '50% 30%' }"
        loading="eager"
      >

      <!-- Scrim: dual gradients fading into the page background token -->
      <div class="absolute inset-0 bg-gradient-to-r from-background via-background/85 to-background/30" />
      <div class="absolute inset-0 bg-gradient-to-t from-background via-transparent to-transparent" />

      <!-- Overlaid container: in-flow flex container aligned with max-w-7xl -->
      <div class="relative z-10 mx-auto flex size-full max-w-7xl flex-1 flex-col justify-between px-5 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-14">
        <!-- Inline breadcrumbs -->
        <div
          v-if="$slots.breadcrumbs || showBreadcrumbs"
          class="mb-8 max-w-full"
        >
          <slot name="breadcrumbs">
            <PublicBreadcrumbs variant="inline" />
          </slot>
        </div>

        <div class="flex items-end gap-5">
          <!-- Date plate: hidden on mobile, visible from sm up -->
          <div
            v-if="event.date"
            class="hidden shrink-0 flex-col items-center border border-brand bg-background/80 px-5 py-3 text-foreground backdrop-blur-sm sm:flex"
            data-slot="hero-date-plate"
          >
            <span class="text-4xl font-bold leading-none tabular-nums lg:text-5xl">{{ dayNumber }}</span>
            <span class="mt-1.5 text-[0.6875rem] font-bold uppercase tracking-[0.18em] text-brand">
              {{ monthAbbr }}
            </span>
          </div>

          <!-- Title lockup -->
          <div class="border-l-2 border-brand pl-5 sm:pl-7">
            <div class="flex flex-wrap items-center gap-2">
              <span
                v-if="tagLabel"
                class="text-[0.6875rem] font-bold uppercase tracking-[0.2em] text-brand"
                data-slot="hero-tag"
              >
                {{ tagLabel }}
              </span>
              <span v-if="tagLabel && statusLabel" class="text-xs text-muted-foreground/60">·</span>
              <span
                v-if="statusLabel"
                class="inline-flex items-center gap-1.5 font-mono text-[0.6875rem] font-semibold uppercase tracking-wider text-muted-foreground"
                data-slot="hero-status"
              >
                <span
                  class="size-1.5 rounded-full"
                  :class="isLive ? 'bg-emerald-500 animate-pulse' : (isPast ? 'bg-zinc-400' : 'bg-brand')"
                />
                {{ statusLabel }}
              </span>
            </div>
            <h1 class="u-display mt-2 max-w-3xl text-pretty text-3xl text-foreground sm:text-4xl lg:text-6xl">
              {{ eventTitle }}
            </h1>

            <div v-if="$slots.actions" class="pt-4">
              <slot name="actions" :on-image="true" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import { useEventStatus } from '@/Composables/useEventStatus';
import { formatMonthAbbr, formatStaticTime } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';

interface Props {
  event: App.Entities.Calendar;
  /** The event announces a meeting; the status badge then says so. */
  isMeeting?: boolean;
  showBreadcrumbs?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  // eslint-disable-next-line vue/no-boolean-default
  showBreadcrumbs: true,
});

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const { isLive, isPast, statusLabel } = useEventStatus(() => props.event, () => !!props.isMeeting);

type HeroVariant = 'card' | 'split' | 'minimal';

const heroImageUrl = computed(() => props.event.main_image_url || null);

const variant = computed<HeroVariant>(() => {
  const style = props.event.hero_style;
  if (style === 'minimal') return 'minimal';
  if (style === 'split') return heroImageUrl.value ? 'split' : 'minimal';
  return 'card';
});

const eventTitle = computed(() =>
  Array.isArray(props.event.title) ? props.event.title.join(' ') : String(props.event.title ?? ''),
);

const dayNumber = computed(() =>
  props.event.date ? formatStaticTime(new Date(props.event.date), { day: 'numeric' }, locale.value) : '',
);

const monthAbbr = computed(() =>
  props.event.date ? formatMonthAbbr(new Date(props.event.date), locale.value) : '',
);

const tagLabel = computed(() => {
  if (props.event.category?.name) return props.event.category.name;
  if (props.isMeeting) return $t('Posėdis');
  if (props.event.tenant?.shortname) return props.event.tenant.shortname;
  return '';
});
</script>
