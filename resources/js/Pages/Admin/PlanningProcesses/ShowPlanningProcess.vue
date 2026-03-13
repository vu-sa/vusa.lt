<template>
  <AdminContentPage>
    <Head :title="pageTitle" />

    <ShowPageHero
      :title="pageTitle"
      :icon="CalendarLtr24Regular"
    >
      <template #badge>
        <Badge v-if="isFinished" variant="success" class="gap-1">
          <CheckCircleIcon class="h-3 w-3" />
          {{ $t("Užbaigta") }}
        </Badge>
        <Badge v-else variant="secondary" class="gap-1">
          {{ $t("Etapas") }} {{ planningProcess.current_stage }} / 5
        </Badge>
      </template>

      <template #subtitle>
        {{ planningProcess.tenant?.shortname }}
      </template>

      <template #info>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
          <UserIcon class="h-3.5 w-3.5" />
          <span v-if="planningProcess.moderator">{{ planningProcess.moderator.name }}</span>
          <span v-else class="italic">{{ $t("Moderatorius nepriskirtas") }}</span>
        </div>
      </template>

      <template #actions>
        <Button
          v-if="canUpdate && !isFinished && planningProcess.current_stage === 4"
          variant="outline"
          size="sm"
          class="gap-1.5"
          :disabled="advanceForm.processing"
          @click="advanceStage"
        >
          <ArrowRightIcon class="h-3.5 w-3.5" />
          {{ $t("Pereiti į kitą etapą") }}
        </Button>
        <DropdownMenu v-if="canDelete">
          <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-8 w-8">
              <MoreHorizontalIcon class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem
              class="text-destructive focus:text-destructive"
              @click="showDeleteDialog = true"
            >
              <Trash2Icon class="h-4 w-4 mr-2" />
              {{ $t("Šalinti procesą") }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </template>

      <template #navigation>
        <StageStepper
          :current-stage="planningProcess.current_stage"
          :selected-stage="selectedStage"
          :is-finished="isFinished"
          :deadlines="deadlines"
          @select="selectStage"
        />
      </template>
    </ShowPageHero>

    <div class="mt-6">
      <!-- Overview (stage 0) -->
      <div v-if="selectedStage === 0">
        <StageOverview
          :planning-process="planningProcess"
          :deadlines="deadlines"
          :can-update="canUpdate"
          :is-finished="isFinished"
          @edit-moderator="selectedStage = -1"
          @navigate-to-stage="selectStage"
        />
      </div>

      <!-- Any non-overview stage -->
      <div v-else>
        <div class="mb-4">
          <Button variant="ghost" size="sm" class="gap-1.5 -ml-2" @click="selectedStage = 0">
            <ArrowLeftIcon class="h-3.5 w-3.5" />
            {{ $t("Grįžti į apžvalgą") }}
          </Button>
        </div>

        <!-- Moderator assignment (stage -1) -->
        <ModeratorAssignment
          v-if="selectedStage === -1"
          :planning-process="planningProcess"
          :can-update="canUpdate"
        />

        <!-- Stage I -->
        <StageExpectations
          v-else-if="selectedStage === 1"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(1)"
          :can-update="canUpdate"
        />

        <!-- Stage II -->
        <StageMeetings
          v-else-if="selectedStage === 2"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(2)"
          :tenant-problems="tenantProblems"
          :can-update="canUpdate"
        />

        <!-- Stage III -->
        <StageDocuments
          v-else-if="selectedStage === 3"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(3)"
          :can-update="canUpdate"
        />

        <!-- Stage IV -->
        <ActivityGrid
          v-else-if="selectedStage === 4"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(4)"
          :can-update="canUpdate"
        />

        <!-- Stage V -->
        <StageMonitoring
          v-else-if="selectedStage === 5"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(5)"
          :can-update="canUpdate"
        />
      </div>
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
import {
  User as UserIcon,
  CheckCircle as CheckCircleIcon,
  ArrowRight as ArrowRightIcon,
  ArrowLeft as ArrowLeftIcon,
  MoreHorizontal as MoreHorizontalIcon,
  Trash2 as Trash2Icon,
} from "lucide-vue-next";

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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import StageStepper from "@/Components/PlanningProcess/StageStepper.vue";
import StageOverview from "@/Components/PlanningProcess/StageOverview.vue";
import ModeratorAssignment from "@/Components/PlanningProcess/ModeratorAssignment.vue";
import StageExpectations from "@/Components/PlanningProcess/StageExpectations.vue";
import StageMeetings from "@/Components/PlanningProcess/StageMeetings.vue";
import StageDocuments from "@/Components/PlanningProcess/StageDocuments.vue";
import ActivityGrid from "@/Components/PlanningProcess/ActivityGrid.vue";
import StageMonitoring from "@/Components/PlanningProcess/StageMonitoring.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadlines: App.Entities.PlanningStageDeadline[];
  tenantProblems: App.Entities.Problem[];
  canUpdate: boolean;
  canDelete: boolean;
  isFinished: boolean;
}>();

const deadlineForStage = (stage: number) =>
  props.deadlines?.find((d) => d.stage === stage) ?? null;

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
const selectedStage = ref(0); // 0 = overview

const pageTitle = computed(() => {
  const year = props.planningProcess.academic_year_start;
  const tenant = props.planningProcess.tenant?.shortname ?? "";
  return `${tenant} ${year}–${year + 1}`;
});

const selectStage = (stage: number) => {
  selectedStage.value = stage;
};

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
