<template>
  <Head>
    <title>{{ $t('studySets.page_title') }}</title>
    <meta name="description" :content="$t('studySets.page_description')">
  </Head>

  <div class="space-y-6">
    <!-- Page header with integrated faculty selector -->
    <div class="text-center max-w-2xl mx-auto py-4 sm:py-6">
      <div class="flex items-center justify-center mb-3 sm:mb-4">
        <div class="p-2 sm:p-3 rounded-xl bg-gradient-to-br from-primary/10 to-secondary/10 shadow-sm">
          <BookOpen class="w-6 h-6 sm:w-8 sm:h-8 text-primary" />
        </div>
      </div>
      <h1 class="text-2xl sm:text-4xl font-bold text-foreground mb-2 sm:mb-3">
        {{ $t('studySets.page_title') }}
      </h1>
      <p class="text-base sm:text-lg text-muted-foreground px-2">
        {{ $t('studySets.page_description') }}
      </p>

      <!-- Faculty selector -->
      <div v-if="tenantOptions.length > 0" class="mt-5 sm:mt-6 flex justify-center">
        <Select v-model="selectedTenantId">
          <SelectTrigger
            class="h-11 min-w-48 rounded-full border-primary/20 bg-primary/5 px-5 text-base font-medium text-foreground shadow-sm hover:border-primary/40 hover:bg-primary/10 transition-colors"
          >
            <GraduationCap class="size-4 text-primary" />
            <SelectValue />
          </SelectTrigger>
          <SelectContent align="center" class="min-w-48">
            <SelectItem
              v-for="option in tenantOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <!-- Search and filters -->
    <div v-if="selectedTenantId" class="flex flex-wrap items-center gap-3">
      <div class="relative flex-grow">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('studySets.search_placeholder')"
          class="h-10 w-full rounded-lg border border-input bg-background pl-9 pr-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
        >
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
      </div>

      <label class="flex shrink-0 items-center gap-2 cursor-pointer select-none">
        <Switch :model-value="searchAllFaculties" @update:model-value="searchAllFaculties = $event" />
        <span class="text-sm text-muted-foreground whitespace-nowrap">{{ $t('studySets.search_all_faculties') }}</span>
      </label>

      <Select v-model="selectedSemester">
        <SelectTrigger class="h-10 w-auto min-w-36 shrink-0">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="__all__">{{ $t('studySets.all_semesters') }}</SelectItem>
          <SelectItem value="autumn">{{ $t('studySets.autumn') }}</SelectItem>
          <SelectItem value="spring">{{ $t('studySets.spring') }}</SelectItem>
        </SelectContent>
      </Select>

      <Button
        v-if="hasActiveFilters"
        variant="ghost"
        size="sm"
        class="shrink-0"
        @click="resetFilters"
      >
        <X class="size-4 mr-1" />
        {{ $t('studySets.reset_filters') }}
      </Button>
    </div>

    <!-- Study sets for selected faculty -->
    <template v-if="selectedTenantId">
      <!-- No sets exist for this faculty and not searching all -->
      <div v-if="!searchAllFaculties && tenantStudySets.length === 0" class="flex flex-col items-center py-16">
        <div class="rounded-full bg-muted/60 p-4">
          <FileX class="size-8 text-muted-foreground/60" />
        </div>
        <p class="mt-4 text-sm font-medium text-foreground">{{ $t('studySets.no_sets') }}</p>
        <p class="mt-1 text-sm text-muted-foreground">{{ $t('studySets.no_sets_description') }}</p>
      </div>

      <!-- Filters returned no results -->
      <div v-else-if="filteredStudySets.length === 0" class="flex flex-col items-center py-16">
        <div class="rounded-full bg-muted/60 p-4">
          <Search class="size-8 text-muted-foreground/60" />
        </div>
        <p class="mt-4 text-sm font-medium text-foreground">{{ $t('studySets.no_results') }}</p>
        <p class="mt-1 text-sm text-muted-foreground">{{ $t('studySets.no_results_description') }}</p>
      </div>

      <div v-else class="grid gap-4 lg:grid-cols-2">
        <div
          v-for="set in filteredStudySets"
          :key="set.id"
          class="border rounded-lg bg-card transition-all duration-200 hover:shadow-lg hover:bg-accent/20"
        >
          <!-- Set header -->
          <div class="flex items-start justify-between gap-4 p-4 sm:p-5">
            <div class="min-w-0">
              <h2 class="text-base sm:text-lg font-semibold text-card-foreground leading-snug">
                {{ set.name }}
              </h2>
              <p v-if="set.description" class="mt-1 text-sm text-muted-foreground">
                {{ set.description }}
              </p>
              <Badge v-if="searchAllFaculties && set._tenantLabel" variant="outline" class="mt-1.5 text-xs font-normal text-primary">
                <GraduationCap class="size-3 mr-1" />
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
          <div v-if="getVisibleCourses(set).length > 0" class="px-4 sm:px-5 pb-1">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-t border-border/50">
                  <th class="py-2.5 pr-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    {{ $t('studySets.course_name') }}
                  </th>
                  <th class="py-2.5 pr-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    {{ $t('studySets.semester') }}
                  </th>
                  <th class="py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    {{ $t('studySets.credits_short') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border/40">
                <tr
                  v-for="course in getVisibleCourses(set)"
                  :key="course.id"
                  class="transition-colors hover:bg-muted/30"
                >
                  <td class="py-3 pr-4">
                    <span class="font-medium text-card-foreground">{{ course.name }}</span>
                  </td>
                  <td class="py-3 pr-4">
                    <Badge
                      variant="outline"
                      :class="[
                        'text-xs',
                        course.semester === 'autumn'
                          ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-400'
                          : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400'
                      ]"
                    >
                      {{ course.semester === 'autumn' ? $t('studySets.autumn') : $t('studySets.spring') }}
                    </Badge>
                  </td>
                  <td class="py-3 text-right tabular-nums text-muted-foreground font-medium">
                    {{ course.credits }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Reviews accordion -->
          <div v-if="getReviewsForSet(set).length > 0" class="px-4 sm:px-5 border-t border-border/50 mt-1">
            <Accordion type="single" collapsible class="w-full">
              <AccordionItem value="reviews" class="border-0">
                <AccordionTrigger class="py-3.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:no-underline">
                  {{ $t('studySets.reviews') }}
                  <Badge variant="secondary" class="ml-2 text-xs">
                    {{ getReviewsForSet(set).length }}
                  </Badge>
                </AccordionTrigger>
                <AccordionContent>
                  <div class="grid gap-3 pb-4 sm:grid-cols-2">
                    <div
                      v-for="review in getReviewsForSet(set)"
                      :key="review.id"
                      class="rounded-lg bg-muted/40 p-4 border border-border/50"
                    >
                      <p class="text-sm text-muted-foreground leading-relaxed">{{ review.comment }}</p>
                      <p class="mt-3 text-xs font-semibold text-foreground">
                        &mdash; {{ review.lecturer }}
                      </p>
                    </div>
                  </div>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
          </div>

          <!-- Footer with updated date -->
          <div class="flex items-center justify-end px-4 sm:px-5 pb-3 pt-1">
            <span class="text-xs text-muted-foreground/60">
              {{ $t('studySets.updated', { date: set.updated_at }) }}
            </span>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="flex flex-col items-center py-16">
      <div class="rounded-full bg-muted/60 p-4">
        <FileX class="size-8 text-muted-foreground/60" />
      </div>
      <p class="mt-4 text-sm font-medium text-foreground">{{ $t('studySets.no_sets') }}</p>
      <p class="mt-1 text-sm text-muted-foreground">{{ $t('studySets.no_sets_description') }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Head } from "@inertiajs/vue3";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { usePageBreadcrumbs, BreadcrumbHelpers, createBreadcrumbItem } from "@/Composables/useBreadcrumbsUnified";

import { Button } from "@/Components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/Components/ui/accordion";
import { Badge } from "@/Components/ui/badge";
import { BookOpen, FileX, GraduationCap, Search, X } from "lucide-vue-next";
import { formatVuFacultyShortname } from "@/Utils/Tenant";

import IFluentDocument16Regular from "~icons/fluent/document-16-regular";

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
      IFluentDocument16Regular
    ),
  ]);
});

const tenantOptions = computed(() =>
  props.tenants.map((t) => ({
    value: String(t.id),
    label: formatVuFacultyShortname(t),
  }))
);

const getInitialTenantId = (): string => {
  const params = new URLSearchParams(window.location.search);
  const facultyParam = params.get("faculty");

  if (facultyParam && props.tenants.some((t) => String(t.id) === facultyParam)) {
    return facultyParam;
  }

  return props.tenants.length > 0 ? String(props.tenants[0].id) : "";
};

const selectedTenantId = ref(getInitialTenantId());
const searchQuery = ref("");
const selectedSemester = ref("__all__");
const searchAllFaculties = ref(false);

const hasActiveFilters = computed(() => searchQuery.value !== "" || selectedSemester.value !== "__all__" || searchAllFaculties.value);

const resetFilters = () => {
  searchQuery.value = "";
  selectedSemester.value = "__all__";
  searchAllFaculties.value = false;
};

watch(selectedTenantId, (newId) => {
  const url = new URL(window.location.href);

  if (newId && props.tenants.length > 0 && newId !== String(props.tenants[0].id)) {
    url.searchParams.set("faculty", newId);
  } else {
    url.searchParams.delete("faculty");
  }

  window.history.replaceState({}, "", url.toString());

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
    sets.map((set) => ({ ...set, _tenantLabel: tenantLabelMap.value[tenantId] }))
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
    if (semester !== "__all__") {
      const hasMatchingSemester = set.courses.some((c) => c.semester === semester);
      if (!hasMatchingSemester) return false;
    }

    // Search filter: match against set name, description, or any course name
    if (query) {
      const nameMatch = set.name.toLowerCase().includes(query);
      const descMatch = set.description?.toLowerCase().includes(query) ?? false;
      const courseMatch = set.courses.some((c) => c.name.toLowerCase().includes(query));
      if (!nameMatch && !descMatch && !courseMatch) return false;
    }

    return true;
  });
});

const getVisibleCourses = (set: StudySetData): CourseData[] => {
  if (selectedSemester.value === "__all__") return set.courses;
  return set.courses.filter((c) => c.semester === selectedSemester.value);
};

const getReviewsForSet = (set: StudySetData): ReviewData[] => {
  const courses = getVisibleCourses(set);
  return courses.flatMap((c) => c.reviews);
};
</script>
