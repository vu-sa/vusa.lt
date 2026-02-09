<template>
  <div class="mt-6 sm:mt-8">
    <!-- Hero Header -->
    <header class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100 p-6 sm:p-8 dark:from-zinc-900 dark:to-zinc-800">
      <!-- Decorative blur circles -->
      <div class="pointer-events-none absolute -right-20 -top-20 size-64 rounded-full bg-vusa-red/5 blur-3xl" />
      <div class="pointer-events-none absolute -bottom-10 -left-10 size-48 rounded-full bg-vusa-yellow/5 blur-3xl" />

      <div class="relative">
        <!-- Back link -->
        <SmartLink
          :href="route('contacts', { subdomain: 'www', lang: $page.props.app.locale })"
          class="mb-4 inline-flex items-center gap-1.5 text-xs font-medium text-zinc-500 transition-colors hover:text-vusa-red dark:text-zinc-400"
        >
          <ArrowLeftIcon class="size-3" />
          {{ $t('Visi kontaktai') }}
        </SmartLink>

        <!-- Institution header with optional image -->
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
          <!-- Logo/Image section -->
          <div v-if="institution.logo_url || institution.image_url" class="shrink-0">
            <!-- Logo with image background -->
            <div v-if="institution.image_url && institution.logo_url" class="relative">
              <img
                :src="institution.image_url"
                :alt="institution.name + ' background'"
                class="h-24 w-40 rounded-xl object-cover blur-[1px] sm:h-28 sm:w-48"
                style="object-position: 50% 35%"
              >
              <div class="absolute inset-0 rounded-xl bg-black/10" />
              <img
                :src="institution.logo_url"
                :alt="institution.name + ' logo'"
                class="absolute left-1/2 top-1/2 size-14 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-white object-contain shadow-lg sm:size-16"
              >
            </div>
            <!-- Logo only -->
            <img
              v-else-if="institution.logo_url"
              :src="institution.logo_url"
              :alt="institution.name + ' logo'"
              class="size-16 rounded-xl border border-zinc-200 bg-white object-contain shadow-sm dark:border-zinc-700 sm:size-20"
            >
            <!-- Image only -->
            <img
              v-else-if="institution.image_url"
              :src="institution.image_url"
              :alt="institution.name"
              class="h-24 w-40 rounded-xl object-cover shadow-sm sm:h-28 sm:w-48"
              style="object-position: 50% 35%"
            >
          </div>

          <!-- Title and meta -->
          <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold leading-tight text-zinc-900 dark:text-zinc-50 sm:text-3xl lg:text-4xl">
              {{ institution.name }}
            </h1>

            <!-- Type badges -->
            <div v-if="institution.types?.length" class="mt-3 flex flex-wrap gap-2">
              <SmartLink
                v-for="institutionType in institution.types"
                :key="institutionType.id"
                :href="route('contacts.category', {
                  subdomain: 'www',
                  lang: $page.props.app.locale,
                  type: institutionType.slug,
                }) + '?all=1'"
                class="inline-flex items-center gap-1 rounded-full bg-zinc-200/70 px-3 py-1 text-xs font-medium text-zinc-700 transition-colors hover:bg-vusa-red/10 hover:text-vusa-red dark:bg-zinc-700/70 dark:text-zinc-300 dark:hover:bg-vusa-red/20"
              >
                {{ $t(institutionType.title) }}
              </SmartLink>
            </div>

            <!-- Social & contact links -->
            <div class="mt-4 flex flex-wrap gap-2">
              <a
                v-if="institution.facebook_url"
                :href="institution.facebook_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
              >
                <IMdiFacebook class="size-3.5" />
                Facebook
              </a>
              <a
                v-if="institution.instagram_url"
                :href="institution.instagram_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-lg bg-pink-50 px-2.5 py-1 text-xs font-medium text-pink-700 transition-colors hover:bg-pink-100 dark:bg-pink-900/30 dark:text-pink-300 dark:hover:bg-pink-900/50"
              >
                <IMdiInstagram class="size-3.5" />
                Instagram
              </a>
              <a
                v-if="institution.website"
                :href="institution.website"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
              >
                <GlobeIcon class="size-3.5" />
                {{ extractDomain(institution.website) }}
              </a>
              <a
                v-if="institution.email"
                :href="`mailto:${institution.email}`"
                class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
              >
                <MailIcon class="size-3.5" />
                {{ institution.email }}
              </a>
              <a
                v-if="institution.phone"
                :href="`tel:${institution.phone}`"
                class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
              >
                <PhoneIcon class="size-3.5" />
                {{ institution.phone }}
              </a>
            </div>
          </div>
        </div>

        <!-- Description (collapsible on mobile) -->
        <div v-if="institution.description" class="mt-6 border-t border-zinc-200/50 pt-5 dark:border-zinc-700/50">
          <Collapsible v-if="isMobile" v-model:open="isDescriptionOpen">
            <CollapsibleTrigger class="flex w-full items-center justify-between text-left">
              <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $t('Aprašymas') }}</span>
              <ChevronDownIcon
                class="size-4 text-zinc-500 transition-transform duration-200"
                :class="{ 'rotate-180': isDescriptionOpen }"
              />
            </CollapsibleTrigger>
            <CollapsibleContent>
              <div class="typography mt-3 max-w-prose text-sm leading-relaxed text-zinc-600 dark:text-zinc-400" v-html="institution.description" />
            </CollapsibleContent>
          </Collapsible>
          <div v-else class="typography max-w-prose text-sm leading-relaxed text-zinc-600 dark:text-zinc-400" v-html="institution.description" />
        </div>
      </div>
    </header>

    <!-- Contacts Section -->
    <section>
      <div class="mb-6 flex items-center gap-3">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
          {{ $t('Kontaktai') }}
        </h2>
        <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
      </div>

      <!-- Empty state -->
      <EmptyContactsState
        v-if="!hasAnyContacts"
        :student-rep-form-info
        :institution-name="String(institution.name)"
      />

      <!-- Mixed contact sections (grouped by duty) -->
      <div v-else-if="hasMixedGrouping" class="space-y-8">
        <div v-for="section in contactSections" :key="section.dutyName">
          <!-- Section header -->
          <div class="mb-4 flex items-center gap-2">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200">
              {{ section.dutyName }}
            </h3>
            <div class="h-px flex-1 bg-zinc-200/50 dark:bg-zinc-700/50" />
          </div>

          <!-- Grouped duty contacts -->
          <div v-if="section.type === 'grouped_duty'" class="space-y-6">
            <div v-for="group in section.groups" :key="group.name">
              <h4 class="mb-3 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                {{ group.name }}
              </h4>
              <div class="grid gap-3 grid-cols-2 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                <ContactCard
                  v-for="contact in group.contacts"
                  :key="contact.id"
                  :contact
                  :duties="contact.duties || []"
                />
              </div>
            </div>
          </div>

          <!-- Flat duty contacts -->
          <div v-else class="grid gap-3 grid-cols-2 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
            <ContactCard
              v-for="contact in section.contacts"
              :key="contact.id"
              :contact
              :duties="contact.duties || []"
            />
          </div>
        </div>
      </div>

      <!-- Flat contacts grid -->
      <div v-else class="grid gap-3 grid-cols-2 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
        <ContactCard
          v-for="contact in contacts"
          :key="contact.id"
          :contact
          :duties="contact.duties || []"
        />
      </div>
    </section>

    <!-- Meetings Section -->
    <section v-if="hasMeetings" class="mt-10">
      <Collapsible v-model:open="showMeetings">
        <!-- Section header -->
        <div class="mb-6 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
              {{ $t('Posėdžiai') }}
            </h2>
            <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />

            <!-- Info button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                    @click.stop="showInfoModal = true"
                  >
                    <InfoIcon class="size-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Apie balsavimo skaidrumą') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>

          <CollapsibleTrigger as-child>
            <Button variant="ghost" size="sm" class="size-8 p-0">
              <ChevronDownIcon
                class="size-4 transition-transform duration-200"
                :class="{ 'rotate-180': !showMeetings }"
              />
            </Button>
          </CollapsibleTrigger>
        </div>

        <CollapsibleContent>
          <!-- Current academic year -->
          <div v-if="currentYearMeetings" class="mb-6">
            <div class="mb-3 flex items-center gap-2">
              <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">
                {{ currentYearMeetings.year_label }}
              </h3>
              <div class="h-px flex-1 bg-zinc-200/50 dark:bg-zinc-700/50" />
            </div>
            <div class="space-y-3">
              <MeetingTimelineItem
                v-for="(meeting, index) in currentYearMeetings.meetings"
                :key="meeting.id"
                :vote-alignment="getVoteAlignment(meeting)"
                :is-last="index === currentYearMeetings.meetings.length - 1"
              >
                <MeetingCard :meeting />
              </MeetingTimelineItem>
            </div>
          </div>

          <!-- Previous years toggle -->
          <Button
            v-if="previousYearsMeetings && previousYearsMeetings.length > 0 && !showPreviousYears"
            variant="outline"
            size="sm"
            class="mb-4 w-full"
            @click="showPreviousYears = true"
          >
            {{ $t('Rodyti ankstesnius mokslo metus') }}
          </Button>

          <!-- Previous academic years -->
          <div v-if="showPreviousYears && previousYearsMeetings">
            <div
              v-for="yearGroup in previousYearsMeetings"
              :key="yearGroup.year_key"
              class="mb-6"
            >
              <div class="mb-3 flex items-center gap-2">
                <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">
                  {{ yearGroup.year_label }}
                </h3>
                <div class="h-px flex-1 bg-zinc-200/50 dark:bg-zinc-700/50" />
              </div>
              <div class="space-y-3">
                <MeetingTimelineItem
                  v-for="(meeting, index) in yearGroup.meetings"
                  :key="meeting.id"
                  :vote-alignment="getVoteAlignment(meeting)"
                  :is-last="index === yearGroup.meetings.length - 1"
                >
                  <MeetingCard :meeting />
                </MeetingTimelineItem>
              </div>
            </div>
          </div>
        </CollapsibleContent>
      </Collapsible>
    </section>

    <!-- Info modal -->
    <PublicVotingExplainerModal v-model:open="showInfoModal" />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import { ArrowLeftIcon, ChevronDownIcon, GlobeIcon, InfoIcon, MailIcon, PhoneIcon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import ContactCard from '@/Components/Public/ContactWithPhoto.vue';
import EmptyContactsState from '@/Components/Public/EmptyContactsState.vue';
import MeetingCard from '@/Components/Public/MeetingCard.vue';
import PublicVotingExplainerModal from '@/Components/Public/PublicVotingExplainerModal.vue';
import MeetingTimelineItem from '@/Components/Public/MeetingTimelineItem.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { getMeetingStatusSummary } from '@/Composables/useAgendaItemStyling';
import { UserIcon, InstitutionIcon, TypeIcon } from '@/Components/icons';

interface ContactGroup {
  name: string;
  contacts: Array<App.Entities.User>;
}

interface ContactSection {
  type: 'grouped_duty' | 'flat_duty';
  dutyName: string;
  groups?: Array<ContactGroup>;
  contacts?: Array<App.Entities.User>;
}

interface AcademicYearGroup {
  year_key: string;
  year_label: string;
  meetings: Array<App.Entities.Meeting>;
}

interface StudentRepFormInfo {
  formPath: string;
  institutionId: string;
  institutionName: string;
}

const $page = usePage();

const props = defineProps<{
  contacts?: Array<App.Entities.User>;
  contactSections?: Array<ContactSection>;
  hasMixedGrouping?: boolean;
  institution: App.Entities.Institution;
  currentYearMeetings?: AcademicYearGroup;
  previousYearsMeetings?: Array<AcademicYearGroup>;
  hasMeetings?: boolean;
  studentRepFormInfo?: StudentRepFormInfo | null;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smallerOrEqual('sm');

// Set breadcrumbs
usePageBreadcrumbs(() => {
  const items = [];

  items.push(
    BreadcrumbHelpers.createRouteBreadcrumb(
      'Kontaktai',
      'contacts',
      { subdomain: 'www', lang: $page.props.app.locale },
      UserIcon,
    ),
  );

  const institutionType = props.institution.types?.[0];
  if (institutionType) {
    items.push(
      BreadcrumbHelpers.createRouteBreadcrumb(
        String(institutionType.title ?? institutionType.slug),
        'contacts.category',
        { subdomain: 'www', lang: $page.props.app.locale, type: institutionType.slug },
        TypeIcon,
      ),
    );
  }

  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(
      String(props.institution.name ?? props.institution.alias),
      undefined,
      InstitutionIcon,
    ),
  );

  return BreadcrumbHelpers.publicContent(items);
});

const isDescriptionOpen = ref(false);
const showMeetings = ref(true);
const showPreviousYears = ref(false);
const showInfoModal = ref(false);

// Check if there are any contacts
const hasAnyContacts = computed(() => {
  if (props.hasMixedGrouping) {
    return (props.contactSections?.length ?? 0) > 0 && props.contactSections?.some((section) => {
      if (section.type === 'grouped_duty') {
        return section.groups?.some(group => (group.contacts?.length ?? 0) > 0);
      }
      return (section.contacts?.length ?? 0) > 0;
    });
  }
  return (props.contacts?.length ?? 0) > 0;
});

// Extract domain from URL for display
const extractDomain = (url: string): string => {
  try {
    const domain = new URL(url).hostname;
    return domain.replace(/^www\./, '');
  }
  catch {
    return url;
  }
};

// Calculate vote alignment for meetings using composable
const getVoteAlignment = (meeting: App.Entities.Meeting): 'aligned' | 'mixed' | 'misaligned' | 'no_data' => {
  const summary = getMeetingStatusSummary(meeting.agenda_items || []);

  // Map composable's voteAlignmentStatus to component's format
  switch (summary.voteAlignmentStatus) {
    case 'all_match':
      return 'aligned';
    case 'all_mismatch':
      return 'misaligned';
    case 'mixed':
      return 'mixed';
    default:
      return 'no_data';
  }
};
</script>
