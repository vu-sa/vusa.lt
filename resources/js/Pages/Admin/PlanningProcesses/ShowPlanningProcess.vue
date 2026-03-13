<template>
  <AdminContentPage>
    <Head :title="pageTitle" />

    <ShowPageHero
      :title="pageTitle"
      :subtitle="planningProcess.tenant?.shortname"
      :icon="CalendarLtr24Regular"
    >
      <template #actions>
        <Badge v-if="isFinished" variant="success">
          {{ $t("Užbaigta") }}
        </Badge>
        <Button
          v-if="canUpdate && !isFinished && planningProcess.current_stage === 5"
          variant="default"
          size="sm"
          :disabled="advanceForm.processing"
          @click="advanceStage"
        >
          {{ $t("Užbaigti procesą") }}
        </Button>
        <Button
          v-if="canUpdate && !isFinished && planningProcess.current_stage < 5"
          variant="outline"
          size="sm"
          :disabled="advanceForm.processing"
          @click="advanceStage"
        >
          {{ $t("Pereiti į kitą etapą") }}
        </Button>
        <Button
          v-if="canDelete"
          variant="destructive"
          size="sm"
          @click="showDeleteDialog = true"
        >
          {{ $t("Šalinti") }}
        </Button>
      </template>
    </ShowPageHero>

    <!-- Stage stepper -->
    <StageStepper :current-stage="planningProcess.current_stage" class="mt-6" />

    <div class="mt-8 flex flex-col gap-6">
      <!-- Moderator assignment -->
      <ModeratorAssignment
        :planning-process="planningProcess"
        :can-update="canUpdate"
      />

      <!-- Stage I: Expectations -->
      <StageExpectations
        :planning-process="planningProcess"
        :can-update="canUpdate"
      />

      <!-- Stage II: Meetings & Goal -->
      <StageMeetings
        :planning-process="planningProcess"
        :tenant-problems="tenantProblems"
        :can-update="canUpdate"
      />

      <!-- Stage III: Documents -->
      <StageDocuments
        :planning-process="planningProcess"
        :can-update="canUpdate"
      />

      <!-- Stage IV: Activity Grid -->
      <ActivityGrid
        :planning-process="planningProcess"
        :can-update="canUpdate"
      />

      <!-- Stage V: Monitoring & Reflection -->
      <StageMonitoring
        :planning-process="planningProcess"
        :can-update="canUpdate"
      />
    </div>

    <!-- Delete dialog -->
    <Dialog v-model:open="showDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ $t("Šalinti planavimo procesą") }}</DialogTitle>
          <DialogDescription>
            {{ $t("Ar tikrai norite šalinti šį planavimo procesą? Šis veiksmas negrįžtamas.") }}
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="showDeleteDialog = false">
            {{ $t("Atšaukti") }}
          </Button>
          <Button
            variant="destructive"
            :disabled="deleteForm.processing"
            @click="destroyProcess"
          >
            {{ $t("Šalinti") }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useForm, Head } from "@inertiajs/vue3";
import { transChoice as $tChoice, trans as $t } from "laravel-vue-i18n";
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import ShowPageHero from "@/Components/Hero/ShowPageHero.vue";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import Icons from "@/Types/Icons/regular";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/Components/ui/dialog";
import StageStepper from "@/Components/PlanningProcess/StageStepper.vue";
import ModeratorAssignment from "@/Components/PlanningProcess/ModeratorAssignment.vue";
import StageExpectations from "@/Components/PlanningProcess/StageExpectations.vue";
import StageMeetings from "@/Components/PlanningProcess/StageMeetings.vue";
import StageDocuments from "@/Components/PlanningProcess/StageDocuments.vue";
import ActivityGrid from "@/Components/PlanningProcess/ActivityGrid.vue";
import StageMonitoring from "@/Components/PlanningProcess/StageMonitoring.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  tenantProblems: App.Entities.Problem[];
  canUpdate: boolean;
  canDelete: boolean;
  isFinished: boolean;
}>();

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    $tChoice("entities.planningProcess.model", 2),
    "planningProcesses.index",
    {},
    pageTitle.value,
    Icons.PLANNING_PROCESS,
  )
);

const showDeleteDialog = ref(false);

const pageTitle = computed(() => {
  const year = props.planningProcess.academic_year_start;
  const tenant = props.planningProcess.tenant?.shortname ?? "";
  return `${tenant} ${year}–${year + 1}`;
});

const advanceForm = useForm({});
const deleteForm = useForm({});

const advanceStage = () => {
  advanceForm.patch(route("planningProcesses.advanceStage", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const destroyProcess = () => {
  deleteForm.delete(route("planningProcesses.destroy", props.planningProcess.id), {
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};
</script>
