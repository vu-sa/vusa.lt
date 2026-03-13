<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-base">{{ $t("V etapas: Monitoringas ir refleksija") }}</CardTitle>
      <CardDescription>{{ $t("Ketvirtinė stebėsena ir metų pabaigos refleksija") }}</CardDescription>
    </CardHeader>
    <CardContent class="flex flex-col gap-6">
      <!-- Quarterly monitoring -->
      <div class="flex flex-col gap-4">
        <h4 class="font-medium text-sm">{{ $t("Ketvirtiniai atnaujinimai") }}</h4>
        <div v-for="quarter in [1, 2, 3, 4]" :key="quarter" class="flex flex-col gap-2">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium">{{ quarter }} {{ $t("ketvirtis") }}</span>
            <Badge
              v-if="getEntry(quarter)"
              variant="success"
              class="text-xs"
            >
              {{ $t("Pateikta") }}
            </Badge>
          </div>
          <div v-if="getEntry(quarter)" class="text-sm whitespace-pre-wrap text-muted-foreground">
            {{ getEntry(quarter)?.status_text }}
          </div>
          <div v-else-if="canUpdate" class="flex flex-col gap-2">
            <Textarea
              v-model="quarterForms[quarter].status_text"
              :placeholder="`${quarter} ${$t('ketvirčio apžvalga...')}`"
              rows="3"
            />
            <Button
              size="sm"
              :disabled="quarterForms[quarter].processing || !quarterForms[quarter].status_text"
              @click="submitQuarter(quarter)"
            >
              {{ $t("Pateikti") }}
            </Button>
          </div>
          <div v-else class="text-sm text-muted-foreground">
            {{ $t("Nėra įrašo") }}
          </div>
        </div>
      </div>

      <!-- Year-end reflection -->
      <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
          <h4 class="font-medium text-sm">{{ $t("Metų refleksija") }}</h4>
          <Badge
            v-if="planningProcess.reflection_submitted_at"
            variant="success"
            class="text-xs"
          >
            {{ $t("Pateikta") }}
          </Badge>
        </div>

        <div v-if="planningProcess.reflection_submitted_at" class="text-sm whitespace-pre-wrap">
          {{ planningProcess.reflection_text }}
        </div>
        <div v-else-if="canUpdate" class="flex flex-col gap-2">
          <Textarea
            v-model="reflectionForm.reflection_text"
            :placeholder="$t('Metų refleksija ir savęs įsivertinimas...')"
            rows="5"
          />
          <Button
            size="sm"
            :disabled="reflectionForm.processing || !reflectionForm.reflection_text"
            @click="submitReflection"
          >
            {{ $t("Pateikti refleksiją") }}
          </Button>
        </div>
        <div v-else class="text-sm text-muted-foreground">
          {{ $t("Refleksija dar nepateikta") }}
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { reactive } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Textarea } from "@/Components/ui/textarea";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  canUpdate: boolean;
}>();

const getEntry = (quarter: number) =>
  props.planningProcess.monitoring_entries?.find(
    (e: App.Entities.PlanningMonitoringEntry) => e.quarter === quarter
  ) ?? null;

// One form per quarter
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
