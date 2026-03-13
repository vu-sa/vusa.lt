<template>
  <div class="flex flex-col gap-4">
  <DeadlineBanner :deadline="deadline ?? null" :is-stage-complete="!!planningProcess.expectations_submitted_at" />
  <Card>
    <CardHeader>
      <div class="flex items-center gap-3">
        <div class="shrink-0 h-9 w-9 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
          <SparklesIcon class="h-4.5 w-4.5 text-primary" />
        </div>
        <div class="flex-1 min-w-0">
          <CardTitle class="text-base">{{ $t("I etapas: Lūkesčiai") }}</CardTitle>
          <CardDescription>{{ $t("Padalinys įveda lūkesčius moderatoriui") }}</CardDescription>
        </div>
        <Badge v-if="planningProcess.expectations_submitted_at" variant="success" class="shrink-0 gap-1">
          <CheckIcon class="h-3 w-3" />
          {{ $t("Pateikta") }}
        </Badge>
      </div>
    </CardHeader>
    <CardContent>
      <div v-if="!editing" class="flex flex-col gap-4">
        <div
          v-if="planningProcess.expectations_text"
          class="rounded-lg border bg-muted/30 p-4 text-sm whitespace-pre-wrap leading-relaxed"
        >
          {{ planningProcess.expectations_text }}
        </div>
        <div v-else class="rounded-lg border border-dashed p-6 text-center">
          <SparklesIcon class="h-8 w-8 mx-auto text-muted-foreground/50 mb-2" />
          <p class="text-sm text-muted-foreground">{{ $t("Lūkesčiai dar neįvesti.") }}</p>
        </div>

        <div
          v-if="planningProcess.expectations_submitted_at"
          class="flex items-center gap-1.5 text-xs text-muted-foreground"
        >
          <CalendarIcon class="h-3.5 w-3.5" />
          {{ $t("Pateikta:") }}
          {{ new Date(planningProcess.expectations_submitted_at).toLocaleDateString("lt-LT") }}
        </div>

        <div v-if="canUpdate" class="flex gap-2">
          <Button variant="outline" size="sm" class="gap-1.5" @click="startEditing">
            <PencilIcon class="h-3.5 w-3.5" />
            {{ $t("Redaguoti") }}
          </Button>
          <Button
            v-if="!planningProcess.expectations_submitted_at"
            size="sm"
            class="gap-1.5"
            :disabled="submitForm.processing || !planningProcess.expectations_text"
            @click="submitExpectations"
          >
            <SendIcon class="h-3.5 w-3.5" />
            {{ $t("Pateikti") }}
          </Button>
        </div>
      </div>

      <div v-else class="flex flex-col gap-4">
        <Textarea
          v-model="form.expectations_text"
          :placeholder="$t('Įveskite lūkesčius...')"
          rows="5"
          class="resize-y"
        />
        <div class="flex gap-2">
          <Button size="sm" :disabled="form.processing" @click="save">
            {{ $t("Išsaugoti") }}
          </Button>
          <Button variant="outline" size="sm" @click="cancelEditing">
            {{ $t("Atšaukti") }}
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>

  <StageCommentThread
    :planning-process="planningProcess"
    :stage="1"
    :comments="comments"
  />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import {
  Sparkles as SparklesIcon,
  Check as CheckIcon,
  Calendar as CalendarIcon,
  Pencil as PencilIcon,
  Send as SendIcon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import DeadlineBanner from "@/Components/PlanningProcess/DeadlineBanner.vue";
import StageCommentThread from "@/Components/PlanningProcess/StageCommentThread.vue";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadline?: App.Entities.PlanningStageDeadline | null;
  canUpdate: boolean;
  comments: App.Entities.Comment[];
}>();

const editing = ref(false);

const form = useForm({
  expectations_text: props.planningProcess.expectations_text ?? "",
});

const submitForm = useForm({
  expectations_submitted_at: null as string | null,
});

const startEditing = () => {
  form.expectations_text = props.planningProcess.expectations_text ?? "";
  editing.value = true;
};

const cancelEditing = () => {
  editing.value = false;
  form.reset();
};

const save = () => {
  form.patch(route("planningProcesses.update", props.planningProcess.id), {
    preserveScroll: true,
    onSuccess: () => {
      editing.value = false;
    },
  });
};

const submitExpectations = () => {
  submitForm.expectations_submitted_at = new Date().toISOString();
  submitForm.patch(route("planningProcesses.update", props.planningProcess.id), {
    preserveScroll: true,
  });
};
</script>
