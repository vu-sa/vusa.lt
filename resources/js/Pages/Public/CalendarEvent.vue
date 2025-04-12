<template>
  <article class="mx-auto full-bleed -mt-28">
    <header class="camp-header relative h-120 overflow-hidden" :class="{
      'py-8 text-white': !hasNoImage,
      'py-6': hasNoImage,
    }" :style="headerImageStyle">
      <div class="absolute inset-0 bg-gradient-to-b from-black/30 to-black/70" />
      <div class="mx-auto flex h-full max-w-7xl">
        <div class="relative z-10 h-fit self-end px-8">
          <div class="flex items-center gap-2 text-sm font-medium text-white/80">
            <Link :href="route('home', { lang: $page.props.app.locale, subdomain: 'www' })" class="hover:text-red-300">
            {{ $t("Pradžia") }}
            </Link>
            <span>&raquo;</span>
            <Link :href="route('calendar.list', { lang: $page.props.app.locale })" class="hover:text-red-300">
            {{ $t("Kalendorius") }}
            </Link>
            <span>&raquo;</span>
            <span>{{ event.title }}</span>
          </div>

          <h1 class="mt-4 flex items-center text-4xl font-extrabold text-white lg:text-5xl">
            <span>{{ event.title }}</span>
          </h1>

          <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-white/90">
            <div class="inline-flex items-center gap-2">
              <IFluentCalendarLtr20Regular />
              <span>
                {{ formatStaticTime(new Date(event.date), { dateStyle: 'medium', timeStyle: 'short' }, $page.props.app.locale) }}
                <template v-if="event.end_date">
                  - {{ formatStaticTime(new Date(event.end_date), 
                     isSameDay(new Date(event.date), new Date(event.end_date)) ? { timeStyle: 'short' } : { dateStyle: 'medium', timeStyle: 'short' }, 
                     $page.props.app.locale) }}
                </template>
              </span>
            </div>
            <div v-if="event.location" class="inline-flex items-center gap-2">
              <IFluentLocation20Regular />
              <span>{{ event.location }}</span>
            </div>
            <div class="inline-flex items-center gap-2">
              <IFluentPeopleTeam20Regular />
              <span>{{ event.organizer || event.tenant?.shortname }}</span>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="mx-auto mt-8 grid max-w-7xl gap-12 px-4 lg:grid-cols-3 lg:px-16">
      <aside class="flex flex-col gap-6 order-2">
        <div class="sticky top-32 flex flex-col gap-6">
          <div v-if="event.url || googleLink"
            class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <NButton v-if="event.url" block type="primary" tag="a" :href="event.url" target="_blank">
              <template #icon>
                <IFluentPersonAdd20Regular />
              </template>
              {{ $t("Dalyvauk renginyje") }}
            </NButton>

            <NButton v-if="googleLink" block secondary tag="a" :href="googleLink" target="_blank">
              <template #icon>
                <IMdiGoogle />
              </template>
              {{ $t("Įtraukti į Google kalendorių") }}
            </NButton>

            <NButton v-if="event.facebook_url" block secondary tag="a" :href="event.facebook_url" target="_blank">
              <template #icon>
                <IMdiFacebook />
              </template>
              {{ $t("Facebook") }}
            </NButton>
          </div>

          <div class="hidden flex-col items-center gap-2 p-4 lg:flex">
            <h3 class="mb-2 text-lg font-bold">
              {{ $t("Renginių kalendorius") }}
            </h3>
            <EventCalendar :calendar-events="calendar" :locale="$page.props.app.locale" />
          </div>
        </div>
      </aside>
      <div class="lg:col-span-2">
        <div class="typography">
          <h2 class="mb-4 text-2xl font-bold">
            {{ $t("Apie renginį") }}
          </h2>
          <div class="prose prose-lg max-w-none dark:prose-invert" v-html="event.description" />

          <iframe v-if="event.video_url" class="my-8 aspect-video h-auto w-full rounded-xl shadow-md" width="560"
            height="315" :src="`https://www.youtube-nocookie.com/embed/${event.video_url}`" title="YouTube video player"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen />

          <div v-if="event.images && event.images.length > 0" class="mt-8">
            <h3 class="mb-4 text-xl font-bold">
              {{ $t("Nuotraukos") }}
            </h3>
            <NImageGroup :show-toolbar="true">
              <NSpace size="large">
                <NImage v-for="image in event.images" :key="image.id" width="200" :src="image.original_url"
                  class="rounded-lg shadow-sm transition-all hover:shadow-md hover:brightness-105" lazy
                  object-fit="cover" />
              </NSpace>
            </NImageGroup>
          </div>

          <div class="mt-8 flex flex-wrap items-center gap-4">
            <h3 class="text-xl font-bold">
              {{ $t("Dalinkis") }}:
            </h3>
            <NButton secondary circle @click="shareEvent('facebook')">
              <IMdiFacebook />
            </NButton>
            <NButton secondary circle @click="shareEvent('twitter')">
              <IMdiTwitter />
            </NButton>
            <NButton secondary circle @click="shareEvent('linkedin')">
              <IMdiLinkedin />
            </NButton>
            <NButton secondary circle @click="shareEvent('email')">
              <IFluentMail16Regular />
            </NButton>
          </div>
        </div>

        <div v-if="upcomingEvents.length > 0" class="mt-8">
          <h2 class="mb-4 text-2xl font-bold">
            {{ $t("Kiti renginiai") }}
          </h2>
          <div class="grid gap-4 sm:grid-cols-2">
            <CalendarCard v-for="upcomingEvent in upcomingEvents" :key="upcomingEvent.id"
              :calendar-event="upcomingEvent" :google-link="generateGoogleLink(upcomingEvent)" :hide-footer="false" />
          </div>
        </div>
      </div>
    </section>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";

import CalendarCard from "@/Components/Calendar/CalendarCard.vue";
import EventCalendar from "@/Components/Calendar/EventCalendar.vue";
import { formatStaticTime } from "@/Utils/IntlTime";
import { Link } from "@inertiajs/vue3";

const props = defineProps<{
  event: App.Entities.Calendar;
  calendar: App.Entities.Calendar[];
  googleLink: string;
}>();

// Check if image array is empty
const hasNoImage = computed(() => {
  return !props.event.images || props.event.images.length === 0;
});

// Generate header background style
const headerImageStyle = computed(() => {
  if (hasNoImage.value) {
    return {
      backgroundColor: '#1e1e24'
    };
  }

  return {
    backgroundImage: `url(${props.event.images[0].original_url})`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    backgroundRepeat: 'no-repeat',
  };
});

// Get upcoming events (excluding current event)
const upcomingEvents = computed(() => {
  if (!props.calendar) return [];

  const now = new Date();
  return props.calendar
    .filter(e => e.id !== props.event.id && new Date(e.date) >= now)
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    .slice(0, 4);
});

// Social media sharing
// Helper function to check if two dates are on the same day
const isSameDay = (date1: Date, date2: Date): boolean => {
  return date1.getFullYear() === date2.getFullYear() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getDate() === date2.getDate();
};

const shareEvent = (platform: string) => {
  const url = window.location.href;
  const title = props.event.title;
  
  // Create a more compact date representation for sharing
  let dateText = formatStaticTime(new Date(props.event.date), { dateStyle: 'medium' }, 'lt');
  if (props.event.end_date) {
    const endDate = new Date(props.event.end_date);
    const startDate = new Date(props.event.date);
    if (isSameDay(startDate, endDate)) {
      dateText += ` ${formatStaticTime(startDate, { timeStyle: 'short' }, 'lt')} - ${formatStaticTime(endDate, { timeStyle: 'short' }, 'lt')}`;
    } else {
      dateText += ` - ${formatStaticTime(endDate, { dateStyle: 'medium' }, 'lt')}`;
    }
  }
  
  const text = `${title} - ${dateText}`;

  switch (platform) {
    case 'facebook':
      window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
      break;
    case 'twitter':
      window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank');
      break;
    case 'linkedin':
      window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`, '_blank');
      break;
    case 'email':
      window.open(`mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(`${text}\n\n${url}`)}`, '_blank');
      break;
  }
};

// Generate Google Calendar link for events
const generateGoogleLink = (event: App.Entities.Calendar) => {
  if (!event) return '';

  const startDate = new Date(event.date);
  const endDate = event.end_date ? new Date(event.end_date) : new Date(startDate.getTime() + 60 * 60 * 1000); // Default 1 hour

  const formatGoogleDate = (date: Date) => {
    return date.toISOString().replace(/-|:|\.\d+/g, '');
  };

  const details = event.description ?
    `${event.description.replace(/<[^>]*>/g, ' ')}` :
    '';

  return `https://www.google.com/calendar/render?action=TEMPLATE` +
    `&text=${encodeURIComponent(event.title)}` +
    `&dates=${formatGoogleDate(startDate)}/${formatGoogleDate(endDate)}` +
    `&details=${encodeURIComponent(details)}` +
    (event.location ? `&location=${encodeURIComponent(event.location)}` : '');
};
</script>

<style scoped>
.camp-header {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}
</style>
