<template>
  <PageContent
    :title="`${$t('Planavimo etapų datos')} ${academicYear}–${academicYear + 1}`"
    :back-url="route('planningProcesses.index')"
    :heading-icon="CalendarLtr24Regular"
  >
    <UpsertModelLayout>
      <form @submit.prevent="submit">
        <div class="flex flex-col gap-6">
          <div
            v-for="stage in localDeadlines"
            :key="stage.stage"
            class="grid gap-4 border rounded-lg p-4"
          >
            <h3 class="font-medium text-sm">{{ $t("Etapas") }} {{ stage.stage }}</h3>
            <div class="grid sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1">
                <Label :for="`stage-${stage.stage}-starts`">{{ $t("Pradžia") }}</Label>
                <Input
                  :id="`stage-${stage.stage}-starts`"
                  v-model="stage.starts_at"
                  type="date"
                />
              </div>
              <div class="flex flex-col gap-1">
                <Label :for="`stage-${stage.stage}-ends`">{{ $t("Pabaiga") }}</Label>
                <Input
                  :id="`stage-${stage.stage}-ends`"
                  v-model="stage.ends_at"
                  type="date"
                />
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-4">
            <Button type="button" variant="outline" as-child>
              <Link :href="route('planningProcesses.index')">{{ $t("Atšaukti") }}</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
              {{ $t("Išsaugoti") }}
            </Button>
          </div>
        </div>
      </form>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { reactive } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import Icons from "@/Types/Icons/regular";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

const props = defineProps<{
  academicYear: number;
  deadlines: App.Entities.PlanningStageDeadline[];
}>();

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm(
    $tChoice("entities.planningProcess.model", 2),
    "planningProcesses.index",
    `${$t("Planavimo etapų datos")} ${props.academicYear}–${props.academicYear + 1}`,
    Icons.PLANNING_PROCESS,
  )
);

// Ensure all 5 stages are present, filling in blanks if needed
const localDeadlines = reactive(
  [1, 2, 3, 4, 5].map((stage) => {
    const existing = props.deadlines.find((d) => d.stage === stage);
    return {
      stage,
      starts_at: existing?.starts_at ?? "",
      ends_at: existing?.ends_at ?? "",
    };
  })
);

const form = useForm({ deadlines: localDeadlines });

const submit = () => {
  form.deadlines = localDeadlines;
  form.patch(route("planningDeadlines.update", props.academicYear), {
    preserveScroll: true,
  });
};
</script>
