<template>
  <NCard size="small" class="h-full max-w-md rounded-md text-gray-900 shadow-md dark:text-zinc-100 lg:border-2"
    hoverable :segmented="{ footer: 'soft' }">
    <template #cover>
      <img v-if="calendarEvent.images && calendarEvent.images?.length > 0" style="height: 100px"
        class="rounded-t-md object-cover object-center" :src="calendarEvent.images[0].original_url">
    </template>
    <template #header>
      <div class="align-center flex h-12 flex-row items-center p-2">
        <p class="line-clamp-2 w-full text-center text-lg font-bold leading-5">{{
          calendarEvent.title
          }}</p>
      </div>
    </template>
    <div class="mb-2 flex flex-col gap-2 text-sm">
      <div class="inline-flex items-center gap-2">
        <IFluentCalendarLtr24Regular />
        <strong>
          {{
            formatStaticTime(
              new Date(calendarEvent.date),
              {
                year: "numeric",
                month: "numeric",
                day: "numeric",
                hour: "numeric",
                minute: "numeric",
              },
              $page.props.app.locale
            )
          }}
        </strong>
      </div>
      <div v-if="calendarEvent.end_date" class="inline-flex items-center gap-2">
        <IFluentTimer16Regular />
        <span>
          {{ formatStaticTime(
            new Date(calendarEvent.end_date),
            {
              year: "numeric",
              month: "numeric",
              day: "numeric",
              hour: "numeric",
              minute: "numeric",
            },
            $page.props.app.locale) }}
          <!-- {{ calculateEventDuration(calendarEvent) }} -->
        </span>
      </div>
      <div v-if="calendarEvent.location" class="inline-flex items-center gap-2">
        <IFluentLocation24Regular />
        <a class="underline" target="_blank"
          :href="`https://www.google.com/maps/search/?api=1&query=${calendarEvent.location}`">{{
            $page.props.app.locale === "en"
              ? calendarEvent.extra_attributes?.en?.location ??
              calendarEvent.location
              : calendarEvent.location
          }}</a>
      </div>
      <div class="inline-flex items-center gap-2">
        <IFluentPeopleTeam24Regular />
        <span>
          {{ $t("Organizuoja") }}:
          <strong>{{ eventOrganizer }}</strong>
        </span>
      </div>
    </div>

    <template v-if="!hideFooter" #footer>
      <div v-if="googleLink ||
        calendarEvent.url ||
        calendarEvent.extra_attributes?.facebook_url
      " class="flex flex-col justify-center">
        <!-- <div class="flex flex-col justify-center text-xs leading-4"> -->
        <!--   {{ formatRelativeTime(new Date(calendarEvent.date)) }} -->
        <!-- </div> -->

        <NButton v-if="calendarEvent.tenant?.alias === 'mif' &&
          calendarEvent.category === 'freshmen-camps'
        " strong tag="a" round type="primary" @click="showModal = true"><template #icon>
            <IFluentHatGraduation20Filled />
          </template>
          {{ $t("Dalyvauk") }}!
        </NButton>
        <NButton v-if="calendarEvent.url" strong tag="a" round type="primary" target="_blank" :href="calendarEvent.url">
          <template #icon>
            <IFluentHatGraduation20Filled />
          </template>
          {{ $t("Dalyvauk") }}!
        </NButton>
        <!-- <NModal
          v-if="
            calendarEvent.tenant?.alias === 'mif' &&
            calendarEvent.category === 'freshmen-camps'
          "
          v-model:show="showModal"
          class="max-w-xl"
          display-directive="show"
          preset="card"
          title="VU MIF pirmakursių stovyklos registracija"
          :bordered="false"
        >
          <NScrollbar style="max-height: 600px"
            ><NMessageProvider><MIFCampRegistration /></NMessageProvider
          ></NScrollbar>
        </NModal> -->
        <div v-if="calendarEvent.extra_attributes?.facebook_url || googleLink" class="mt-2 flex justify-center gap-2">
          <NButton v-if="calendarEvent.extra_attributes?.facebook_url" title="Facebook" secondary tag="a"
            target="_blank" :href="calendarEvent.extra_attributes?.facebook_url" circle size="small">
            <IMdiFacebook />
          </NButton>
          <NPopover v-if="googleLink">
            {{ $t("Įsidėk į Google kalendorių") }}
            <template #trigger>
              <NButton secondary circle size="small" tag="a" target="_blank" :href="googleLink" @click.stop>
                <template #icon>
                  <IMdiGoogle />
                </template>
              </NButton>
            </template>
          </NPopover>
        </div>
      </div>
    </template>
  </NCard>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref } from "vue";

import { formatStaticTime } from "@/Utils/IntlTime";

const props = defineProps<{
  calendarEvent: App.Entities.Calendar;
  googleLink?: string;
  hideFooter?: boolean;
}>();

const eventOrganizer = computed(() => {
  return (
    props.calendarEvent.extra_attributes?.organizer ??
    props.calendarEvent.tenant?.shortname
  );
});

const showModal = ref(false);

//const timeTillEvent = computed(() => {
//  const date = new Date(props.calendarEvent.date);
//  const now = new Date();
//  // get full days till event
//  const daysTillEvent = Math.floor(
//    (date.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
//  );
//  // get ms till event minus full days
//  const msTillEvent =
//    date.getTime() - now.getTime() - daysTillEvent * 1000 * 60 * 60 * 24;
//  return {
//    days: daysTillEvent,
//    ms: msTillEvent,
//  };
//});

//const calculateEventDuration = (event: App.Entities.Calendar) => {
//  if (!event.end_date) return undefined;
//
//  const startDate = new Date(event.date);
//  const endDate = new Date(event.end_date.replace(/-/g, "/"));
//  const duration = endDate.getTime() - startDate.getTime();
//
//  // if event is longer than 1 day, return days
//  if (duration > 1000 * 60 * 60 * 24) {
//    const days = Math.floor(duration / (1000 * 60 * 60 * 24));
//    return `${days} ${$t("dienos")}`;
//  }
//  // if event is longer than 1 hour, return hours
//  if (duration > 1000 * 60 * 60) {
//    const hours = Math.floor(duration / (1000 * 60 * 60));
//    return `${hours} ${$t("valandos")}`;
//  }
//  // if event is longer than 1 minute, return minutes
//  if (duration > 1000 * 60) {
//    const minutes = Math.floor(duration / (1000 * 60));
//    return `${minutes} ${$t("minutės")}`;
//  }
//};
</script>
