<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
    <!-- Team card (moderator + editors) -->
    <Card class="sm:col-span-2 xl:col-span-1">
      <CardContent class="p-4">
        <div class="flex items-center gap-3">
          <div class="shrink-0 h-10 w-10 rounded-full bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
            <UserIcon class="h-5 w-5 text-primary" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-muted-foreground font-medium">{{ $t("Moderatorius") }}</p>
            <p v-if="planningProcess.moderator" class="text-sm font-semibold truncate">
              {{ planningProcess.moderator.name }}
            </p>
            <p v-else class="text-sm text-muted-foreground italic">{{ $t("Nepriskirtas") }}</p>
          </div>
          <Button v-if="canUpdate || canManageEditors" variant="ghost" size="icon" class="shrink-0 h-8 w-8" @click="$emit('editTeam')">
            <PencilIcon class="h-3.5 w-3.5" />
          </Button>
        </div>
        <div v-if="editors && editors.length > 0" class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
          <p class="text-xs text-muted-foreground font-medium mb-1.5">{{ $t("Atsakingi asmenys") }}</p>
          <div class="flex flex-wrap gap-1.5">
            <Badge v-for="editor in editors" :key="editor.id" variant="secondary" class="text-xs">
              {{ editor.name }}
            </Badge>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Progress card -->
    <Card>
      <CardContent class="p-4">
        <div class="flex items-center gap-3">
          <div class="shrink-0 h-10 w-10 rounded-full bg-green-500/10 dark:bg-green-500/20 flex items-center justify-center">
            <TrendingUpIcon class="h-5 w-5 text-green-600 dark:text-green-400" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-muted-foreground font-medium">{{ $t("Progresas") }}</p>
            <p class="text-sm font-semibold">{{ completedStages }} / 5 {{ $t("etapų") }}</p>
          </div>
        </div>
        <Progress :model-value="progressPercent" class="mt-3 h-1.5" />
      </CardContent>
    </Card>

    <!-- Activities card -->
    <Card>
      <CardContent class="p-4">
        <div class="flex items-center gap-3">
          <div class="shrink-0 h-10 w-10 rounded-full bg-blue-500/10 dark:bg-blue-500/20 flex items-center justify-center">
            <LayoutGridIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-muted-foreground font-medium">{{ $t("Veiklos") }}</p>
            <p class="text-sm font-semibold">{{ activitiesCount }} {{ $t("suplanuota") }}</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Deadline card -->
    <Card v-if="!isFinished" class="sm:col-span-2 xl:col-span-3">
      <CardContent class="p-4">
        <div class="flex items-center gap-3">
          <div
            class="shrink-0 h-10 w-10 rounded-full flex items-center justify-center"
            :class="currentDeadline
              ? (deadlineDaysRemaining !== null && deadlineDaysRemaining < 0
                ? 'bg-red-500/10 dark:bg-red-500/20'
                : deadlineDaysRemaining !== null && deadlineDaysRemaining <= 7
                  ? 'bg-amber-500/10 dark:bg-amber-500/20'
                  : 'bg-green-500/10 dark:bg-green-500/20')
              : 'bg-zinc-100 dark:bg-zinc-800'"
          >
            <ClockIcon
              class="h-5 w-5"
              :class="currentDeadline
                ? (deadlineDaysRemaining !== null && deadlineDaysRemaining < 0
                  ? 'text-red-600 dark:text-red-400'
                  : deadlineDaysRemaining !== null && deadlineDaysRemaining <= 7
                    ? 'text-amber-600 dark:text-amber-400'
                    : 'text-green-600 dark:text-green-400')
                : 'text-muted-foreground'"
            />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-muted-foreground font-medium">{{ $t("planning.current_stage_deadline") }}</p>
            <p v-if="currentDeadline" class="text-sm font-semibold">
              {{ new Date(currentDeadline.ends_at).toLocaleDateString("lt-LT") }}
            </p>
            <p v-else class="text-sm text-muted-foreground italic">{{ $t("planning.no_deadline_set") }}</p>
          </div>
          <Badge
            v-if="currentDeadline && deadlineDaysRemaining !== null"
            :variant="deadlineDaysRemaining < 0 ? 'destructive' : deadlineDaysRemaining <= 7 ? 'warning' : 'success'"
            class="shrink-0"
          >
            <template v-if="deadlineDaysRemaining < 0">
              {{ $t("planning.deadline_overdue", { days: Math.abs(deadlineDaysRemaining) }) }}
            </template>
            <template v-else>
              {{ $t("planning.deadline_days_remaining", { days: deadlineDaysRemaining }) }}
            </template>
          </Badge>
        </div>
      </CardContent>
    </Card>
  </div>

  <!-- Stage checklist -->
  <Card class="mt-4">
    <CardHeader class="pb-3">
      <CardTitle class="text-base flex items-center gap-2">
        <ClipboardListIcon class="h-4.5 w-4.5 text-primary" />
        {{ $t("Etapų apžvalga") }}
      </CardTitle>
    </CardHeader>
    <CardContent class="flex flex-col gap-1">
      <button
        v-for="stage in stageItems"
        :key="stage.number"
        type="button"
        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors hover:bg-muted/50"
        :class="stage.restricted && 'opacity-50 cursor-not-allowed'"
        @click="handleNavigate(stage.number)"
      >
        <div
          class="shrink-0 h-7 w-7 rounded-full flex items-center justify-center text-xs font-medium"
          :class="stage.completed
            ? 'bg-green-500/10 text-green-600 dark:bg-green-500/20 dark:text-green-400'
            : stage.current
              ? 'bg-primary/10 text-primary dark:bg-primary/20'
              : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'"
        >
          <LockIcon v-if="stage.restricted" class="h-4 w-4" />
          <CheckCircleIcon v-else-if="stage.completed" class="h-4 w-4" />
          <CircleDotIcon v-else-if="stage.current" class="h-4 w-4" />
          <CircleIcon v-else class="h-4 w-4" />
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <p class="text-sm font-medium" :class="stage.completed ? 'text-green-700 dark:text-green-400' : ''">
              {{ stage.title }}
            </p>
            <span v-if="formatDeadlineRange(stage.number)" class="text-[10px] text-muted-foreground">
              {{ formatDeadlineRange(stage.number) }}
            </span>
            <Badge
              v-if="isStageOverdue(stage.number) && !stage.completed"
              variant="destructive"
              class="text-[10px] px-1.5 py-0"
            >
              {{ $t("Vėluojama") }}
            </Badge>
            <Badge
              v-if="hasRejection(stage.number) && !stage.completed"
              variant="warning"
              class="text-[10px] px-1.5 py-0"
            >
              {{ $t("Reikia pataisymų") }}
            </Badge>
          </div>
          <p class="text-xs text-muted-foreground">{{ stage.description }}</p>
        </div>
        <ChevronRightIcon class="h-4 w-4 text-muted-foreground shrink-0" />
      </button>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import {
  User as UserIcon,
  TrendingUp as TrendingUpIcon,
  LayoutGrid as LayoutGridIcon,
  ClipboardList as ClipboardListIcon,
  Clock as ClockIcon,
  CheckCircle as CheckCircleIcon,
  CircleDot as CircleDotIcon,
  Circle as CircleIcon,
  Lock as LockIcon,
  ChevronRight as ChevronRightIcon,
  Pencil as PencilIcon,
} from "lucide-vue-next";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Progress } from "@/Components/ui/progress";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadlines?: App.Entities.PlanningStageDeadline[];
  editors?: Array<{ id: string; name: string }>;
  canUpdate: boolean;
  canManageEditors?: boolean;
  canViewExpectations?: boolean;
  isFinished: boolean;
  approvalHistory?: Record<string, App.Entities.ApprovalRecord[]>;
}>();

const emit = defineEmits<{
  editTeam: [];
  navigateToStage: [stage: number];
}>();

const handleNavigate = (stageNumber: number) => {
  if (stageNumber === 1 && !props.canViewExpectations) return;
  emit("navigateToStage", stageNumber);
};

const completedStages = computed(() => {
  if (props.isFinished) return 5;
  return Math.max(0, props.planningProcess.current_stage - 1);
});

const progressPercent = computed(() => (completedStages.value / 5) * 100);

const activitiesCount = computed(
  () => (props.planningProcess.activities ?? []).length
);

const currentDeadline = computed(() => {
  if (props.isFinished) return null;
  return props.deadlines?.find((d) => d.stage === props.planningProcess.current_stage) ?? null;
});

const deadlineDaysRemaining = computed(() => {
  if (!currentDeadline.value) return null;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const endsAt = new Date(currentDeadline.value.ends_at);
  endsAt.setHours(0, 0, 0, 0);
  return Math.ceil((endsAt.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
});

const deadlineForStage = (stageNumber: number) =>
  props.deadlines?.find((d) => d.stage === stageNumber) ?? null;

const formatDeadlineRange = (stageNumber: number) => {
  const deadline = deadlineForStage(stageNumber);
  if (!deadline) return null;
  const fmt = (v: string) => {
    const d = new Date(v);
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${mm}-${dd}`;
  };
  return `(${fmt(deadline.starts_at)} – ${fmt(deadline.ends_at)})`;
};

const isStageOverdue = (stageNumber: number) => {
  const deadline = deadlineForStage(stageNumber);
  if (!deadline) return false;
  if (props.isFinished || stageNumber < props.planningProcess.current_stage) return false;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const endsAt = new Date(deadline.ends_at);
  endsAt.setHours(0, 0, 0, 0);
  return today > endsAt;
};

const hasRejection = (stageNumber: number) => {
  if (!props.approvalHistory) return false;
  if (stageNumber === 2) {
    const goalApprovals = props.approvalHistory.goal ?? [];
    return goalApprovals.length > 0 && goalApprovals[0]?.decision === "rejected" && !props.planningProcess.goal_approved_at;
  }
  if (stageNumber === 3) {
    const tipApprovals = props.approvalHistory.tip_document ?? [];
    const mvpApprovals = props.approvalHistory.mvp_document ?? [];
    const tipRejected = tipApprovals.length > 0 && tipApprovals[0]?.decision === "rejected" && !props.planningProcess.tip_approved_at;
    const mvpRejected = mvpApprovals.length > 0 && mvpApprovals[0]?.decision === "rejected" && !props.planningProcess.mvp_approved_at;
    return tipRejected || mvpRejected;
  }
  return false;
};

const stageItems = computed(() => {
  const cs = props.planningProcess.current_stage;
  return [
    {
      number: 1,
      title: $t("I. Pasiruošimas"),
      description: $t("Lūkesčių suformulavimas moderatoriui"),
      completed: props.isFinished || cs > 1,
      current: cs === 1 && !props.isFinished,
      restricted: !props.canViewExpectations,
    },
    {
      number: 2,
      title: $t("II. Susitikimai ir tikslas"),
      description: $t("Susitikimai, problemos pasirinkimas, tikslo formulavimas"),
      completed: props.isFinished || cs > 2,
      current: cs === 2 && !props.isFinished,
      restricted: false,
    },
    {
      number: 3,
      title: $t("III. Dokumentai"),
      description: $t("TĮP ir MVP dokumentų įkėlimas ir tvirtinimas"),
      completed: props.isFinished || cs > 3,
      current: cs === 3 && !props.isFinished,
      restricted: false,
    },
    {
      number: 4,
      title: $t("IV. Metų veiklos tinklelis"),
      description: $t("Padalinio veiklų sąrašas su lygmenimis"),
      completed: props.isFinished || cs > 4,
      current: cs === 4 && !props.isFinished,
      restricted: false,
    },
    {
      number: 5,
      title: $t("V. Monitoringas ir refleksija"),
      description: $t("Ketvirtinė stebėsena ir metų pabaigos refleksija"),
      completed: props.isFinished || cs > 5,
      current: cs === 5 && !props.isFinished,
      restricted: false,
    },
  ];
});
</script>
