<template>
  <nav aria-label="Planavimo etapai">
    <ol class="flex items-center w-full">
      <li
        v-for="(stage, index) in stages"
        :key="stage.number"
        class="flex items-center"
        :class="index < stages.length - 1 ? 'flex-1' : ''"
      >
        <button
          type="button"
          class="group flex flex-col items-center gap-1.5 min-w-0"
          :class="stage.number <= currentStage || isFinished ? 'cursor-pointer' : 'cursor-default'"
          @click="$emit('select', stage.number)"
        >
          <div
            class="relative flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-full text-sm font-semibold shrink-0 transition-all duration-200 ring-2 ring-offset-2 ring-offset-background"
            :class="stepClasses(stage.number)"
          >
            <CheckIcon v-if="isStageComplete(stage.number)" class="w-4 h-4 sm:w-5 sm:h-5" />
            <component
              :is="stage.icon"
              v-else-if="stage.number === selectedStage"
              class="w-4 h-4 sm:w-5 sm:h-5"
            />
            <span v-else>{{ stage.number }}</span>
          </div>
          <div class="hidden sm:flex flex-col items-center gap-0.5">
            <span
              class="text-xs font-medium leading-tight text-center transition-colors"
              :class="stepLabelClasses(stage.number)"
            >
              {{ stage.label }}
            </span>
            <span
              v-if="isStageComplete(stage.number)"
              class="text-[10px] text-green-600 dark:text-green-400"
            >
              {{ $t("Atlikta") }}
            </span>
            <span
              v-else-if="stage.number === currentStage && !isFinished"
              class="text-[10px] text-primary"
            >
              {{ $t("Vykdoma") }}
            </span>
            <span
              v-if="formatDeadlineDate(stage.number) && !isStageComplete(stage.number)"
              class="text-[10px]"
              :class="isStageOverdue(stage.number) ? 'text-red-500 dark:text-red-400 font-medium' : 'text-muted-foreground'"
            >
              {{ formatDeadlineDate(stage.number) }}
            </span>
          </div>
        </button>

        <!-- Connector line -->
        <div
          v-if="index < stages.length - 1"
          class="flex-1 h-0.5 mx-2 sm:mx-3 transition-colors duration-200"
          :class="isStageComplete(stage.number) ? 'bg-green-400 dark:bg-green-500' : 'bg-zinc-200 dark:bg-zinc-700'"
        />
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import {
  Check as CheckIcon,
  Sparkles,
  Users,
  FileText,
  LayoutGrid,
  BarChart3,
} from "lucide-vue-next";

const props = defineProps<{
  currentStage: number;
  selectedStage: number;
  isFinished?: boolean;
  deadlines?: App.Entities.PlanningStageDeadline[];
}>();

defineEmits<{
  select: [stage: number];
}>();

const stages = computed(() => [
  { number: 1, label: $t("Pasiruošimas"), icon: Sparkles },
  { number: 2, label: $t("Susitikimai"), icon: Users },
  { number: 3, label: $t("Dokumentai"), icon: FileText },
  { number: 4, label: $t("MVT"), icon: LayoutGrid },
  { number: 5, label: $t("Monitoringas"), icon: BarChart3 },
]);

const isStageComplete = (stageNumber: number) =>
  props.isFinished || stageNumber < props.currentStage;

const deadlineForStage = (stageNumber: number) =>
  props.deadlines?.find((d) => d.stage === stageNumber) ?? null;

const isStageOverdue = (stageNumber: number) => {
  if (isStageComplete(stageNumber) || props.isFinished) return false;
  if (stageNumber !== props.currentStage) return false;
  const deadline = deadlineForStage(stageNumber);
  if (!deadline) return false;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const endsAt = new Date(deadline.ends_at);
  endsAt.setHours(0, 0, 0, 0);
  return today > endsAt;
};

const formatDeadlineDate = (stageNumber: number) => {
  const deadline = deadlineForStage(stageNumber);
  if (!deadline) return null;
  const d = new Date(deadline.ends_at);
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  return `iki ${mm}-${dd}`;
};

const stepClasses = (stageNumber: number) => {
  if (isStageComplete(stageNumber)) {
    return "bg-green-500 text-white ring-green-500/30 dark:ring-green-500/20";
  }
  if (isStageOverdue(stageNumber)) {
    if (stageNumber === props.selectedStage) {
      return "bg-red-500 text-white ring-red-500/30 shadow-md";
    }
    return "bg-red-500/10 text-red-600 ring-red-500/20 dark:bg-red-500/20 dark:text-red-400";
  }
  if (stageNumber === props.selectedStage) {
    return "bg-primary text-primary-foreground ring-primary/30 shadow-md";
  }
  if (stageNumber === props.currentStage && !props.isFinished) {
    return "bg-primary/10 text-primary ring-primary/20 dark:bg-primary/20";
  }
  return "bg-zinc-100 text-zinc-400 ring-zinc-200/50 dark:bg-zinc-800 dark:text-zinc-500 dark:ring-zinc-700/50";
};

const stepLabelClasses = (stageNumber: number) => {
  if (isStageComplete(stageNumber)) {
    return "text-green-700 dark:text-green-400";
  }
  if (stageNumber === props.selectedStage || stageNumber === props.currentStage) {
    return "text-primary dark:text-primary";
  }
  return "text-muted-foreground";
};
</script>
