<template>
  <Head>
    <title>{{ $t('studySets.page_title') }}</title>
    <meta name="description" :content="$t('studySets.page_description')">
  </Head>

  <div class="space-y-8">
    <div class="space-y-2">
      <h1 class="text-3xl font-bold tracking-tight">{{ $t('studySets.page_title') }}</h1>
      <p class="text-lg text-muted-foreground">{{ $t('studySets.page_description') }}</p>
    </div>

    <Tabs v-if="tenantTabs.length > 0" :default-value="tenantTabs[0]?.value">
      <TabsList class="flex flex-wrap h-auto gap-1">
        <TabsTrigger v-for="tab in tenantTabs" :key="tab.value" :value="tab.value" class="text-sm">
          {{ tab.label }}
        </TabsTrigger>
      </TabsList>

      <TabsContent v-for="tab in tenantTabs" :key="tab.value" :value="tab.value" class="mt-6">
        <div v-if="getStudySetsForTenant(tab.tenantId).length === 0"
          class="py-12 text-center text-muted-foreground">
          {{ $t('studySets.no_sets') }}
        </div>

        <div v-else class="space-y-8">
          <div v-for="set in getStudySetsForTenant(tab.tenantId)" :key="set.id"
            class="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-xl font-semibold">{{ set.name }}</h2>
                <p v-if="set.description" class="mt-1 text-muted-foreground">{{ set.description }}</p>
              </div>
              <Badge variant="secondary" class="shrink-0">
                {{ set.total_credits }} {{ $t('studySets.credits') }}
              </Badge>
            </div>

            <!-- Courses table -->
            <div v-if="set.courses.length > 0" class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b text-left">
                    <th class="pb-2 pr-4 font-medium">{{ $t('studySets.course_name') }}</th>
                    <th class="pb-2 pr-4 font-medium">{{ $t('studySets.semester') }}</th>
                    <th class="pb-2 font-medium text-right">{{ $t('studySets.credits_short') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="course in set.courses" :key="course.id" class="border-b last:border-0">
                    <td class="py-3 pr-4">
                      <span class="font-medium">{{ course.name }}</span>
                    </td>
                    <td class="py-3 pr-4">
                      <Badge variant="outline">
                        {{ course.semester === 'autumn' ? $t('studySets.autumn') : $t('studySets.spring') }}
                      </Badge>
                    </td>
                    <td class="py-3 text-right tabular-nums">
                      {{ course.credits }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Reviews accordion -->
            <Accordion v-if="getReviewsForSet(set).length > 0" type="single" collapsible class="w-full">
              <AccordionItem value="reviews" class="border-0">
                <AccordionTrigger class="text-sm font-medium py-2">
                  {{ $t('studySets.reviews') }} ({{ getReviewsForSet(set).length }})
                </AccordionTrigger>
                <AccordionContent>
                  <div class="space-y-4 pt-2">
                    <div v-for="review in getReviewsForSet(set)" :key="review.lecturer"
                      class="rounded-lg bg-muted/50 p-4">
                      <p class="text-sm font-medium">{{ review.lecturer }}</p>
                      <p class="mt-1 text-sm text-muted-foreground">{{ review.comment }}</p>
                    </div>
                  </div>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
          </div>
        </div>
      </TabsContent>
    </Tabs>

    <div v-else class="py-12 text-center text-muted-foreground">
      {{ $t('studySets.no_sets') }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { usePageBreadcrumbs, BreadcrumbHelpers, createBreadcrumbItem } from "@/Composables/useBreadcrumbsUnified";

import { Badge } from "@/Components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/Components/ui/accordion";

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

const tenantTabs = computed(() =>
  props.tenants
    .filter((t) => (props.studySetsByTenant[t.id] || []).length > 0)
    .map((t) => ({
      value: String(t.id),
      label: $t(t.shortname),
      tenantId: String(t.id),
    }))
);

const getStudySetsForTenant = (tenantId: string) => {
  return props.studySetsByTenant[tenantId] || [];
};

const getReviewsForSet = (set: StudySetData) => {
  return set.courses.flatMap((c) => c.reviews);
};
</script>
