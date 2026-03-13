<template>
  <div class="flex flex-col gap-4">
    <DeadlineBanner :deadline="deadline ?? null" :is-stage-complete="!!planningProcess.reflection_submitted_at" />
    <!-- Quarterly monitoring -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="shrink-0 h-9 w-9 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
            <BarChart3Icon class="h-4.5 w-4.5 text-primary" />
          </div>
          <div class="flex-1 min-w-0">
            <CardTitle class="text-base">{{ $t("V etapas: Monitoringas ir refleksija") }}</CardTitle>
            <CardDescription>{{ $t("Ketvirtinė stebėsena ir metų pabaigos refleksija") }}</CardDescription>
          </div>
          <Badge variant="outline" class="shrink-0">
            {{ submittedQuarters }} / 4 {{ $t("ketvirtis", submittedQuarters) }}
          </Badge>
        </div>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="quarter in [1, 2, 3, 4] as const"
            :key="quarter"
            class="rounded-lg border p-4 flex flex-col gap-3 transition-colors"
            :class="getEntry(quarter) ? 'bg-green-50/50 border-green-200 dark:bg-green-950/20 dark:border-green-900' : ''"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div
                  class="h-6 w-6 rounded-full flex items-center justify-center text-xs font-semibold"
                  :class="getEntry(quarter)
                    ? 'bg-green-500/10 text-green-600 dark:text-green-400'
                    : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'"
                >
                  {{ quarter }}
                </div>
                <span class="text-sm font-medium">{{ $t("ketvirtis") }}</span>
              </div>
              <Badge
                v-if="getEntry(quarter)"
                variant="success"
                class="text-xs gap-1"
              >
                <CheckIcon class="h-3 w-3" />
                {{ $t("Pateikta") }}
              </Badge>
            </div>

            <div v-if="getEntry(quarter)" class="text-sm whitespace-pre-wrap text-muted-foreground leading-relaxed">
              {{ getEntry(quarter)?.status_text }}
            </div>
            <div v-else-if="canUpdate" class="flex flex-col gap-2">
              <Textarea
                v-model="quarterForms[quarter].status_text"
                :placeholder="`${quarter} ${$t('ketvirčio apžvalga...')}`"
                rows="3"
                class="resize-y"
              />
              <Button
                size="sm"
                class="gap-1.5 self-start"
                :disabled="quarterForms[quarter].processing || !quarterForms[quarter].status_text"
                @click="submitQuarter(quarter)"
              >
                <SendIcon class="h-3.5 w-3.5" />
                {{ $t("Pateikti") }}
              </Button>
            </div>
            <div v-else class="text-sm text-muted-foreground italic">
              {{ $t("Nėra įrašo") }}
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Year-end reflection -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="shrink-0 h-9 w-9 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center">
            <BookOpenIcon class="h-4.5 w-4.5 text-amber-600 dark:text-amber-400" />
          </div>
          <div class="flex-1 min-w-0">
            <CardTitle class="text-base">{{ $t("Metų refleksija") }}</CardTitle>
          </div>
          <Badge
            v-if="planningProcess.reflection_submitted_at"
            variant="success"
            class="shrink-0 gap-1"
          >
            <CheckIcon class="h-3 w-3" />
            {{ $t("Pateikta") }}
          </Badge>
        </div>
      </CardHeader>
      <CardContent>
        <div v-if="planningProcess.reflection_submitted_at">
          <div class="rounded-lg border bg-muted/30 p-4 text-sm whitespace-pre-wrap leading-relaxed">
            {{ planningProcess.reflection_text }}
          </div>
          <p class="mt-2 flex items-center gap-1.5 text-xs text-muted-foreground">
            <CalendarIcon class="h-3.5 w-3.5" />
            {{ $t("Pateikta:") }}
            {{ new Date(planningProcess.reflection_submitted_at).toLocaleDateString("lt-LT") }}
          </p>
        </div>
        <div v-else-if="canUpdate" class="flex flex-col gap-3">
          <Textarea
            v-model="reflectionForm.reflection_text"
            :placeholder="$t('Metų refleksija ir savęs įsivertinimas...')"
            rows="5"
            class="resize-y"
          />
          <Button
            size="sm"
            class="gap-1.5 self-start"
            :disabled="reflectionForm.processing || !reflectionForm.reflection_text"
            @click="submitReflection"
          >
            <SendIcon class="h-3.5 w-3.5" />
            {{ $t("Pateikti refleksiją") }}
          </Button>
        </div>
        <div v-else class="rounded-lg border border-dashed p-6 text-center">
          <BookOpenIcon class="h-8 w-8 mx-auto text-muted-foreground/40 mb-2" />
          <p class="text-sm text-muted-foreground">{{ $t("Refleksija dar nepateikta") }}</p>
        </div>
      </CardContent>
    </Card>

    <StageCommentThread
      :planning-process="planningProcess"
      :stage="5"
      :comments="comments"
    />
  </div>
</template>

<script setup lang="ts">
import { reactive, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import {
  BarChart3 as BarChart3Icon,
  BookOpen as BookOpenIcon,
  Calendar as CalendarIcon,
  Check as CheckIcon,
  Send as SendIcon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Textarea } from "@/Components/ui/textarea";
import DeadlineBanner from "@/Components/PlanningProcess/DeadlineBanner.vue";
import StageCommentThread from "@/Components/PlanningProcess/StageCommentThread.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadline?: App.Entities.PlanningStageDeadline | null;
  canUpdate: boolean;
  comments: App.Entities.Comment[];
}>();

const getEntry = (quarter: number) =>
  props.planningProcess.monitoring_entries?.find(
    (e: App.Entities.PlanningMonitoringEntry) => e.quarter === quarter
  ) ?? null;

const submittedQuarters = computed(
  () => [1, 2, 3, 4].filter((q) => getEntry(q)).length
);

const quarterForms = reactive({
  1: useForm({ planning_process_id: props.planningProcess.id, quarter: 1, status_text: "" }),
  2: useForm({ planning_process_id: props.planningProcess.id, quarter: 2, status_text: "" }),
  3: useForm({ planning_process_id: props.planningProcess.id, quarter: 3, status_text: "" }),
  4: useForm({ planning_process_id: props.planningProcess.id, quarter: 4, status_text: "" }),
});

const reflectionForm = useForm({
  reflection_text: props.planningProcess.reflection_text ?? "",
  reflection_submitted_at: null as string | null,
});

const submitQuarter = (quarter: 1 | 2 | 3 | 4) => {
  quarterForms[quarter].post(route("planningMonitoringEntries.store"), {
    preserveScroll: true,
  });
};

const submitReflection = () => {
  reflectionForm.reflection_submitted_at = new Date().toISOString();
  reflectionForm.patch(route("planningProcesses.update", props.planningProcess.id), {
    preserveScroll: true,
  });
};
</script>
