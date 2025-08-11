<template>
  <AdminContentPage>

    <Head :title="$t('Atstovavimas')" />

    <!-- Hero section with greeting and overview -->
    <section
      class="relative mb-10 overflow-hidden rounded-lg bg-gradient-to-br from-primary/10 to-primary/5 px-8 py-7 shadow-sm dark:from-primary/20 dark:to-background">
      <div>
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl text-primary dark:text-primary-foreground/90">
          {{ $t('Atstovavimas') }}
        </h1>
        <p class="mt-3 max-w-xl text-muted-foreground">
          {{ $t('Susitikimų, tikslų ir atstovavimo veiklų stebėjimas') }}
        </p>
      </div>
    </section>

    <!-- Personal Overview Section -->
    <section class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold tracking-tight">
          {{ $t('Personal Overview') }}
        </h2>
        <Button variant="default" size="sm" @click="showMeetingModal = true">
          <component :is="Icons.MEETING" class="mr-2 h-4 w-4" />
          {{ $t('Sukurti susitikimą') }}
        </Button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Upcoming meetings card - Priority card with urgent styling -->
        <Card :class="[
          'flex flex-col relative overflow-hidden',
          upcomingMeetings.length > 0 ? 'border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950/20 dark:to-amber-950/20' : 'border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20'
        ]">
          <!-- Status indicator -->
          <div :class="[
            'absolute top-0 right-0 w-16 h-16 -mr-8 -mt-8 rotate-45',
            upcomingMeetings.length > 0 ? 'bg-orange-200 dark:bg-orange-800' : 'bg-green-200 dark:bg-green-800'
          ]" />

          <CardHeader class="pb-3 relative z-10">
            <CardTitle class="flex items-center gap-2">
              <component :is="Icons.MEETING" :class="[
                'h-5 w-5',
                upcomingMeetings.length > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400'
              ]" />
              {{ $t('Tavo artėjantys susitikimai') }}
            </CardTitle>
          </CardHeader>
          <CardContent class="flex-1 relative z-10">
            <div class="flex items-end gap-4 mb-4">
              <span :class="[
                'text-4xl font-bold',
                upcomingMeetings.length > 0 ? 'text-orange-700 dark:text-orange-300' : 'text-green-700 dark:text-green-300'
              ]">
                <NNumberAnimation :from="0" :to="upcomingMeetings.length" />
              </span>
              <div :class="[
                'px-2 py-1 rounded-full text-xs font-medium mb-2',
                upcomingMeetings.length > 0 ? 'bg-orange-200 text-orange-800 dark:bg-orange-800/50 dark:text-orange-200' : 'bg-green-200 text-green-800 dark:bg-green-800/50 dark:text-green-200'
              ]">
                {{ upcomingMeetings.length > 0 ? $t('Reikia dėmesio') : $t('Viskas tvarkoje') }}
              </div>
            </div>

            <div v-if="upcomingMeetings.length > 0" class="space-y-2">
              <p class="text-sm font-medium text-orange-800 dark:text-orange-200">
                {{ $t('Kitas susitikimas') }}:
              </p>
              <Link
                class="block p-3 bg-white/60 dark:bg-black/20 rounded-md border border-orange-200 dark:border-orange-700 hover:bg-white/80 dark:hover:bg-black/30 transition-colors"
                :href="route('meetings.show', upcomingMeetings[0].id)">
              <div class="font-semibold text-orange-900 dark:text-orange-100">
                {{ formatStaticTime(new Date(upcomingMeetings[0].start_time), {
                  month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric'
                }) }}
              </div>
              <div class="text-sm text-orange-700 dark:text-orange-300 mt-1">
                {{ upcomingMeetings[0].institutions?.[0].name }}
              </div>
              </Link>
            </div>

            <div v-else class="text-center py-4">
              <div class="text-4xl mb-2">
                🎉
              </div>
              <p class="text-green-800 dark:text-green-200 font-medium">
                {{ $t('Artėjančių susitikimų nėra!') }}
              </p>
              <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                {{ $t('Puikus laikas planuoti naują veiklą') }}
              </p>
            </div>

            <!-- Prominent CTA within card -->
            <div class="mt-6 pt-4 border-t border-orange-200 dark:border-orange-700">
              <div class="flex gap-2">
                <Button size="sm" variant="outline" class="flex-1" @click="showAllMeetingModal = true">
                  <component :is="Icons.MEETING" class="mr-2 h-4 w-4" />
                  {{ $t('Visi susitikimai') }}
                </Button>
                <Button v-if="upcomingMeetings.length > 0" size="sm" variant="default" as-child>
                  <Link :href="route('meetings.show', upcomingMeetings[0].id)">
                  {{ $t('Eiti į kitą') }}
                  </Link>
                </Button>
              </div>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-white/40 dark:bg-black/20 p-4 relative z-10">
            <!-- Meeting insights to encourage registration -->
            <div class="text-xs text-center w-full space-y-1" :class="[
              upcomingMeetings.length > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400'
            ]">
              <div v-if="institutionsInsights.withoutMeetings.length > 0">
                {{ $t('Institucijos be susitikimų') }}: {{ institutionsInsights.withoutMeetings.slice(0, 2).map(i => i.name).join(', ') }}
              </div>
              <div v-else-if="institutionsInsights.withOldMeetings.length > 0">
                {{ $t('Seniausi susitikimai') }}: {{ institutionsInsights.withOldMeetings[0].name }} 
                ({{ institutionsInsights.withOldMeetings[0].daysSinceLastMeeting }} {{ $t('dienos') }})
              </div>
              <div v-else>
                {{ $t('Visi susitikimai aktualūs!') }}
              </div>
            </div>
            <Dialog v-model:open="showAllMeetingModal">
              <DialogContent class="sm:max-w-[95vw] w-full max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                  <DialogTitle>{{ $t('Visi susitikimai') }}</DialogTitle>
                  <DialogDescription>
                    {{ $t('Peržiūrėkite visus savo susitikimus') }}
                  </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                  <SimpleDataTable :data="sortedMeetings" :columns="modernMeetingColumns" :enable-pagination="true"
                    :page-size="10" :enable-filtering="true" :enable-column-visibility="false"
                    :empty-message="$t('Susitikimų nerasta')" />
                </div>
              </DialogContent>
            </Dialog>
          </CardFooter>
        </Card>

        <!-- Your institutions card - Data card with red theme -->
        <Card
          class="flex flex-col bg-gradient-to-br from-red-50 to-rose-50 border-red-200 dark:from-red-950/20 dark:to-rose-950/20 dark:border-red-700">
          <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2">
              <component :is="Icons.INSTITUTION" class="h-5 w-5 text-red-600 dark:text-red-400" />
              {{ $t('Tavo institucijos') }}
            </CardTitle>
          </CardHeader>
          <CardContent class="flex-1">
            <div class="grid grid-cols-2 gap-4">
              <!-- Total institutions -->
              <div
                class="text-center p-4 bg-white/60 dark:bg-black/20 rounded-lg border border-red-200 dark:border-red-700">
                <div class="text-2xl font-bold text-red-700 dark:text-red-300 mb-1">
                  <NNumberAnimation :from="0" :to="institutions.length" />
                </div>
                <p class="text-sm text-red-600 dark:text-red-400 font-medium">
                  {{ $t('Visos institucijos') }}
                </p>
              </div>

              <!-- Active institutions -->
              <div
                class="text-center p-4 bg-white/60 dark:bg-black/20 rounded-lg border border-red-200 dark:border-red-700">
                <div class="text-2xl font-bold text-red-700 dark:text-red-300 mb-1">
                  <NNumberAnimation :from="0" :to="hasUpcomingMeetingCount" />
                </div>
                <p class="text-sm text-red-600 dark:text-red-400 font-medium">
                  {{ $t('Su artėjančiais posėdžiais') }}
                </p>
              </div>
            </div>

            <!-- Progress indicator -->
            <div v-if="institutions.length > 0" class="mt-4">
              <div class="flex justify-between text-sm text-red-600 dark:text-red-400 mb-2">
                <span>{{ $t('Aktyvumas') }}</span>
                <span>{{ Math.round((hasUpcomingMeetingCount / institutions.length) * 100) }}%</span>
              </div>
              <div class="w-full bg-red-200 dark:bg-red-700 rounded-full h-2">
                <div class="bg-red-600 dark:bg-red-400 h-2 rounded-full transition-all duration-500"
                  :style="{ width: `${(hasUpcomingMeetingCount / institutions.length) * 100}%` }" />
              </div>
            </div>

            <!-- Prominent CTA within card -->
            <div class="mt-4 pt-4 border-t border-red-200 dark:border-red-700">
              <Button size="sm" variant="outline" class="w-full" @click="showAllInstitutionModal = true">
                <component :is="Icons.INSTITUTION" class="mr-2 h-4 w-4" />
                {{ $t('Peržiūrėti institucijas') }}
              </Button>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-red-50/40 dark:bg-red-900/20 p-4">
            <!-- Institution activity insights -->
            <div class="text-xs text-red-500 dark:text-red-400 text-center w-full space-y-1">
              <div v-if="institutionsInsights.withoutMeetings.length > 0">
                <div class="font-medium">{{ $t('Reikia dėmesio') }}:</div>
                <div>{{ institutionsInsights.withoutMeetings.length }} {{ $t('institucijos be susitikimų') }}</div>
              </div>
              <div v-else-if="hasUpcomingMeetingCount < institutions.length">
                {{ $t('Aktyvumas') }}: {{ hasUpcomingMeetingCount }}/{{ institutions.length }} {{ $t('institucijos turi artėjančių susitikimų') }}
              </div>
              <div v-else>
                {{ $t('Puikus aktyvumas! Visos institucijos turi artėjančių susitikimų') }}
              </div>
            </div>
            <Dialog v-model:open="showAllInstitutionModal">
              <DialogContent class="sm:max-w-[95vw] w-full max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                  <DialogTitle>{{ $t('Visos institucijos') }}</DialogTitle>
                  <DialogDescription>
                    {{ $t('Peržiūrėkite visas savo institucijas ir jų aktyvumą') }}
                  </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                  <SimpleDataTable :data="institutions" :columns="modernInstitutionColumns" :enable-pagination="true"
                    :page-size="10" :enable-filtering="true" :enable-column-visibility="false"
                    :empty-message="$t('Institucijų nerasta')" />
                </div>
              </DialogContent>
            </Dialog>
          </CardFooter>
        </Card>
      </div>
    </section>

    <!-- Calendar Section -->
    <section class="mt-12 space-y-6">
      <h2 class="text-xl font-semibold tracking-tight">
        {{ $t('Kalendorius') }}
      </h2>

      <div class="flex flex-col lg:flex-row gap-6">
        <div class="flex-1 min-w-0">
          <Card class="my-calendar">
            <CardContent class="p-4">
              <Calendar :columns="1" :expanded="true" :is-dark :initial-page :locale="{
                id: $page.props.app.locale,
                firstDayOfWeek: 2,
                masks: { weekdays: 'WW' },
              }" color="red" borderless class="max-w-sm mx-auto" :attributes="calendarAttributes">
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
              </Calendar>
            </CardContent>
          </Card>
        </div>

        <div class="lg:w-80">
          <Card class="h-fit">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <component :is="Icons.MEETING" class="h-5 w-5 text-primary" />
                {{ $t('Susitikimų veiksmai') }}
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <Button variant="default" size="default" class="w-full" @click="showMeetingModal = true">
                <component :is="Icons.MEETING" class="mr-2 h-4 w-4" />
                {{ $t('Sukurti susitikimą') }}
              </Button>
              <Button size="default" variant="outline" class="w-full" @click="showAllMeetingModal = true">
                <component :is="Icons.MEETING" class="mr-2 h-4 w-4" />
                {{ $t('Rodyti visus susitikimus') }}
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>

      <NewMeetingModal :show-modal="showMeetingModal" @close="showMeetingModal = false" />
    </section>

    <!-- Tenant Representation Section -->
    <section v-if="tenants.length > 0" class="mt-12 space-y-8">
      <Separator class="mb-8" />

      <div class="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
        <h2 class="text-xl font-semibold tracking-tight">
          {{ $t('Atstovavimas padalinyje') }}
        </h2>
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

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Representatives card - Info card with brand amber theme -->
        <Card
          class="flex flex-col bg-gradient-to-br from-amber-50 to-yellow-50 border-vusa-yellow/30 dark:from-amber-950/20 dark:to-yellow-950/20 dark:border-vusa-yellow">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <component :is="Icons.USER" class="h-5 w-5 text-vusa-yellow-secondary" />
              {{ $t('Atstovai padalinyje') }}
            </CardTitle>
          </CardHeader>
          <CardContent class="flex-1">
            <div class="space-y-4">
              <div v-for="dutyType in dutyTypesWithUserCounts" :key="dutyType.title"
                class="flex items-center justify-between p-3 bg-white/60 dark:bg-black/20 rounded-lg border border-vusa-yellow/30 dark:border-vusa-yellow/50">
                <div>
                  <p class="font-medium text-vusa-yellow-dark dark:text-vusa-yellow-tertiary">
                    {{ dutyType.title }}
                  </p>
                  <p class="text-sm text-vusa-yellow-secondary dark:text-vusa-yellow">
                    {{ $t('Aktyvių atstovų') }}
                  </p>
                </div>
                <div class="text-right">
                  <span class="text-2xl font-bold text-vusa-yellow-secondary">
                    <NNumberAnimation :from="0" :to="dutyType.count" />
                  </span>
                </div>
              </div>

              <div v-if="!dutyTypesWithUserCounts || dutyTypesWithUserCounts.length === 0" class="text-center py-6">
                <div class="text-3xl mb-2">
                  👥
                </div>
                <p class="text-vusa-yellow-dark dark:text-vusa-yellow-tertiary font-medium">
                  {{ $t('Nėra aktyvių atstovų') }}
                </p>
                <p class="text-sm text-vusa-yellow-secondary mt-1">
                  {{ $t('Susisiekite su administratoriais') }}
                </p>
              </div>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-white/40 dark:bg-black/20 p-3">
            <Button size="sm" variant="outline" class="w-full" @click="showAllDutyModal = true">
              <component :is="Icons.USER" class="mr-2 h-4 w-4" />
              {{ $t('Visos pareigybės') }}
            </Button>
            <Dialog v-model:open="showAllDutyModal">
              <DialogContent class="sm:max-w-[95vw] w-full max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                  <DialogTitle>{{ $t('Visos pareigybės') }}</DialogTitle>
                  <DialogDescription>
                    {{ $t('Peržiūrėkite visas pareigybės ir jų narius') }}
                  </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                  <SimpleDataTable :data="allDuties" :columns="modernDutyColumns" :enable-pagination="true"
                    :page-size="10" :enable-filtering="true" :enable-column-visibility="false"
                    :empty-message="$t('Pareigybių nerasta')" />
                </div>
              </DialogContent>
            </Dialog>
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
                    <Skeleton class="h-8 w-8 rounded-full" />
                    <div class="space-y-2">
                      <Skeleton class="h-3 w-36" />
                      <Skeleton class="h-2 w-24 mx-auto" />
                    </div>
                  </div>
                </div>
              </template>
            </Suspense>
          </CardContent>
          <Dialog v-model:open="showMeetingBarPlot">
            <DialogContent class="sm:max-w-[95vw] w-full max-h-[85vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>{{ $t('Visų susitikimų statistika') }}</DialogTitle>
                <DialogDescription>
                  {{ $t('Detalus susitikimų aktyvumo vaizdas') }}
                </DialogDescription>
              </DialogHeader>
              <div class="space-y-4">
                <MeetingBarPlot :all-tenant-meetings />
              </div>
            </DialogContent>
          </Dialog>
        </Card>
      </div>

      <!-- Tenant calendar section -->
      <div class="space-y-4">
        <h3 class="text-lg font-medium tracking-tight">
          {{ $t('Padalinio kalendorius') }}
        </h3>
        <div class="flex justify-center">
          <Card class="my-calendar w-fit">
            <CardContent class="p-4">
              <Calendar :columns="1" :expanded="true" :is-dark :initial-page :locale="{
                id: $page.props.app.locale,
                firstDayOfWeek: 2,
                masks: { weekdays: 'WW' },
              }" color="red" borderless class="max-w-sm" :attributes="tenantCalendarAttributes">
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
            </CardContent>
          </Card>
        </div>
      </div>
    </section>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import "v-calendar/style.css";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, h, ref } from 'vue';
import { useDark } from "@vueuse/core";
import { trans as $t } from "laravel-vue-i18n";

// UI components
import { Calendar, PopoverRow } from "v-calendar";
import { NNumberAnimation, NSelect } from 'naive-ui';
import { Maximize2 as FullScreenIcon } from "lucide-vue-next";
import type { ColumnDef } from '@tanstack/vue-table';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import NewMeetingModal from '@/Components/Modals/NewMeetingModal.vue';
import SimpleDataTable from "@/Components/Tables/SimpleDataTable.vue";
import MeetingBarPlot from "@/Components/Graphs/MeetingBarPlot.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";

// Naive UI components (still needed for some legacy components)

// Shadcn UI components
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import { Skeleton } from "@/Components/ui/skeleton";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";

// Icons
import Icons from "@/Types/Icons/filled";


// Utils
import { formatStaticTime } from '@/Utils/IntlTime';
import { createIdColumn, createTimestampColumn, createTextColumn } from '@/Utils/DataTableColumns.tsx';
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

// Sort all meetings from newest to oldest for the table
const sortedMeetings = computed(() => {
  return meetings.sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime());
});

// Calculate institutions insights for footer information
const institutionsInsights = computed(() => {
  const institutionsWithMeetings = institutions.filter(inst => inst.meetings && inst.meetings.length > 0);
  const institutionsWithoutMeetings = institutions.filter(inst => !inst.meetings || inst.meetings.length === 0);
  
  // Find institutions with oldest last meetings
  const institutionsWithOldMeetings = institutionsWithMeetings
    .map(inst => {
      const lastMeeting = inst.meetings.sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())[0];
      return {
        ...inst,
        lastMeetingDate: new Date(lastMeeting.start_time),
        daysSinceLastMeeting: Math.floor((new Date().getTime() - new Date(lastMeeting.start_time).getTime()) / (1000 * 60 * 60 * 24))
      };
    })
    .sort((a, b) => b.daysSinceLastMeeting - a.daysSinceLastMeeting)
    .slice(0, 2);
  
  return {
    withoutMeetings: institutionsWithoutMeetings,
    withOldMeetings: institutionsWithOldMeetings
  };
});

const calendarAttributes = meetings.map((meeting) => {
  const calendarAttrObject = {
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


// Modern TanStack columns for institutions
const modernInstitutionColumns = computed<ColumnDef<any, any>[]>(() => [
  createIdColumn({ width: 60 }),
  {
    accessorKey: 'name',
    id: 'name',
    header: () => $t('forms.fields.title'),
    cell: ({ row }) => {
      const institutionName = row.original.name;
      const shouldShowTooltip = institutionName.length > 30;
      
      if (shouldShowTooltip) {
        return (
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <Link 
                  href={route('institutions.show', row.original.id)}
                  class="text-primary hover:underline max-w-[250px] truncate block"
                >
                  {institutionName}
                </Link>
              </TooltipTrigger>
              <TooltipContent side="top" align="start">
                <p class="max-w-xs">{institutionName}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        );
      }
      
      return (
        <Link 
          href={route('institutions.show', row.original.id)}
          class="text-primary hover:underline"
        >
          {institutionName}
        </Link>
      );
    },
    enableSorting: true,
  },
  {
    accessorKey: 'hasUpcomingMeetings',
    id: 'hasUpcomingMeetings',
    header: () => $t('Artėjantys posėdžiai'),
    cell: ({ row }) => (
      <span class={row.original.hasUpcomingMeetings ? 'text-green-600 font-medium' : 'text-gray-500'}>
        {row.original.hasUpcomingMeetings ? $t('Taip') : $t('Ne')}
      </span>
    ),
    enableSorting: true,
  },
]);

// Modern TanStack columns for duties
const modernDutyColumns = computed<ColumnDef<any, any>[]>(() => [
  createIdColumn({ width: 60 }),
  {
    accessorKey: 'title',
    id: 'title',
    header: () => $t('Pareigybė'),
    cell: ({ row }) => (
      <Link
        href={route('duties.show', row.original.duty_id)}
        class="text-primary hover:underline"
      >
        {row.original.title}
      </Link>
    ),
    enableSorting: true,
  },
  {
    accessorKey: 'institution',
    id: 'institution',
    header: () => $t('Institucija'),
    cell: ({ row }) => (
      <Link
        href={route('institutions.show', row.original.institution_id)}
        class="text-primary hover:underline"
      >
        {row.original.institution}
      </Link>
    ),
    enableSorting: true,
  },
  createTextColumn('users', {
    title: $t('Vartotojai'),
    width: 200,
  }),
  createTextColumn('type', {
    title: $t('Tipas'),
    width: 150,
  }),
]);

// Modern TanStack columns for meetings
const modernMeetingColumns = computed<ColumnDef<any, any>[]>(() => [
  createIdColumn({ width: 60 }),
  {
    accessorKey: 'institutions.0.name',
    id: 'institution',
    header: () => $t('Institucija'),
    cell: ({ row }) => (
      <Link
        href={route('institutions.show', row.original.institutions[0].id)}
        class="text-primary hover:underline"
      >
        {row.original.institutions[0].name}
      </Link>
    ),
    enableSorting: true,
  },
  {
    accessorKey: 'start_time',
    id: 'start_time',
    header: () => $t('Susitikimo pradžia'),
    cell: ({ row }) => {
      const startTime = new Date(row.original.start_time);
      const formattedTime = formatStaticTime(startTime, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric'
      });
      
      return (
        <Link 
          href={route('meetings.show', row.original.id)}
          class="text-primary hover:underline"
        >
          {formattedTime}
        </Link>
      );
    },
    enableSorting: true,
    sortingFn: (rowA, rowB) => {
      const dateA = new Date(rowA.original.start_time).getTime();
      const dateB = new Date(rowB.original.start_time).getTime();
      return dateB - dateA; // Sort newest first
    },
    width: 200,
  },
]);

// Legacy columns (keep for compatibility)
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
    const calendarAttrObject = {
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

/* Let calendar use its natural responsive behavior */
.my-calendar :deep(.vc-container) {
  max-width: 100%;
}
</style>
