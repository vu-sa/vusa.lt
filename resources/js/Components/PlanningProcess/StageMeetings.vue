<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-base">{{ $t("II etapas: Susitikimai ir tikslas") }}</CardTitle>
      <CardDescription>
        {{ $t("Susitikimų išvados, problemos pasirinkimas ir metinio tikslo formulavimas") }}
      </CardDescription>
    </CardHeader>
    <CardContent class="flex flex-col gap-6">
      <!-- Meeting 1 -->
      <div class="flex flex-col gap-2">
        <h4 class="font-medium text-sm">{{ $t("1-asis susitikimas") }}</h4>
        <div class="grid sm:grid-cols-2 gap-2">
          <div class="flex flex-col gap-1">
            <Label>{{ $t("Data") }}</Label>
            <Input
              v-if="canUpdate"
              v-model="meetingForm.meeting_1_date"
              type="date"
            />
            <div v-else class="text-sm">
              {{ planningProcess.meeting_1_date
                ? new Date(planningProcess.meeting_1_date).toLocaleDateString("lt-LT")
                : "—" }}
            </div>
          </div>
        </div>
        <Textarea
          v-if="canUpdate"
          v-model="meetingForm.meeting_1_notes"
          :placeholder="$t('1-ojo susitikimo išvados...')"
          rows="3"
        />
        <div v-else class="text-sm whitespace-pre-wrap text-muted-foreground">
          {{ planningProcess.meeting_1_notes ?? $t("Nėra išvadų") }}
        </div>
      </div>

      <!-- Meeting 2 -->
      <div class="flex flex-col gap-2">
        <h4 class="font-medium text-sm">{{ $t("2-asis susitikimas") }}</h4>
        <div class="grid sm:grid-cols-2 gap-2">
          <div class="flex flex-col gap-1">
            <Label>{{ $t("Data") }}</Label>
            <Input
              v-if="canUpdate"
              v-model="meetingForm.meeting_2_date"
              type="date"
            />
            <div v-else class="text-sm">
              {{ planningProcess.meeting_2_date
                ? new Date(planningProcess.meeting_2_date).toLocaleDateString("lt-LT")
                : "—" }}
            </div>
          </div>
        </div>
        <Textarea
          v-if="canUpdate"
          v-model="meetingForm.meeting_2_notes"
          :placeholder="$t('2-ojo susitikimo išvados...')"
          rows="3"
        />
        <div v-else class="text-sm whitespace-pre-wrap text-muted-foreground">
          {{ planningProcess.meeting_2_notes ?? $t("Nėra išvadų") }}
        </div>
      </div>

      <!-- Problem selection -->
      <div class="flex flex-col gap-2">
        <h4 class="font-medium text-sm">{{ $t("Pasirinkta problema") }}</h4>
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

      <div v-if="canUpdate" class="flex gap-2">
        <Button size="sm" :disabled="meetingForm.processing" @click="saveMeetings">
          {{ $t("Išsaugoti susitikimų duomenis") }}
        </Button>
      </div>

      <!-- Goal text -->
      <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
          <h4 class="font-medium text-sm">{{ $t("Metinis tikslas") }}</h4>
          <Badge v-if="planningProcess.goal_approved_at" variant="success" class="text-xs">
            {{ $t("Patvirtinta") }}
          </Badge>
        </div>
        <Textarea
          v-if="canUpdate"
          v-model="goalForm.goal_text"
          :placeholder="$t('Suformuluokite metinį tikslą...')"
          rows="3"
          @blur="saveGoal"
        />
        <div v-else class="text-sm whitespace-pre-wrap text-muted-foreground">
          {{ planningProcess.goal_text ?? $t("Tikslas dar nesuformuluotas") }}
        </div>
        <div v-if="canUpdate && !planningProcess.goal_approved_at" class="flex gap-2">
          <Button
            size="sm"
            :disabled="goalForm.processing"
            @click="approveGoal"
          >
            {{ $t("Patvirtinti tikslą") }}
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { Textarea } from "@/Components/ui/textarea";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  tenantProblems: App.Entities.Problem[];
  canUpdate: boolean;
}>();

const toDateInput = (value: string | null | undefined): string => {
  if (!value) return "";
  return value.substring(0, 10);
};

const meetingForm = useForm({
  meeting_1_notes: props.planningProcess.meeting_1_notes ?? "",
  meeting_1_date: toDateInput(props.planningProcess.meeting_1_date),
  meeting_2_notes: props.planningProcess.meeting_2_notes ?? "",
  meeting_2_date: toDateInput(props.planningProcess.meeting_2_date),
  selected_problem_id: props.planningProcess.selected_problem_id ?? null,
});

const goalForm = useForm({
  goal_text: props.planningProcess.goal_text ?? "",
});

const saveMeetings = () => {
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
  });
};
</script>
