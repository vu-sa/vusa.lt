<template>
  <div
    class="border border-border bg-secondary/40"
    data-slot="event-details-card"
  >
    <!-- Header -->
    <div class="border-b border-border px-5 py-4">
      <span class="u-eyebrow">{{ $t('Informacija') }}</span>
    </div>

    <!-- Facts list -->
    <dl class="divide-y divide-border">
      <!-- When -->
      <div class="flex items-start gap-3 px-5 py-4">
        <IFluentCalendarLtr20Regular class="mt-0.5 size-4 shrink-0 text-brand" />
        <div class="min-w-0">
          <dt class="text-[0.6875rem] font-bold uppercase tracking-[0.14em] text-muted-foreground">
            {{ $t('Data') }}
          </dt>
          <dd class="mt-0.5 text-sm font-semibold text-foreground">
            {{ dateSpan.primary }}
          </dd>
          <dd v-if="dateSpan.secondary" class="mt-0.5 text-xs text-muted-foreground">
            {{ dateSpan.secondary }}
          </dd>
        </div>
      </div>

      <!-- Where -->
      <div v-if="event.is_remote" class="flex items-start gap-3 px-5 py-4">
        <IFluentGlobe20Regular class="mt-0.5 size-4 shrink-0 text-brand" />
        <div class="min-w-0">
          <dt class="text-[0.6875rem] font-bold uppercase tracking-[0.14em] text-muted-foreground">
            {{ $t('Vieta') }}
          </dt>
          <dd class="mt-0.5 text-sm font-semibold text-foreground">
            {{ $t('Nuotolinis renginys') }}
          </dd>
          <dd v-if="joinUrl" class="mt-2">
            <a
              :href="joinUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand hover:underline"
            >
              <IFluentOpen20Regular class="size-3.5" />
              {{ $t('Prisijungti') }}
            </a>
          </dd>
        </div>
      </div>

      <div v-else-if="location" class="flex items-start gap-3 px-5 py-4">
        <IFluentLocation20Regular class="mt-0.5 size-4 shrink-0 text-brand" />
        <div class="min-w-0 flex-1">
          <dt class="text-[0.6875rem] font-bold uppercase tracking-[0.14em] text-muted-foreground">
            {{ $t('Vieta') }}
          </dt>
          <dd class="mt-0.5 text-sm font-semibold text-foreground">
            {{ location }}
          </dd>

          <EventLocationMap
            v-if="coordinates"
            :latitude="coordinates.lat"
            :longitude="coordinates.lng"
            :label="location"
            class="mt-3"
          />

          <dd class="mt-2">
            <a
              :href="googleMapsUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand hover:underline"
            >
              <IFluentOpen20Regular class="size-3.5" />
              {{ $t('Žiūrėti žemėlapyje') }}
            </a>
          </dd>
        </div>
      </div>

      <!-- Who -->
      <div v-if="organizer" class="flex items-start gap-3 px-5 py-4">
        <IFluentPeopleTeam20Regular class="mt-0.5 size-4 shrink-0 text-brand" />
        <div class="min-w-0">
          <dt class="text-[0.6875rem] font-bold uppercase tracking-[0.14em] text-muted-foreground">
            {{ $t('Organizatorius') }}
          </dt>
          <dd class="mt-0.5 text-sm font-semibold text-foreground">
            {{ organizer }}
          </dd>
        </div>
      </div>

      <!-- Category -->
      <div v-if="event.category?.name" class="flex items-start gap-3 px-5 py-4">
        <IFluentTag20Regular class="mt-0.5 size-4 shrink-0 text-brand" />
        <div class="min-w-0">
          <dt class="text-[0.6875rem] font-bold uppercase tracking-[0.14em] text-muted-foreground">
            {{ $t('Kategorija') }}
          </dt>
          <dd class="mt-0.5 text-sm font-semibold text-foreground">
            {{ event.category.name }}
          </dd>
        </div>
      </div>

      <!-- Status -->
      <div v-if="statusLabel" class="flex items-start gap-3 px-5 py-4">
        <span class="mt-1 relative flex size-2 shrink-0">
          <span
            v-if="isLive"
            class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"
          />
          <span
            class="relative inline-flex size-2 rounded-full"
            :class="isLive ? 'bg-emerald-500' : 'bg-muted-foreground'"
          />
        </span>
        <div class="min-w-0">
          <dt class="text-[0.6875rem] font-bold uppercase tracking-[0.14em] text-muted-foreground">
            {{ $t('Būsena') }}
          </dt>
          <dd class="mt-0.5 text-sm font-semibold text-foreground">
            {{ statusLabel }}
          </dd>
        </div>
      </div>
    </dl>

    <!-- Actions footer -->
    <div v-if="hasActions" class="flex flex-col gap-2 border-t border-border p-4">
      <Button
        v-if="effectiveRegistrationUrl && !isPast"
        as="a"
        :href="effectiveRegistrationUrl"
        target="_blank"
        rel="noopener noreferrer"
        variant="brand"
        size="public"
        class="w-full justify-center"
      >
        <IFluentPlay20Filled v-if="isLive" class="size-4" />
        <IFluentTicket20Regular v-else class="size-4" />
        {{ isLive ? $t('Dalyvauk dabar') : $t('Registruotis') }}
      </Button>

      <Button
        v-if="googleLink"
        as="a"
        :href="googleLink"
        target="_blank"
        rel="noopener noreferrer"
        variant="brand-outline"
        size="public"
        class="w-full justify-center"
      >
        <IFluentCalendarAdd20Regular class="size-4" />
        {{ $t('Į kalendorių') }}
      </Button>

      <Button
        v-if="event.facebook_url"
        as="a"
        :href="event.facebook_url"
        target="_blank"
        rel="noopener noreferrer"
        variant="brand-outline"
        size="public"
        class="w-full justify-center"
      >
        <ISimpleIconsFacebook class="size-4" />
        <span>Facebook</span>
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import IFluentCalendarLtr20Regular from '~icons/fluent/calendar-ltr-20-regular';
import IFluentCalendarAdd20Regular from '~icons/fluent/calendar-add-20-regular';
import IFluentLocation20Regular from '~icons/fluent/location-20-regular';
import IFluentPeopleTeam20Regular from '~icons/fluent/people-team-20-regular';
import IFluentTag20Regular from '~icons/fluent/tag-20-regular';
import IFluentGlobe20Regular from '~icons/fluent/globe-20-regular';
import IFluentOpen20Regular from '~icons/fluent/open-20-regular';
import IFluentPlay20Filled from '~icons/fluent/play-20-filled';
import IFluentTicket20Regular from '~icons/fluent/ticket-20-regular';
import ISimpleIconsFacebook from '~icons/simple-icons/facebook';
import Button from '@/Components/ui/button/Button.vue';
import EventLocationMap from '@/Components/Calendar/EventLocationMap.vue';
import { formatEventDateSpan } from '@/Utils/IntlTime';
import { useEventStatus } from '@/Composables/useEventStatus';
import { LocaleEnum } from '@/Types/enums';

const props = defineProps<{
  event: App.Entities.Calendar;
  googleLink?: string | null;
  /** Server-side geocode of `event.location`; null when unresolvable. */
  coordinates?: { lat: number; lng: number; display_name: string } | null;
  registrationUrl?: string | null;
  isPast?: boolean;
  isLive?: boolean;
  isMeeting?: boolean;
}>();

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const statusHook = useEventStatus(() => props.event, () => Boolean(props.isMeeting));
const isLive = computed(() => (props.isLive !== undefined ? props.isLive : statusHook.isLive.value));
const isPast = computed(() => (props.isPast !== undefined ? props.isPast : statusHook.isPast.value));
const statusLabel = computed(() => statusHook.statusLabel.value);

const location = computed(() => (props.event.location ? String(props.event.location) : ''));
const organizer = computed(() => (props.event.organizer ? String(props.event.organizer) : (props.event.tenant?.fullname || props.event.tenant?.shortname || '')));
/** The call-to-action URL doubles as the join link for a remote event. */
const joinUrl = computed(() => (props.event.cto_url ? String(props.event.cto_url) : ''));
const effectiveRegistrationUrl = computed(() => props.registrationUrl ?? (props.event.cto_url ? String(props.event.cto_url) : null));

const dateSpan = computed(() =>
  formatEventDateSpan(props.event.date, props.event.end_date, {
    allDay: props.event.is_all_day,
    locale: locale.value,
  }),
);

const googleMapsUrl = computed(
  () => `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location.value)}`,
);

const hasActions = computed(() =>
  Boolean(effectiveRegistrationUrl.value && !isPast.value)
  || Boolean(props.googleLink)
  || Boolean(props.event.facebook_url),
);
</script>
