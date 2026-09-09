<template>
  <div class="study-sets-page">
    <Head>
      <title>{{ $t('studySets.page_title') }}</title>
      <meta name="description" :content="$t('studySets.page_description')">
    </Head>

    <!-- Page Title Band per v0 redesign -->
    <PageTitleBand
      :eyebrow="$t('Studijos')"
      :title="$t('studySets.page_title')"
      :lead="$t('studySets.page_description')"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>

      <!-- Faculty selector in title band actions -->
      <template v-if="tenantOptions.length > 0" #actions>
        <Select v-model="selectedTenantId">
          <SelectTrigger
            :class="[
              'h-10 min-w-48 border border-border bg-background px-4 text-sm font-medium',
              'text-foreground transition-colors hover:border-brand hover:text-brand',
            ]"
          >
            <IFluentHatGraduation24Regular class="size-4 text-brand" />
            <SelectValue />
          </SelectTrigger>
          <SelectContent align="end" class="min-w-48">
            <SelectItem
              v-for="option in tenantOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </template>
    </PageTitleBand>

    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8 space-y-6">
      <!-- Search and filters -->
      <div v-if="selectedTenantId" class="flex flex-wrap items-center gap-3">
        <div class="relative flex-grow min-w-64">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('studySets.search_placeholder')"
            :class="[
              'h-10 w-full border border-input bg-background pl-9 pr-3 text-sm text-foreground',
              'placeholder:text-muted-foreground outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            ]"
          >
          <IFluentSearch24Regular class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
        </div>

        <CheckControl
          v-model="searchAllFaculties"
          size="sm"
          class="h-10 w-auto border border-input bg-background px-3 py-0 text-sm gap-2"
          :label="$t('studySets.search_all_faculties')"
        />

        <Select v-model="selectedSemester">
          <SelectTrigger class="h-10 w-auto min-w-36 shrink-0">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="__all__">
              {{ $t('studySets.all_semesters') }}
            </SelectItem>
            <SelectItem value="autumn">
              {{ $t('studySets.autumn') }}
            </SelectItem>
            <SelectItem value="spring">
              {{ $t('studySets.spring') }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Button
          v-if="hasActiveFilters"
          variant="ghost"
          size="sm"
          class="shrink-0"
          @click="resetFilters"
        >
          <IFluentDismiss16Regular class="size-4 mr-1" />
          {{ $t('studySets.reset_filters') }}
        </Button>
      </div>

      <!-- Study sets for selected faculty -->
      <template v-if="selectedTenantId">
        <!-- No sets exist for this faculty and not searching all -->
        <div
          v-if="!searchAllFaculties && tenantStudySets.length === 0"
          class="border border-border bg-card p-12 text-center"
        >
          <div class="mx-auto flex size-12 items-center justify-center border border-border bg-muted/40 text-muted-foreground mb-4">
            <IFluentDocumentDismiss24Regular class="size-6" />
          </div>
          <p class="text-base font-medium text-foreground">
            {{ $t('studySets.no_sets') }}
          </p>
          <p class="mt-1 text-sm text-muted-foreground">
            {{ $t('studySets.no_sets_description') }}
          </p>
        </div>

        <!-- Filters returned no results -->
        <div
          v-else-if="filteredStudySets.length === 0"
          class="border border-border bg-card p-12 text-center"
        >
          <div class="mx-auto flex size-12 items-center justify-center border border-border bg-muted/40 text-muted-foreground mb-4">
            <IFluentSearch24Regular class="size-6" />
          </div>
          <p class="text-base font-medium text-foreground">
            {{ $t('studySets.no_results') }}
          </p>
          <p class="mt-1 text-sm text-muted-foreground">
            {{ $t('studySets.no_results_description') }}
          </p>
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-2">
          <div
            v-for="set in filteredStudySets"
            :key="set.id"
            class="border border-border bg-card transition-colors hover:border-brand/40"
          >
            <!-- Set header -->
            <div class="flex items-start justify-between gap-4 p-5 sm:p-6">
              <div class="min-w-0">
                <h2 class="text-lg font-semibold text-card-foreground leading-snug">
                  {{ set.name }}
                </h2>
                <p v-if="set.description" class="mt-1.5 text-sm text-muted-foreground">
                  {{ set.description }}
                </p>
                <Badge v-if="searchAllFaculties && set._tenantLabel" variant="outline" class="mt-2 text-xs font-normal text-brand border-brand/30">
                  <IFluentHatGraduation16Regular class="size-3 mr-1" />
                  {{ set._tenantLabel }}
                </Badge>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <Badge variant="outline" class="tabular-nums text-xs text-muted-foreground">
                  {{ $tChoice('studySets.course_count', set.courses.length, { count: String(set.courses.length) }) }}
                </Badge>
                <Badge variant="secondary" class="tabular-nums">
                  {{ set.total_credits }} {{ $tChoice('studySets.credits', set.total_credits) }}
                </Badge>
              </div>
            </div>

            <!-- Courses table -->
            <div v-if="getVisibleCourses(set).length > 0" class="px-5 sm:px-6 pb-2">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-t border-border">
                    <th class="py-2.5 pr-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      {{ $t('studySets.course_name') }}
                    </th>
                    <th class="py-2.5 pr-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      {{ $t('studySets.semester') }}
                    </th>
                    <th class="py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                      {{ $t('studySets.credits_short') }}
                    </th>
                    <th class="py-2.5 pl-3 text-right">
                      <span class="sr-only">{{ $t('studySets.reviews') }}</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border">
                  <template
                    v-for="course in getVisibleCourses(set)"
                    :key="course.id"
                  >
                    <tr class="transition-colors hover:bg-muted/30">
                      <td class="py-3 pr-4">
                        <span class="font-medium text-card-foreground">{{ course.name }}</span>
                      </td>
                      <td class="py-3 pr-4">
                        <Badge
                          variant="outline"
                          class="text-xs"
                        >
                          {{ course.semester === 'autumn' ? $t('studySets.autumn') : $t('studySets.spring') }}
                        </Badge>
                      </td>
                      <td class="py-3 text-right tabular-nums text-muted-foreground font-medium">
                        {{ course.credits }}
                      </td>
                      <td class="py-3 pl-3 text-right">
                        <Button
                          v-if="course.reviews.length > 0"
                          type="button"
                          variant="ghost"
                          size="sm"
                          class="-my-1 h-7 px-2 text-muted-foreground hover:text-foreground"
                          :aria-expanded="isCourseReviewsExpanded(set, course)"
                          :aria-controls="getCourseReviewPanelId(set, course)"
                          :aria-label="$t('studySets.reviews')"
                          @click="toggleCourseReviews(set, course)"
                        >
                          <IFluentComment24Regular class="size-3.5" />
                          <span class="tabular-nums text-xs ml-1">{{ course.reviews.length }}</span>
                        </Button>
                      </td>
                    </tr>

                    <tr
                      v-if="isCourseReviewsExpanded(set, course)"
                      :id="getCourseReviewPanelId(set, course)"
                      class="bg-muted/20"
                    >
                      <td colspan="4" class="py-0">
                        <div class="flex flex-col gap-3 p-4">
                          <div
                            v-for="review in course.reviews"
                            :key="review.id"
                            class="border border-border bg-background p-4"
                          >
                            <p class="text-xs font-semibold text-foreground">
                              {{ review.lecturer }}
                            </p>
                            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                              {{ review.comment }}
                            </p>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <!-- Footer with updated date -->
            <div class="flex items-center justify-end px-5 sm:px-6 pb-4 pt-1">
              <span class="text-xs text-muted-foreground/60">
                {{ $t('studySets.updated', { date: set.updated_at }) }}
              </span>
            </div>
          </div>
        </div>
      </template>

      <div v-else class="border border-border bg-card p-12 text-center">
        <div class="mx-auto flex size-12 items-center justify-center border border-border bg-muted/40 text-muted-foreground mb-4">
          <IFluentDocumentDismiss24Regular class="size-6" />
        </div>
        <p class="text-base font-medium text-foreground">
          {{ $t('studySets.no_sets') }}
        </p>
        <p class="mt-1 text-sm text-muted-foreground">
          {{ $t('studySets.no_sets_description') }}
        </p>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';

import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import CheckControl from '@/Components/Public/Base/CheckControl.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers, createBreadcrumbItem } from '@/Composables/useBreadcrumbsUnified';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { formatVuFacultyShortname } from '@/Utils/Tenant';
import { TenantType } from '@/Types/enums';
import IFluentDocumentDismiss24Regular from '~icons/fluent/document-dismiss-24-regular';
import IFluentHatGraduation24Regular from '~icons/fluent/hat-graduation-24-regular';
import IFluentHatGraduation16Regular from '~icons/fluent/hat-graduation-16-regular';
import IFluentComment24Regular from '~icons/fluent/comment-24-regular';
import IFluentSearch24Regular from '~icons/fluent/search-24-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentDocument16Regular from '~icons/fluent/document-16-regular';

interface ReviewData {
  id: string;
  lecturer: string;
  comment: string;
}

interface CourseData {
  id: string;
  name: string;
  semester: string;
  credits: number;
  reviews: ReviewData[];
}

interface StudySetData {
  id: string;
  name: string;
  description: string | null;
  total_credits: number;
  updated_at: string;
  courses: CourseData[];
  _tenantLabel?: string;
}

interface TenantData {
  id: number;
  shortname: string;
  alias: string;
  shortname_vu: string;
}

const props = defineProps<{
  tenants: TenantData[];
  studySetsByTenant: Record<string, StudySetData[]>;
}>();

usePageBreadcrumbs(() => {
  return BreadcrumbHelpers.publicContent([
    createBreadcrumbItem(
      $t('studySets.page_title'),
      undefined,
      IFluentDocument16Regular,
    ),
  ]);
}, { placement: 'band' });

const page = usePage();

const tenantOptions = computed(() =>
  props.tenants.map(t => ({
    value: String(t.id),
    label: formatVuFacultyShortname(t),
  })),
);

const getInitialTenantId = (): string => {
  // Landing here via a subdomain switch (see PadalinysSelector) carries the previous
  // tenant's `?faculty=` along with it — the current subdomain wins over that.
  const currentTenant = page.props.tenant;
  if (currentTenant?.type === TenantType.Padalinys) {
    const match = props.tenants.find(t => t.alias === currentTenant.alias);
    if (match) {
      return String(match.id);
    }
  }

  const params = new URLSearchParams(window.location.search);
  const facultyParam = params.get('faculty');

  if (facultyParam && props.tenants.some(t => String(t.id) === facultyParam)) {
    return facultyParam;
  }

  return props.tenants.length > 0 ? String(props.tenants[0].id) : '';
};

const selectedTenantId = ref(getInitialTenantId());
const searchQuery = ref('');
const selectedSemester = ref('__all__');
const searchAllFaculties = ref(false);
const expandedCourseBySet = ref<Record<string, string | null>>({});

const hasActiveFilters = computed(() => searchQuery.value !== '' || selectedSemester.value !== '__all__' || searchAllFaculties.value);

const resetFilters = () => {
  searchQuery.value = '';
  selectedSemester.value = '__all__';
  searchAllFaculties.value = false;
};

watch(selectedTenantId, (newId) => {
  const url = new URL(window.location.href);

  if (newId && props.tenants.length > 0 && newId !== String(props.tenants[0].id)) {
    url.searchParams.set('faculty', newId);
  }
  else {
    url.searchParams.delete('faculty');
  }

  window.history.replaceState({}, '', url.toString());

  resetFilters();
});

const tenantLabelMap = computed(() => {
  const map: Record<string, string> = {};
  for (const t of props.tenants) {
    map[String(t.id)] = formatVuFacultyShortname(t);
  }
  return map;
});

const tenantStudySets = computed(() => {
  return props.studySetsByTenant[selectedTenantId.value] || [];
});

const allStudySets = computed((): StudySetData[] => {
  return Object.entries(props.studySetsByTenant).flatMap(([tenantId, sets]) =>
    sets.map(set => ({ ...set, _tenantLabel: tenantLabelMap.value[tenantId] })),
  );
});

const searchPool = computed(() => {
  return searchAllFaculties.value ? allStudySets.value : tenantStudySets.value;
});

const filteredStudySets = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  const semester = selectedSemester.value;

  return searchPool.value.filter((set) => {
    // Semester filter: set must have at least one course matching the semester
    if (semester !== '__all__') {
      const hasMatchingSemester = set.courses.some(c => c.semester === semester);
      if (!hasMatchingSemester) return false;
    }

    // Search filter: match against set name, description, or any course name
    if (query) {
      const nameMatch = set.name.toLowerCase().includes(query);
      const descMatch = set.description?.toLowerCase().includes(query) ?? false;
      const courseMatch = set.courses.some(c => c.name.toLowerCase().includes(query));
      if (!nameMatch && !descMatch && !courseMatch) return false;
    }

    return true;
  });
});

const getVisibleCourses = (set: StudySetData): CourseData[] => {
  if (selectedSemester.value === '__all__') return set.courses;
  return set.courses.filter(c => c.semester === selectedSemester.value);
};

const getCourseReviewPanelId = (set: StudySetData, course: CourseData): string => {
  return `study-set-${set.id}-course-${course.id}-reviews`;
};

const isCourseReviewsExpanded = (set: StudySetData, course: CourseData): boolean => {
  return expandedCourseBySet.value[set.id] === course.id;
};

const toggleCourseReviews = (set: StudySetData, course: CourseData): void => {
  expandedCourseBySet.value[set.id] = isCourseReviewsExpanded(set, course) ? null : course.id;
};
</script>
