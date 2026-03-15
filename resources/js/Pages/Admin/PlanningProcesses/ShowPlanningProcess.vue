<template>
  <AdminContentPage>
    <Head :title="pageTitle" />

    <PageHero>
      <div class="flex items-start gap-4">
        <GoalIcon class="h-16 w-16 sm:h-20 sm:w-20 text-primary shrink-0" />
        <div>
          <h1 class="text-3xl font-bold tracking-tight sm:text-4xl text-foreground flex items-center gap-3">
            {{ planningProcess.tenant?.shortname }} {{ capitalize($tChoice("entities.planningProcess.model", 2)) }}
            <Badge v-if="isFinished" variant="success" class="gap-1">
              <CheckCircleIcon class="h-3 w-3" />
              {{ $t("Užbaigta") }}
            </Badge>
          </h1>
          <p class="mt-0.5 text-lg text-muted-foreground font-medium">
            {{ planningProcess.academic_year_start }}–{{ planningProcess.academic_year_start + 1 }}
          </p>
          <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <div class="flex items-center gap-1.5">
              <UserIcon class="h-3.5 w-3.5" />
              <span class="font-medium">{{ $t("Moderatorius") }}:</span>
              <span v-if="planningProcess.moderator">{{ planningProcess.moderator.name }}</span>
              <span v-else class="italic">{{ $t("Nepriskirtas") }}</span>
            </div>
            <div v-if="editors.length > 0" class="flex items-center gap-1.5">
              <component :is="editors.length === 1 ? UserIcon : UsersIcon" class="h-3.5 w-3.5" />
              <span class="font-medium">{{ editors.length === 1 ? $t("Atsakingas asmuo") : $t("Atsakingi asmenys") }}:</span>
              <span>{{ editors.map(e => e.name).join(', ') }}</span>
            </div>
          </div>
        </div>
      </div>
      <template #actions>
        <div class="flex items-center gap-2">
          <FieldChangeHistory v-if="canViewFieldChanges" :changes="fieldChanges" />
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
        </div>
      </template>
      <template #content>
        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
          <StageStepper
            :current-stage="planningProcess.current_stage"
            :selected-stage="selectedStage"
            :is-finished="isFinished"
            :deadlines="deadlines"
            :can-view-expectations="canViewExpectations"
            @select="selectStage"
          />
        </div>
      </template>
    </PageHero>

    <div class="mt-6">
      <!-- Overview (stage 0) -->
      <div v-if="selectedStage === 0" class="flex flex-col gap-4">
        <StageOverview
          :planning-process="planningProcess"
          :deadlines="deadlines"
          :editors="editors"
          :can-update="canUpdate"
          :can-manage-editors="canManageEditors"
          :can-view-expectations="canViewExpectations"
          :is-finished="isFinished"
          :approval-history="approvalHistory"
          @edit-team="selectedStage = -1"
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

        <!-- Team management (stage -1) -->
        <div v-if="selectedStage === -1" class="flex flex-col gap-4">
          <ModeratorAssignment
            :planning-process="planningProcess"
            :can-assign-moderator="canAssignModerator"
          />
          <EditorsManagement
            :planning-process="planningProcess"
            :editors="editors"
            :can-manage-editors="canManageEditors"
            :is-moderator="isModerator"
          />
        </div>

        <!-- Stage I -->
        <StageExpectations
          v-else-if="selectedStage === 1"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(1)"
          :can-update="canUpdate"
          :can-view-expectations="canViewExpectations"
          :comments="stageComments?.[1] ?? []"
        />

        <!-- Stage II -->
        <StageMeetings
          v-else-if="selectedStage === 2"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(2)"
          :tenant-problems="tenantProblems"
          :can-update="canUpdate"
          :can-approve="canApprove"
          :comments="stageComments?.[2] ?? []"
          :goal-approvals="approvalHistory?.goal ?? []"
        />

        <!-- Stage III -->
        <StageDocuments
          v-else-if="selectedStage === 3"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(3)"
          :can-update="canUpdate"
          :can-approve="canApprove"
          :can-manage-templates="canManageTemplates"
          :comments="stageComments?.[3] ?? []"
          :tip-documents="tipDocuments"
          :mvp-documents="mvpDocuments"
          :tip-approvals="approvalHistory?.tip_document ?? []"
          :mvp-approvals="approvalHistory?.mvp_document ?? []"
        />

        <!-- Stage IV -->
        <ActivityGrid
          v-else-if="selectedStage === 4"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(4)"
          :can-update="canUpdate"
          :comments="stageComments?.[4] ?? []"
        />

        <!-- Stage V -->
        <StageMonitoring
          v-else-if="selectedStage === 5"
          :planning-process="planningProcess"
          :deadline="deadlineForStage(5)"
          :can-update="canUpdate"
          :comments="stageComments?.[5] ?? []"
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
import { ref, computed, capitalize } from "vue";
import { useForm, Head } from "@inertiajs/vue3";
import { transChoice as $tChoice, trans as $t } from "laravel-vue-i18n";
import {
  Goal as GoalIcon,
  User as UserIcon,
  Users as UsersIcon,
  CheckCircle as CheckCircleIcon,
  ArrowRight as ArrowRightIcon,
  ArrowLeft as ArrowLeftIcon,
  MoreHorizontal as MoreHorizontalIcon,
  Trash2 as Trash2Icon,
} from "lucide-vue-next";

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import PageHero from "@/Components/Hero/PageHero.vue";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
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
import EditorsManagement from "@/Components/PlanningProcess/EditorsManagement.vue";
import FieldChangeHistory from "@/Components/PlanningProcess/FieldChangeHistory.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadlines: App.Entities.PlanningStageDeadline[];
  tenantProblems: App.Entities.Problem[];
  stageComments: Record<number, App.Entities.Comment[]>;
  tipDocuments: App.Entities.DocumentVersion[];
  mvpDocuments: App.Entities.DocumentVersion[];
  approvalHistory: Record<string, App.Entities.ApprovalRecord[]>;
  fieldChanges: App.Entities.FieldChange[];
  editors: Array<{ id: string; name: string }>;
  canUpdate: boolean;
  canApprove: boolean;
  canDelete: boolean;
  canManageEditors: boolean;
  canAssignModerator: boolean;
  canManageTemplates: boolean;
  canViewExpectations: boolean;
  canViewFieldChanges: boolean;
  isModerator: boolean;
  isFinished: boolean;
}>();

const deadlineForStage = (stage: number) =>
  props.deadlines?.find((d) => d.stage === stage) ?? null;

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    capitalize($tChoice("entities.planningProcess.model", 2)),
    "planavimai.index",
    {},
    pageTitle.value,
    GoalIcon,
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
  if (stage === 1 && !props.canViewExpectations) return;
  selectedStage.value = stage;
};

const advanceForm = useForm({});
const deleteForm = useForm({});

const advanceStage = () => {
  advanceForm.patch(route("planavimai.advanceStage", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const destroyProcess = () => {
  deleteForm.delete(route("planavimai.destroy", props.planningProcess.id), {
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};
</script>
