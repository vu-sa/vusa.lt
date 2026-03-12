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

    <!-- Study sets for selected faculty -->
    <template v-if="selectedTenantId">
      <div v-if="currentStudySets.length === 0" class="flex flex-col items-center py-16">
        <div class="rounded-full bg-muted/60 p-4">
          <FileX class="size-8 text-muted-foreground/60" />
        </div>
        <p class="mt-4 text-sm font-medium text-foreground">{{ $t('studySets.no_sets') }}</p>
        <p class="mt-1 text-sm text-muted-foreground">{{ $t('studySets.no_sets_description') }}</p>
      </div>

      <div v-else class="grid gap-4 lg:grid-cols-2">
        <div
          v-for="set in currentStudySets"
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
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <Badge variant="outline" class="tabular-nums text-xs text-muted-foreground">
                {{ $tChoice('studySets.course_count', set.courses.length, { count: String(set.courses.length) }) }}
              </Badge>
              <Badge variant="secondary" class="tabular-nums">
                {{ set.total_credits }} {{ $t('studySets.credits') }}
              </Badge>
            </div>
          </div>

          <!-- Courses table -->
          <div v-if="set.courses.length > 0" class="px-4 sm:px-5 pb-1">
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
                  v-for="course in set.courses"
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
                      :key="review.lecturer"
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

          <!-- Bottom spacer when no reviews -->
          <div v-else class="h-2" />
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

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/Components/ui/accordion";
import { Badge } from "@/Components/ui/badge";
import { BookOpen, FileX, GraduationCap } from "lucide-vue-next";

import IFluentDocument16Regular from "~icons/fluent/document-16-regular";

interface CourseData {
  id: string;
  name: string;
  semester: string;
  credits: number;
  reviews: ReviewData[];
}

interface ReviewData {
  lecturer: string;
  comment: string;
}

interface StudySetData {
  id: string;
  name: string;
  description: string | null;
  total_credits: number;
  courses: CourseData[];
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
    label: $t(t.shortname),
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

watch(selectedTenantId, (newId) => {
  const url = new URL(window.location.href);

  if (newId && props.tenants.length > 0 && newId !== String(props.tenants[0].id)) {
    url.searchParams.set("faculty", newId);
  } else {
    url.searchParams.delete("faculty");
  }

  window.history.replaceState({}, "", url.toString());
});

const currentStudySets = computed(() => {
  return props.studySetsByTenant[selectedTenantId.value] || [];
});

const getReviewsForSet = (set: StudySetData) => {
  return set.courses.flatMap((c) => c.reviews);
};
</script>
