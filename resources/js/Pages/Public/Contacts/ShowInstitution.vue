<template>
  <div class="institution-page">
    <Head>
      <title>{{ `${institution.name} | ${$t('Kontaktai')}` }}</title>
      <meta v-if="institution.description" name="description" :content="institution.description">
    </Head>

    <!-- Hero Section based on v0 redesign -->
    <section class="border-b border-border">
      <div
        class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8 lg:py-16"
        :class="institution.image_url ? 'grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16' : ''"
      >
        <div class="flex flex-col justify-center">
          <!-- Inline Breadcrumbs -->
          <div class="mb-8">
            <PublicBreadcrumbs variant="inline" />
          </div>

          <!-- Title lockup with border-l-2 border-brand -->
          <div class="border-l-2 border-brand pl-5 sm:pl-7">
            <span class="block text-xs font-bold uppercase tracking-[0.28em] text-brand">
              {{ institution.tenant?.shortname ?? $t('Kontaktai') }}
            </span>
            <h1 class="u-display mt-3 text-3xl font-bold uppercase tracking-tight text-foreground sm:text-5xl lg:text-6xl leading-tight">
              {{ institution.name }}
            </h1>
          </div>

          <!-- Description -->
          <div
            v-if="institution.description"
            class="typography mt-6 max-w-xl pl-5 text-base leading-relaxed text-muted-foreground sm:pl-7"
            v-html="institution.description"
          />
        </div>

        <!-- Right-column photo card (photo only, no text or logo) -->
        <div
          v-if="institution.image_url"
          class="relative flex aspect-video sm:aspect-4/3 lg:aspect-auto min-h-[240px] overflow-hidden border border-border bg-muted/20"
        >
          <img
            :src="institution.image_url"
            :alt="institution.name"
            class="size-full object-cover contrast-105"
            :style="{ objectPosition: institution.image_focal_point ?? '50% 30%' }"
          >
        </div>
      </div>
    </section>

    <!-- Available Contact Details Mini-Card Grid (ruled flex grid matching PartnersBanner) -->
    <section v-if="availableDetails.length > 0">
      <div class="mx-auto flex max-w-7xl flex-wrap justify-center">
        <component
          :is="item.href ? 'a' : 'div'"
          v-for="item in availableDetails"
          :key="item.label"
          :href="item.href"
          :target="item.external ? '_blank' : undefined"
          :rel="item.external ? 'noopener noreferrer' : undefined"
          :class="[
            'group flex shrink-0 grow-0 flex-col p-6 sm:p-8 text-left transition-colors hover:bg-secondary/40',
            detailBasisClass,
            'border-b border-l border-border',
            detailBorderRightClass,
          ]"
        >
          <component :is="item.icon" class="size-5 text-brand" />
          <p class="mt-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
            {{ item.label }}
          </p>
          <p class="mt-1 text-base sm:text-lg font-bold text-foreground group-hover:text-brand transition-colors break-words">
            {{ item.value }}
          </p>
          <p v-if="item.sub" class="mt-1 text-xs text-muted-foreground">
            {{ item.sub }}
          </p>
        </component>
      </div>

      <!-- Separator line with generous margin below contact details -->
      <div class="mt-10 sm:mt-14 border-b border-border mb-4 sm:mb-6" />
    </section>

    <!-- Main Content: Duty Tabs & Contacts -->
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <!-- Duty Type Tabs for Padalinys -->
      <div v-if="dutyTypeTabs && dutyTypeTabs.length > 1" class="mb-8 flex flex-wrap items-center gap-2">
        <SmartLink
          v-for="tab in dutyTypeTabs"
          :key="tab.slug"
          :href="tab.href"
          :class="[
            'inline-flex h-8 items-center justify-center px-3.5 text-xs font-bold uppercase tracking-wider transition-colors',
            activeDutyTypeTab === tab.slug
              ? 'bg-brand-fill text-brand-foreground'
              : 'text-muted-foreground hover:text-foreground hover:bg-transparent',
          ]"
        >
          {{ tab.label }}
        </SmartLink>
      </div>

      <!-- Contacts section -->
      <div>
        <div class="mb-8 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
          <div>
            <h2 class="u-display text-2xl font-bold uppercase tracking-tight text-foreground sm:text-3xl">
              {{ $t('Kontaktai') }}
            </h2>
          </div>
        </div>

        <!-- Empty state -->
        <EmptyContactsState
          v-if="!hasAnyContacts"
          :student-rep-form-info
          :institution-name="String(institution.name)"
        />

        <!-- Mixed contact sections (grouped by duty) -->
        <div v-else-if="hasMixedGrouping" class="space-y-10">
          <div v-for="section in contactSections" :key="section.dutyName">
            <!-- Section header -->
            <div class="mb-5 border-b border-border pb-2">
              <h3 class="text-lg font-bold text-foreground">
                {{ section.dutyName }}
              </h3>
            </div>

            <!-- Grouped duty contacts -->
            <div v-if="section.type === 'grouped_duty'" class="space-y-6">
              <div v-for="group in section.groups" :key="group.name">
                <h4 class="mb-3 text-sm font-semibold text-muted-foreground">
                  {{ group.name }}
                </h4>
                <div :class="contactGridClass">
                  <ContactCard
                    v-for="contact in group.contacts"
                    :key="contact.id"
                    :contact
                    :duties="contact.duties || []"
                    hide-duty-names
                  />
                </div>
              </div>
            </div>

            <!-- Flat duty contacts -->
            <div v-else :class="contactGridClass">
              <ContactCard
                v-for="contact in section.contacts"
                :key="contact.id"
                :contact
                :duties="contact.duties || []"
                hide-duty-names
              />
            </div>
          </div>
        </div>

        <!-- Flat contacts grid -->
        <div v-else :class="contactGridClass">
          <ContactCard
            v-for="contact in contacts"
            :key="contact.id"
            :contact
            :duties="contact.duties || []"
          />
        </div>
      </div>
    </section>

    <!-- Meetings Section -->
    <section v-if="hasMeetings" class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8 border-t border-border">
      <Collapsible v-model:open="showMeetings">
        <!-- Section header -->
        <div class="mb-6 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <h2 class="u-display text-2xl font-bold uppercase tracking-tight text-foreground sm:text-3xl">
              {{ $t('Posėdžiai') }}
            </h2>

            <!-- Info button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="size-7 p-0 text-muted-foreground hover:text-foreground"
                    @click.stop="showInfoModal = true"
                  >
                    <IFluentInfo16Regular class="size-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Apie balsavimo skaidrumą') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>

          <CollapsibleTrigger as-child>
            <Button variant="ghost" size="sm" class="size-8 p-0">
              <IFluentChevronDown16Regular
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
              <h3 class="text-sm font-semibold text-muted-foreground">
                {{ currentYearMeetings.year_label }}
              </h3>
              <div class="h-px flex-1 bg-border" />
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
                <h3 class="text-sm font-semibold text-muted-foreground">
                  {{ yearGroup.year_label }}
                </h3>
                <div class="h-px flex-1 bg-border" />
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
import { Head, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import ContactCard from '@/Components/Public/ContactWithPhoto.vue';
import EmptyContactsState from '@/Components/Public/EmptyContactsState.vue';
import MeetingCard from '@/Components/Public/MeetingCard.vue';
import PublicVotingExplainerModal from '@/Components/Public/PublicVotingExplainerModal.vue';
import MeetingTimelineItem from '@/Components/Public/MeetingTimelineItem.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { getMeetingStatusSummary } from '@/Composables/useAgendaItemStyling';
import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentGlobe24Regular from '~icons/fluent/globe-24-regular';
import IFluentInfo16Regular from '~icons/fluent/info-16-regular';
import IFluentMail24Regular from '~icons/fluent/mail-24-regular';
import IFluentCall24Regular from '~icons/fluent/call-24-regular';
import IFluentLocation24Regular from '~icons/fluent/location-24-regular';
import ISimpleIconsFacebook from '~icons/simple-icons/facebook';
import ISimpleIconsInstagram from '~icons/simple-icons/instagram';

// Draws grid dividers only *between* cards (never on the grid's outer edge): the right
// border is skipped on each row's last column, the bottom border on each column's last row —
// both computed with :nth-last-child so a ragged final row still gets correct edges. `:last-child`
// is excluded from the right border too: when the final row is short (e.g. 3n+2 items), the last
// card lands short of the last column but is still the rightmost card in its row.
const contactGridClass = [
  'grid sm:grid-cols-2 lg:grid-cols-3',
  '[&>*]:border-border',
  'max-sm:[&>*:not(:nth-last-child(-n+1))]:border-b',
  'sm:max-lg:[&>*:not(:nth-child(2n)):not(:last-child)]:border-r sm:max-lg:[&>*:not(:nth-last-child(-n+2))]:border-b',
  'lg:[&>*:not(:nth-child(3n)):not(:last-child)]:border-r lg:[&>*:not(:nth-last-child(-n+3))]:border-b',
].join(' ');

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

interface DutyTypeTab {
  label: string;
  slug: string;
  href: string;
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
  dutyTypeTabs?: Array<DutyTypeTab>;
  activeDutyTypeTab?: string;
}>();

// Set breadcrumbs
usePageBreadcrumbs(() => {
  const items = [];

  items.push(
    BreadcrumbHelpers.createRouteBreadcrumb(
      'Kontaktai',
      'contacts',
      { subdomain: 'www', lang: $page.props.app.locale },
    ),
  );

  const institutionType = props.institution.types?.[0];
  if (institutionType) {
    items.push(
      BreadcrumbHelpers.createRouteBreadcrumb(
        String(institutionType.title ?? institutionType.slug),
        'contacts.category',
        { subdomain: 'www', lang: $page.props.app.locale, type: institutionType.slug },
      ),
    );
  }

  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(
      String(props.institution.name ?? props.institution.alias),
    ),
  );

  return BreadcrumbHelpers.publicContent(items);
}, { placement: 'band' });

const showMeetings = ref(true);
const showPreviousYears = ref(false);
const showInfoModal = ref(false);

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

// Available details for mini-card grid (v0 redesign)
const availableDetails = computed(() => {
  const items: Array<{
    icon: unknown;
    label: string;
    value: string;
    sub?: string;
    href?: string;
    external?: boolean;
  }> = [];

  if (props.institution.address) {
    items.push({
      icon: IFluentLocation24Regular,
      label: $t('Adresas'),
      value: props.institution.address,
      href: `https://maps.google.com/?q=${encodeURIComponent(props.institution.address)}`,
      external: true,
    });
  }

  if (props.institution.phone) {
    items.push({
      icon: IFluentCall24Regular,
      label: $t('Telefonas'),
      value: props.institution.phone,
      sub: $t('Darbo dienomis'),
      href: `tel:${props.institution.phone}`,
    });
  }

  if (props.institution.email) {
    items.push({
      icon: IFluentMail24Regular,
      label: $t('El. paštas'),
      value: props.institution.email,
      href: `mailto:${props.institution.email}`,
    });
  }

  if (props.institution.website) {
    items.push({
      icon: IFluentGlobe24Regular,
      label: $t('Svetainė'),
      value: extractDomain(props.institution.website),
      href: props.institution.website,
      external: true,
    });
  }

  if (props.institution.facebook_url) {
    items.push({
      icon: ISimpleIconsFacebook,
      label: 'Facebook',
      value: 'VU SA',
      href: props.institution.facebook_url,
      external: true,
    });
  }

  if (props.institution.instagram_url) {
    items.push({
      icon: ISimpleIconsInstagram,
      label: 'Instagram',
      value: '@vusa',
      href: props.institution.instagram_url,
      external: true,
    });
  }

  return items;
});

const detailBasisClass = computed(() => {
  const count = availableDetails.value.length;
  if (count === 1) return 'basis-full';
  if (count === 2) return 'basis-full sm:basis-1/2 lg:basis-1/2';
  if (count === 3) return 'basis-full sm:basis-1/2 lg:basis-1/3';
  if (count === 4) return 'basis-full sm:basis-1/2 lg:basis-1/4';
  if (count === 5) return 'basis-full sm:basis-1/2 lg:basis-1/5';
  return 'basis-full sm:basis-1/2 lg:basis-1/3';
});

const detailBorderRightClass = computed(() => {
  const count = availableDetails.value.length;
  if (count <= 5) {
    return 'max-sm:border-r sm:max-lg:[&:is(:nth-child(2n),:last-child)]:border-r lg:[&:last-child]:border-r';
  }
  return 'max-sm:border-r sm:max-lg:[&:is(:nth-child(2n),:last-child)]:border-r lg:[&:is(:nth-child(3n),:last-child)]:border-r';
});

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

// Calculate vote alignment for meetings using composable
const getVoteAlignment = (meeting: App.Entities.Meeting): 'aligned' | 'mixed' | 'misaligned' | 'no_data' => {
  const summary = getMeetingStatusSummary(meeting.agenda_items || [], meeting.requires_student_perspective ?? true);

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
