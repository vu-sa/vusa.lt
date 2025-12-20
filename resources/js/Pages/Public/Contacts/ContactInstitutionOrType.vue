<template>
  <div>
    <InstitutionContacts
      class="mt-8"
      :contacts="contacts"
      :contact-sections="contactSections"
      :has-mixed-grouping="hasMixedGrouping"
      :institution="institution"
    />

    <!-- Meetings Section (expanded by default, collapsible) -->
    <Collapsible v-if="hasMeetings" v-model:open="showMeetings" class="mt-8">
      <!-- Section header with info button -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <h2 class="text-2xl font-bold">{{ $t('Posėdžiai') }}</h2>

          <!-- Info button with tooltip -->
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-6 w-6 p-0"
                  @click="showInfoModal = true"
                >
                  <InfoIcon class="h-3.5 w-3.5" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>{{ $t('Apie balsavimo skaidrumą') }}</TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <!-- Collapse toggle -->
        <CollapsibleTrigger as-child>
          <Button variant="ghost" size="sm">
            <ChevronDownIcon
              class="h-4 w-4 transition-transform"
              :class="{ 'rotate-180': !showMeetings }"
            />
          </Button>
        </CollapsibleTrigger>
      </div>

      <CollapsibleContent>
        <!-- Current academic year (always visible) -->
        <div v-if="currentYearMeetings" class="mb-6">
          <div class="flex items-center gap-2 mb-3">
            <h3 class="text-sm font-semibold text-muted-foreground">
              {{ currentYearMeetings.year_label }}
            </h3>
            <div class="flex-1 h-px bg-border" />
          </div>
          <div class="space-y-3">
            <MeetingTimelineItem
              v-for="(meeting, index) in currentYearMeetings.meetings"
              :key="meeting.id"
              :vote-alignment="getVoteAlignment(meeting)"
              :is-last="index === currentYearMeetings.meetings.length - 1"
            >
              <MeetingCard :meeting="meeting" />
            </MeetingTimelineItem>
          </div>
        </div>

        <!-- Toggle for previous academic years -->
        <Button
          v-if="previousYearsMeetings && previousYearsMeetings.length > 0 && !showPreviousYears"
          variant="outline"
          size="sm"
          class="w-full mb-4"
          @click="showPreviousYears = true"
        >
          {{ $t('Rodyti ankstesnius mokslo metus') }}
        </Button>

        <!-- Previous academic years (conditionally shown) -->
        <div v-if="showPreviousYears && previousYearsMeetings">
          <div
            v-for="(yearGroup, yearGroupIndex) in previousYearsMeetings"
            :key="yearGroup.year_key"
            class="mb-6"
          >
            <div class="flex items-center gap-2 mb-3">
              <h3 class="text-sm font-semibold text-muted-foreground">
                {{ yearGroup.year_label }}
              </h3>
              <div class="flex-1 h-px bg-border" />
            </div>
            <div class="space-y-3">
              <MeetingTimelineItem
                v-for="(meeting, index) in yearGroup.meetings"
                :key="meeting.id"
                :vote-alignment="getVoteAlignment(meeting)"
                :is-last="index === yearGroup.meetings.length - 1"
              >
                <MeetingCard :meeting="meeting" />
              </MeetingTimelineItem>
            </div>
          </div>
        </div>
      </CollapsibleContent>
    </Collapsible>

    <!-- Info modal -->
    <MeetingInfoModal v-model:open="showInfoModal" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronDownIcon, InfoIcon } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import InstitutionContacts from "@/Components/Public/InstitutionContacts.vue";
import MeetingCard from "@/Components/Public/MeetingCard.vue";
import MeetingInfoModal from "@/Components/Public/MeetingInfoModal.vue";
import MeetingTimelineItem from "@/Components/Public/MeetingTimelineItem.vue";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
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

const $page = usePage();

const props = defineProps<{
  contacts?: Array<App.Entities.User>;
  contactSections?: Array<ContactSection>;
  hasMixedGrouping?: boolean;
  institution: App.Entities.Institution;
  currentYearMeetings?: AcademicYearGroup;
  previousYearsMeetings?: Array<AcademicYearGroup>;
  hasMeetings?: boolean;
}>();

// Set breadcrumbs for institution contact page
usePageBreadcrumbs(() => {
  const items = [];
  
  // Main contacts link
  items.push(
    BreadcrumbHelpers.createRouteBreadcrumb(
      'Kontaktai',
      'contacts',
      {
        subdomain: 'www',
        lang: $page.props.app.locale
      },
      UserIcon
    )
  );
  
  // Add institution type as intermediate breadcrumb if available
  const institutionType = props.institution.types?.[0];
  if (institutionType) {
    items.push(
      BreadcrumbHelpers.createRouteBreadcrumb(
        String(institutionType.title ?? institutionType.slug),
        'contacts.category',
        {
          subdomain: 'www',
          lang: $page.props.app.locale,
          type: institutionType.slug
        },
        TypeIcon
      )
    );
  }
  
  // Current institution
  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(
      String(props.institution.name ?? props.institution.alias),
      undefined,
      InstitutionIcon
    )
  );
  
  return BreadcrumbHelpers.publicContent(items);
});

const showMeetings = ref(true);  // Expanded by default
const showPreviousYears = ref(false);
const showInfoModal = ref(false);

/**
 * Calculate vote alignment for a meeting based on agenda items
 * Returns: 'aligned' (all match), 'mixed' (some match), 'misaligned' (none match), 'no_data'
 */
const getVoteAlignment = (meeting: App.Entities.Meeting): 'aligned' | 'mixed' | 'misaligned' | 'no_data' => {
  const items = meeting.agenda_items || [];
  
  // Filter items that have both student_vote and decision
  const itemsWithBothVotes = items.filter(
    item => item.student_vote && item.decision
  );
  
  if (itemsWithBothVotes.length === 0) {
    return 'no_data';
  }
  
  // Count matches and mismatches
  const matches = itemsWithBothVotes.filter(
    item => item.student_vote === item.decision
  ).length;
  const mismatches = itemsWithBothVotes.length - matches;
  
  if (mismatches === 0) {
    return 'aligned';  // All student votes accepted
  } else if (matches === 0) {
    return 'misaligned';  // No student votes accepted
  } else {
    return 'mixed';  // Some matches, some mismatches
  }
};
</script>
