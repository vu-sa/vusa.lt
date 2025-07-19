<template>
  <AdminContentPage>
    <Head :title="$t('Atstovavimas')" />
    
    <!-- Hero section with greeting and overview -->
    <section class="relative mb-10 overflow-hidden rounded-lg bg-gradient-to-br from-primary/10 to-primary/5 px-8 py-7 shadow-sm dark:from-primary/20 dark:to-background">
      <div>
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl text-primary dark:text-primary-foreground/90">
          {{ $t('Atstovavimas') }}
        </h1>
        <p class="mt-3 max-w-xl text-muted-foreground">
          {{ $t('Susitikimų, tikslų ir atstovavimo veiklų stebėjimas') }}
        </p>
      </div>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
      <!-- Upcoming meetings card -->
      <Card class="flex flex-col">
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2">
            <component :is="Icons.MEETING" class="h-5 w-5 text-primary" />
            {{ $t('Tavo artėjantys susitikimai') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="flex-1">
          <span class="mb-4 inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="upcomingMeetings.length" />
          </span>
          <p v-if="upcomingMeetings.length > 0" class="mt-4">
            {{ $t('Kitas susitikimas') }}:
            <Link class="font-bold text-primary hover:underline" :href="route('meetings.show', upcomingMeetings[0].id)">
              {{ formatStaticTime(new Date(upcomingMeetings[0].start_time), {
                month: 'long', day: 'numeric'
              }) }}
            </Link> 
            ({{ upcomingMeetings[0].institutions?.[0].name }})
          </p>
          <p v-else>
            {{ $t('Artėjančių susitikimų nėra! Nepamiršk, kad gali sukurti susitikimą į priekį!') }} 🎉
          </p>
        </CardContent>
        <CardFooter class="flex gap-2 border-t bg-muted/50 p-4">
          <Button variant="default" size="sm" @click="showMeetingModal = true">
            <component :is="Icons.MEETING" class="mr-2 h-4 w-4" />
            {{ $t("Pranešti") }}
          </Button>
          <NewMeetingModal :show-modal="showMeetingModal" @close="showMeetingModal = false" />
          <Button size="sm" variant="outline" @click="showAllMeetingModal = true">
            <component :is="Icons.MEETING" class="mr-2 h-4 w-4" />
            {{ $t('Rodyti visus') }}
          </Button>
          <CardModal v-model:show="showAllMeetingModal" :title="$t('Visi susitikimai')" @close="showAllMeetingModal = false">
            <NDataTable :data="meetings" :columns="allMeetingColumns" :pagination="{ pageSize: 7 }" />
          </CardModal>
        </CardFooter>
      </Card>

      <!-- Your institutions card -->
      <Card class="flex flex-col">
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2">
            <component :is="Icons.INSTITUTION" class="h-5 w-5 text-primary" />
            {{ $t('Tavo institucijos') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="flex-1">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <p class="mb-1 text-muted-foreground">
                {{ $t('Visos institucijos') }}
              </p>
            </div>
            <p v-if="$page.props.app.locale === 'lt'" class="leading-tight text-muted-foreground">
              Institucijos su <strong>artėjančiais</strong> posėdžiais
            </p>
            <p v-else class="leading-tight text-muted-foreground">
              Institutions with <strong>upcoming</strong> meetings
            </p>
            <span class="mb-2 inline-block text-4xl font-bold">
              <NNumberAnimation :from="0" :to="institutions.length" />
            </span>
            <span class="mb-2 inline-block text-4xl font-bold">
              <NNumberAnimation :from="0" :to="hasUpcomingMeetingCount" />
            </span>
          </div>
        </CardContent>
        <CardFooter class="flex gap-2 border-t bg-muted/50 p-4">
          <Button size="sm" variant="outline" @click="showAllInstitutionModal = true">
            <component :is="Icons.INSTITUTION" class="mr-2 h-4 w-4" />
            {{ $t('Rodyti visas') }}
          </Button>
          <CardModal v-model:show="showAllInstitutionModal" :title="$t('Visos institucijos')"
            @close="showAllInstitutionModal = false">
            <NDataTable :data="institutions" :columns="allInstitutionColumns" :pagination="{ pageSize: 7 }" />
          </CardModal>
        </CardFooter>
      </Card>

      <!-- Calendar card -->
      <div class="my-calendar rounded-md shadow-sm overflow-hidden">
        <Calendar 
          :is-dark 
          :initial-page 
          :locale="{
            id: $page.props.app.locale,
            firstDayOfWeek: 2,
            masks: { weekdays: 'WW' },
          }" 
          color="red" 
          borderless 
          class="rounded-md" 
          :attributes="calendarAttributes"
        >
          <template #day-popover="{ attributes, dayTitle }">
            <div class="max-w-md">
              <div class="mb-2 text-center text-xs font-semibold text-gray-300 dark:text-zinc-700">
                {{ dayTitle }}
              </div>
              <PopoverRow v-for="attr in attributes" :key="attr.key" :attribute="attr">
                <div class="inline-flex items-center gap-2">
                  <Link :href="route('meetings.show', attr.key)">
                    {{ attr.popover.label }}
                  </Link>
                </div>
              </PopoverRow>
            </div>
          </template>
          <template #footer>
            <div class="w-full px-5 py-4">
              <Button variant="default" size="default" style="width: 100%" @click="showMeetingModal = true">
                <component :is="Icons.MEETING" class="mr-2 h-4 w-4" />
                {{ $t('Sukurti') }}
              </Button>
            </div>
          </template>
        </Calendar>
      </div>
    </div>

    <Separator v-if="tenants.length > 0" class="my-10" />

    <!-- Tenant representation section -->
    <section v-if="tenants.length > 0" class="space-y-8">
      <div class="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
        <h2 class="text-xl font-semibold tracking-tight">{{ $t('Atstovavimas padalinyje') }}</h2>
        <div class="flex flex-wrap items-center gap-4">
          <NSelect :value="providedTenant?.id" filterable
            :options="tenants.map(tenant => ({ label: tenant.shortname, value: tenant.id }))"
            @update:value="handleTenantUpdateValue" />
          <SmartLink :href="route('dashboard.atstovavimas.summary')">
            <Button size="sm" variant="outline" class="gap-1">
              <component :is="Icons.CALENDAR" class="h-4 w-4" />
              {{ $t('Žiūrėti pokyčius pagal dienas') }}
            </Button>
          </SmartLink>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <!-- Representatives card -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <component :is="Icons.USER" class="h-5 w-5 text-primary" />
              {{ $t('Atstovai padalinyje') }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 gap-2">
              <p v-for="dutyType in dutyTypesWithUserCounts" :key="dutyType.title" class="mb-1 text-muted-foreground">
                {{ dutyType.title }}
              </p>
              <span v-for="dutyType in dutyTypesWithUserCounts" :key="dutyType.slug"
                class="mb-4 inline-block text-4xl font-bold">
                <NNumberAnimation :from="0" :to="dutyType.count" />
              </span>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-muted/50 p-3">
            <Button size="sm" variant="outline" @click="showAllDutyModal = true">
              <component :is="Icons.USER" class="mr-2 h-4 w-4" />
              {{ $t('Rodyti visus') }}
            </Button>
            <CardModal v-model:show="showAllDutyModal" class="max-w-5xl" :title="$t('Visos pareigybės')"
              @close="showAllDutyModal = false">
              <NDataTable :max-height="450" :data="allDuties" :columns="allDutyColumns" :pagination="{ pageSize: 7 }" />
            </CardModal>
          </CardFooter>
        </Card>

        <!-- Meeting statistics card -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle>{{ $t('Visų susitikimų statistika') }}</CardTitle>
            <Button variant="ghost" size="icon" @click="showMeetingBarPlot = true">
              <FullScreenIcon class="h-4 w-4" />
            </Button>
          </CardHeader>
          <CardContent>
            <Suspense>
              <MeetingBarPlot :all-tenant-meetings :width="320" :height="190" />
              <template #fallback>
                <div class="h-[190px] w-full flex items-center justify-center">
                  <div class="flex flex-col items-center gap-4">
                    <div class="h-8 w-8 rounded bg-zinc-300 dark:bg-zinc-600 animate-pulse"></div>
                    <div class="space-y-2">
                      <div class="h-3 w-36 rounded bg-zinc-300 dark:bg-zinc-600 animate-pulse"></div>
                      <div class="h-2 w-24 rounded bg-zinc-300 dark:bg-zinc-600 animate-pulse mx-auto"></div>
                    </div>
                  </div>
                </div>
              </template>
            </Suspense>
          </CardContent>
          <CardModal v-model:show="showMeetingBarPlot" :title="$t('Visų susitikimų statistika')"
            @close="showMeetingBarPlot = false">
            <MeetingBarPlot :all-tenant-meetings />
          </CardModal>
        </Card>

        <!-- Tenant calendar -->
        <div class="my-calendar">
          <Calendar :is-dark :initial-page :locale="{
            id: $page.props.app.locale,
            firstDayOfWeek: 2,
            masks: { weekdays: 'WW' },
          }" color="red" borderless class="rounded-md shadow-xs" :attributes="tenantCalendarAttributes">
            <template #day-popover="{ attributes, dayTitle }">
              <div class="max-w-md">
                <div class="mb-1 text-center text-xs font-semibold text-gray-300 dark:text-zinc-700">
                  {{ dayTitle }}
                </div>
                <PopoverRow v-for="attr in attributes" :key="attr.key" :attribute="attr">
                  <div class="inline-flex items-center gap-2">
                    <Link :href="route('meetings.show', attr.key)">
                      {{ attr.popover.label }}
                    </Link>
                  </div>
                </PopoverRow>
              </div>
            </template>
          </Calendar>
        </div>
      </div>
    </section>
  </AdminContentPage>
</template>

<script setup lang="ts">
import "v-calendar/style.css";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, h, ref } from 'vue';
import { useDark } from "@vueuse/core";
import { trans as $t } from "laravel-vue-i18n";

// UI components
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import NewMeetingModal from '@/Components/Modals/NewMeetingModal.vue';
import CardModal from "@/Components/Modals/CardModal.vue";
import { Calendar, PopoverRow } from "v-calendar";
import MeetingBarPlot from "@/Components/Graphs/MeetingBarPlot.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";

// Shadcn UI components
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";

// Icons
import Icons from "@/Types/Icons/filled";
import { Maximize2 as FullScreenIcon } from "lucide-vue-next";

// Utils
import { formatStaticTime } from '@/Utils/IntlTime';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

// Setup breadcrumbs for the atstovavimas page
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createBreadcrumbItem($t('Atstovavimas'), undefined, Icons.MEETING)
]);

const props = defineProps<{
  user: App.Entities.User;
  providedTenant?: App.Entities.Tenant;
  tenants: App.Entities.Tenant[];
}>();

const showMeetingModal = ref(false);
const showAllMeetingModal = ref(false);
const showAllInstitutionModal = ref(false);
const showAllDutyModal = ref(false);
const showMeetingBarPlot = ref(false);

const isDark = useDark();

const initialPage = {
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
  day: new Date().getDate(),
};

// Filter institutions and handle nulls
const institutions = props.user.current_duties.map(duty => {
  if (!duty.institution) {
    return null;
  }

  return {
    ...duty.institution, 
    hasUpcomingMeetings: duty.institution?.meetings.some(meeting => new Date(meeting.start_time) > new Date())
  };
}).filter(Boolean).filter((institution, index, self) =>
  index === self.findIndex((t) => (t.id === institution.id))
);

const hasUpcomingMeetingCount = computed(() => institutions.filter(institution => institution.hasUpcomingMeetings).length);

const meetings = institutions.map(institution => institution.meetings).flat();

// Get meetings from related institutions
const relatedInstitutionsMeetingsCalendarAttributes = institutions.map(institution => {
  return institution.relatedInstitutions?.map(relatedInstitution => {
    return relatedInstitution.meetings?.map(meeting => {
      return {
        dates: new Date(meeting.start_time),
        dot: 'blue',
        popover: {
          label: relatedInstitution.name,
          isInteractive: true,
        },
        key: meeting.id,
      };
    });
  }).flat();
}).flat().filter(Boolean);

const upcomingMeetings = meetings.filter(meeting => new Date(meeting.start_time) > new Date())
  .sort((a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime());

const calendarAttributes = meetings.map((meeting) => {
  let calendarAttrObject = {
    dates: new Date(meeting.start_time),
    dot: 'red',
    popover: {
      label: meeting.institutions[0].name,
      isInteractive: true,
    },
    key: meeting.id,
  };
  return calendarAttrObject;
});

calendarAttributes.push({
  dates: new Date(),
  highlight: { color: "red", fillMode: "outline" },
  order: 1,
});

// Push related institutions meetings to the calendar attributes
calendarAttributes.push(...relatedInstitutionsMeetingsCalendarAttributes);


const allMeetingColumns = [
  {
    title() {
      return $t('Institucija');
    },
    key: 'institution.title',
    render(row) {
      return h(Link, { href: route('institutions.show', row.institutions[0].id), }, row.institutions[0].name);
    },
  },
  {
    title() {
      return $t('Susitikimo pradžia');
    },
    key: 'start_time',
    render: (row) => h(Link, { href: route('meetings.show', row.id) }, formatStaticTime(new Date(row.start_time), { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric' })),
    sorter: (a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime(),
    defaultSortOrder: 'descend',
  },
];

const allInstitutionColumns = [
  {
    title() {
      return $t('forms.fields.title');
    },
    key: 'name',
    render(row) {
      return h(Link, { href: route('institutions.show', row.id) }, row.name);
    },
  },
  {
    title() {
      return $t('Artėjantys posėdžiai');
    },
    key: 'meetings',
    render(row) {
      return row.hasUpcomingMeetings ? $t('Taip') : $t('Ne');
    },
  },
];

const handleTenantUpdateValue = (value) => {
  router.reload({ data: { tenant_id: value } });
};

const allTenantMeetings = computed(() => props.providedTenant?.institutions.map(institution => {
  return institution.meetings?.map(meeting => {
    return {
      institution: institution.name,
      start_time: new Date(meeting.start_time),
      id: meeting.id,
    };
  });
}).flat());

const tenantCalendarAttributes = computed(() => {
  const meetings = allTenantMeetings.value?.map((meeting) => {
    let calendarAttrObject = {
      dates: meeting?.start_time,
      dot: 'red',
      popover: {
        label: meeting?.institution,
        isInteractive: true,
      },
      key: meeting?.id,
    };
    return calendarAttrObject;
  });

  meetings?.push({
    dates: new Date(),
    highlight: { color: "red", fillMode: "outline" },
    order: 1,
  });

  return meetings;
});

// Check types of each duty, and duties.current_users amount
const dutyTypesWithUserCounts = computed(() => props.providedTenant?.institutions?.reduce((acc, institution) => {
  institution.duties?.forEach(duty => {
    duty.types?.forEach(type => {
      if (!type.title) {
        return;
      }

      const existingType = acc.find(t => t.title === type.title);

      if (existingType) {
        existingType.count += duty.current_users?.length ?? 0;
      } else {
        acc.push({
          title: type.title,
          count: duty.current_users.length,
          slug: type.slug
        });
      }
    });
  });

  return acc;
}, [])?.sort((a, b) => b.count - a.count).filter(type => type.count > 0 && type.slug !== 'kuratoriai').slice(0, 2));

const allDuties = computed(() => props.providedTenant?.institutions?.map(institution => {
  return institution.duties?.map(duty => {
    return {
      institution_id: institution.id,
      institution: institution.name,
      duty_id: duty.id,
      title: duty.name,
      users: duty.current_users?.map(user => user.name).join(', '),
      type: duty.types?.map(type => type.title).join(', '),
    };
  });
}).flat());

const allDutyColumns = [
  {
    title() {
      return $t('Pareigybė');
    },
    key: 'title',
    sorter: (a, b) => a.title.localeCompare(b.title),
    defaultSortOrder: 'ascend',
    render(row) {
      return h(Link, { href: route('duties.show', row.duty_id) }, row.title);
    },
  },
  {
    title() {
      return $t('Institucija');
    },
    key: 'institution',
    render(row) {
      return h(Link, { href: route('institutions.show', row.institution_id) }, row.institution);
    },
  },
  {
    title() {
      return $t('Vartotojai');
    },
    key: 'users',
  },
  {
    title() {
      return $t('Tipas');
    },
    key: 'type',
  },
];
</script>

<style scoped>
.my-calendar :deep(.vc-container.vc-dark) {
  background-color: #29292e;
}

.my-calendar :deep(button.vc-arrow) {
  background-color: transparent;
}

.vc-container {
  font-family: "Inter", sans-serif !important;
  border: 0 !important;
}
</style>
