<template>
  <div class="flex flex-col gap-4">
    <DeadlineBanner :deadline="deadline ?? null" :is-stage-complete="!!planningProcess.goal_approved_at" />
    <!-- Meetings card -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="shrink-0 h-9 w-9 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
            <UsersIcon class="h-4.5 w-4.5 text-primary" />
          </div>
          <div class="flex-1 min-w-0">
            <CardTitle class="text-base">{{ $t("II etapas: Susitikimai ir tikslas") }}</CardTitle>
            <CardDescription>
              {{ $t("Susitikimų išvados/apibendrinimas, problemos pasirinkimas ir metinio tikslo formulavimas") }}
            </CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent class="flex flex-col gap-6">
        <!-- Meetings grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <!-- Meeting 1 -->
          <div class="rounded-lg border p-4 flex flex-col gap-3">
            <div class="flex items-center gap-2">
              <div class="h-6 w-6 rounded-full bg-primary/10 flex items-center justify-center text-xs font-semibold text-primary">
                1
              </div>
              <h4 class="font-medium text-sm">{{ $t("1-asis susitikimas") }}</h4>
              <Badge v-if="planningProcess.meeting_1_date" variant="outline" class="ml-auto text-xs gap-1">
                <CalendarIcon class="h-3 w-3" />
                {{ formatDate(planningProcess.meeting_1_date) }}
              </Badge>
            </div>
            <div v-if="canUpdate" class="flex flex-col gap-2">
              <Input
                v-model="meetingForm.meeting_1_date"
                type="date"
                class="w-auto"
              />
              <Textarea
                v-model="meetingForm.meeting_1_notes"
                :placeholder="$t('Kokias problemas ir kontekstą padalinys pristatė moderatoriui? Kokie pagrindiniai klausimai iškilo?')"
                rows="3"
                class="resize-y"
              />
            </div>
            <div v-else>
              <p class="text-sm whitespace-pre-wrap text-muted-foreground">
                {{ planningProcess.meeting_1_notes ?? $t("Nėra išvadų/apibendrinimo") }}
              </p>
            </div>
          </div>

          <!-- Meeting 2 -->
          <div class="rounded-lg border p-4 flex flex-col gap-3">
            <div class="flex items-center gap-2">
              <div class="h-6 w-6 rounded-full bg-primary/10 flex items-center justify-center text-xs font-semibold text-primary">
                2
              </div>
              <h4 class="font-medium text-sm">{{ $t("2-asis susitikimas") }}</h4>
              <Badge v-if="planningProcess.meeting_2_date" variant="outline" class="ml-auto text-xs gap-1">
                <CalendarIcon class="h-3 w-3" />
                {{ formatDate(planningProcess.meeting_2_date) }}
              </Badge>
            </div>
            <div v-if="canUpdate" class="flex flex-col gap-2">
              <Input
                v-model="meetingForm.meeting_2_date"
                type="date"
                class="w-auto"
              />
              <Textarea
                v-model="meetingForm.meeting_2_notes"
                :placeholder="$t('Kokia problema buvo pasirinkta? Kaip buvo suformuluotas tikslas? Kokie veiklų prioritetai numatyti?')"
                rows="3"
                class="resize-y"
              />
            </div>
            <div v-else>
              <p class="text-sm whitespace-pre-wrap text-muted-foreground">
                {{ planningProcess.meeting_2_notes ?? $t("Nėra išvadų/apibendrinimo") }}
              </p>
            </div>
          </div>

          <!-- Additional meetings -->
          <template v-for="(meeting, index) in additionalMeetings" :key="index">
            <div class="rounded-lg border border-dashed p-4 flex flex-col gap-3">
              <div class="flex items-center gap-2">
                <div class="h-6 w-6 rounded-full bg-muted flex items-center justify-center text-xs font-semibold text-muted-foreground">
                  {{ index + 3 }}
                </div>
                <h4 class="font-medium text-sm">{{ $t("Papildomas susitikimas") }} #{{ index + 1 }}</h4>
                <Badge v-if="meeting.date && !canUpdate" variant="outline" class="ml-auto text-xs gap-1">
                  <CalendarIcon class="h-3 w-3" />
                  {{ formatDate(meeting.date) }}
                </Badge>
                <Button
                  v-if="canUpdate"
                  variant="ghost"
                  size="icon"
                  class="ml-auto h-6 w-6 text-muted-foreground hover:text-destructive"
                  @click="removeAdditionalMeeting(index)"
                >
                  <XIcon class="h-3.5 w-3.5" />
                </Button>
              </div>
              <div v-if="canUpdate" class="flex flex-col gap-2">
                <Input
                  v-model="additionalMeetings[index].date"
                  type="date"
                  class="w-auto"
                />
                <Textarea
                  v-model="additionalMeetings[index].notes"
                  :placeholder="$t('Papildomo susitikimo išvados/apibendrinimas...')"
                  rows="3"
                  class="resize-y"
                />
              </div>
              <div v-else>
                <p class="text-sm whitespace-pre-wrap text-muted-foreground">
                  {{ meeting.notes ?? $t("Nėra išvadų/apibendrinimo") }}
                </p>
              </div>
            </div>
          </template>
        </div>

        <!-- Add additional meeting button -->
        <div v-if="canUpdate">
          <Button variant="outline" size="sm" class="gap-1.5" @click="addAdditionalMeeting">
            <PlusIcon class="h-3.5 w-3.5" />
            {{ $t("Pridėti papildomą susitikimą") }}
          </Button>
        </div>

        <!-- Problem selection -->
        <div class="rounded-lg border p-4 flex flex-col gap-3">
          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
              <TargetIcon class="h-4 w-4 text-primary" />
              <h4 class="font-medium text-sm">{{ $t("Pasirinkta problema") }}</h4>
            </div>
            <p class="text-xs text-muted-foreground">
              {{ $t("Problemas galima pridėti ir redaguoti") }}
              <a :href="route('problems.index')" class="underline font-medium text-primary" target="_blank">
                {{ $t("problemų puslapyje") }}
              </a>.
            </p>
          </div>
          <Select
            v-if="canUpdate"
            v-model="meetingForm.selected_problem_id"
          >
            <SelectTrigger>
              <SelectValue :placeholder="$t('Pasirinkite problemą')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="problem in tenantProblems"
                :key="problem.id"
                :value="problem.id"
              >
                {{ problem.title }}
              </SelectItem>
            </SelectContent>
          </Select>
          <div v-else class="text-sm">
            {{ planningProcess.selected_problem?.title ?? "—" }}
          </div>
        </div>

        <div v-if="canUpdate">
          <Button size="sm" :disabled="meetingForm.processing" @click="saveMeetings">
            {{ $t("Išsaugoti susitikimų duomenis") }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Goal card (separate for visual emphasis) -->
    <Card>
      <CardHeader>
        <div class="flex items-center gap-3">
          <div class="shrink-0 h-9 w-9 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center">
            <GoalIcon class="h-4.5 w-4.5 text-amber-600 dark:text-amber-400" />
          </div>
          <div class="flex-1 min-w-0">
            <CardTitle class="text-base">{{ $t("Metinis tikslas") }}</CardTitle>
          </div>
          <Badge v-if="planningProcess.goal_approved_at" variant="success" class="shrink-0 gap-1">
            <CheckIcon class="h-3 w-3" />
            {{ $t("Patvirtinta") }}
          </Badge>
          <Badge v-else-if="isGoalRejected" variant="destructive" class="shrink-0 gap-1">
            <XIcon class="h-3 w-3" />
            {{ $t("Atmesta") }}
          </Badge>
          <Badge v-else-if="planningProcess.goal_text && !planningProcess.goal_approved_at" variant="warning" class="shrink-0 gap-1">
            <ClockIcon class="h-3 w-3" />
            {{ $t("Laukia patvirtinimo") }}
          </Badge>
        </div>
      </CardHeader>
      <CardContent class="flex flex-col gap-3">
        <!-- Rejection feedback banner -->
        <div v-if="isGoalRejected && latestGoalRejection" class="rounded-md border border-destructive/30 bg-destructive/5 p-3 flex flex-col gap-1">
          <div class="flex items-center gap-2 text-sm font-medium text-destructive">
            <AlertCircleIcon class="h-4 w-4" />
            {{ $t("Reikia pataisymų") }}
          </div>
          <p class="text-sm text-muted-foreground">{{ latestGoalRejection.notes }}</p>
          <span class="text-xs text-muted-foreground">
            — {{ latestGoalRejection.user?.name ?? $t("Nežinomas") }},
            {{ formatDate(latestGoalRejection.created_at) }}
          </span>
        </div>

        <div v-if="canUpdate" class="flex flex-col gap-3">
          <Textarea
            v-model="goalForm.goal_text"
            :placeholder="$t('Suformuluokite metinį tikslą...')"
            rows="3"
            class="resize-y"
          />
          <div class="flex flex-wrap gap-2">
            <Button
              size="sm"
              variant="outline"
              class="gap-1.5"
              :disabled="goalForm.processing"
              @click="saveGoal"
            >
              {{ $t("Išsaugoti tikslą") }}
            </Button>
            <template v-if="canApprove && !planningProcess.goal_approved_at && planningProcess.goal_text">
              <Textarea
                v-model="goalReviewNotes"
                :placeholder="$t('Pastabos (neprivaloma tvirtinant, privaloma atmetant)...')"
                rows="2"
                class="w-full text-sm"
              />
              <Button
                size="sm"
                variant="outline"
                class="gap-1.5"
                :disabled="rejectGoalForm.processing || !goalReviewNotes.trim()"
                @click="rejectGoal"
              >
                <XIcon class="h-3.5 w-3.5 text-destructive" />
                {{ $t("Atmesti tikslą") }}
              </Button>
              <Button
                size="sm"
                class="gap-1.5"
                :disabled="approveGoalForm.processing"
                @click="approveGoal"
              >
                <CheckIcon class="h-3.5 w-3.5" />
                {{ $t("Patvirtinti tikslą") }}
              </Button>
            </template>
          </div>
        </div>
        <div v-else>
          <div
            v-if="planningProcess.goal_text"
            class="rounded-lg border bg-muted/30 p-4 text-sm whitespace-pre-wrap leading-relaxed"
          >
            {{ planningProcess.goal_text }}
          </div>
          <p v-else class="text-sm text-muted-foreground">
            {{ $t("Tikslas dar nesuformuluotas") }}
          </p>
        </div>

        <!-- Goal approval history -->
        <Collapsible v-if="goalApprovals.length > 0" v-model:open="showGoalHistory">
          <CollapsibleTrigger class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors">
            <HistoryIcon class="h-3.5 w-3.5" />
            {{ $t("Tvirtinimo istorija") }} ({{ goalApprovals.length }})
            <ChevronDownIcon :class="['h-3.5 w-3.5 transition-transform', showGoalHistory && 'rotate-180']" />
          </CollapsibleTrigger>
          <CollapsibleContent class="mt-2">
            <ApprovalTimeline :approvals="goalApprovals" />
          </CollapsibleContent>
        </Collapsible>
      </CardContent>
    </Card>

    <StageCommentThread
      :planning-process="planningProcess"
      :stage="2"
      :comments="comments"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import {
  Users as UsersIcon,
  Calendar as CalendarIcon,
  Target as TargetIcon,
  Goal as GoalIcon,
  Check as CheckIcon,
  Clock as ClockIcon,
  Plus as PlusIcon,
  X as XIcon,
  AlertCircle as AlertCircleIcon,
  History as HistoryIcon,
  ChevronDown as ChevronDownIcon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/collapsible";
import { Input } from "@/Components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { Textarea } from "@/Components/ui/textarea";
import ApprovalTimeline from "@/Features/Admin/Approvals/ApprovalTimeline.vue";
import DeadlineBanner from "@/Components/PlanningProcess/DeadlineBanner.vue";
import StageCommentThread from "@/Components/PlanningProcess/StageCommentThread.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadline?: App.Entities.PlanningStageDeadline | null;
  tenantProblems: App.Entities.Problem[];
  canUpdate: boolean;
  canApprove: boolean;
  comments: App.Entities.Comment[];
  goalApprovals: App.Entities.ApprovalRecord[];
}>();

const formatDate = (value: string | null | undefined): string => {
  if (!value) return "—";
  return new Date(value).toLocaleDateString("lt-LT");
};

const toDateInput = (value: string | null | undefined): string => {
  if (!value) return "";
  return value.substring(0, 10);
};

const additionalMeetings = ref<Array<{ date: string; notes: string }>>(
  (props.planningProcess.additional_meetings ?? []).map((m) => ({
    date: toDateInput(m.date),
    notes: m.notes ?? "",
  }))
);

const addAdditionalMeeting = () => {
  additionalMeetings.value.push({ date: "", notes: "" });
};

const removeAdditionalMeeting = (index: number) => {
  additionalMeetings.value.splice(index, 1);
};

const meetingForm = useForm({
  meeting_1_notes: props.planningProcess.meeting_1_notes ?? "",
  meeting_1_date: toDateInput(props.planningProcess.meeting_1_date),
  meeting_2_notes: props.planningProcess.meeting_2_notes ?? "",
  meeting_2_date: toDateInput(props.planningProcess.meeting_2_date),
  additional_meetings: null as Array<{ date: string; notes: string }> | null,
  selected_problem_id: props.planningProcess.selected_problem_id ?? null,
});

const goalForm = useForm({
  goal_text: props.planningProcess.goal_text ?? "",
});

const saveMeetings = () => {
  meetingForm.additional_meetings = additionalMeetings.value.length > 0
    ? additionalMeetings.value
    : null;
  meetingForm.patch(route("planningProcesses.update", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const saveGoal = () => {
  goalForm.patch(route("planningProcesses.updateGoal", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const approveGoalForm = useForm({
  goal_text: props.planningProcess.goal_text ?? "",
  goal_approved_at: null as string | null,
});

const approveGoal = () => {
  approveGoalForm.goal_text = goalForm.goal_text;
  approveGoalForm.goal_approved_at = new Date().toISOString();
  approveGoalForm.patch(route("planningProcesses.updateGoal", props.planningProcess.id), {
    preserveScroll: true,
    onSuccess: () => { goalReviewNotes.value = ""; },
  });
};

const goalReviewNotes = ref("");
const showGoalHistory = ref(false);

const rejectGoalForm = useForm({
  notes: "",
});

const rejectGoal = () => {
  rejectGoalForm.notes = goalReviewNotes.value;
  rejectGoalForm.patch(route("planningProcesses.rejectGoal", props.planningProcess.id), {
    preserveScroll: true,
    onSuccess: () => { goalReviewNotes.value = ""; },
  });
};

const isGoalRejected = computed(() => {
  if (props.planningProcess.goal_approved_at) return false;
  return props.goalApprovals.length > 0 && props.goalApprovals[0]?.decision === "rejected";
});

const latestGoalRejection = computed(() => {
  return props.goalApprovals.find((a) => a.decision === "rejected") ?? null;
});
</script>
