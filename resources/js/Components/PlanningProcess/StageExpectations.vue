<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-base">{{ $t("I etapas: Lūkesčiai") }}</CardTitle>
      <CardDescription>{{ $t("Padalinys įveda lūkesčius moderatoriui") }}</CardDescription>
    </CardHeader>
    <CardContent>
      <div v-if="!editing" class="flex flex-col gap-4">
        <div v-if="planningProcess.expectations_text" class="text-sm whitespace-pre-wrap">
          {{ planningProcess.expectations_text }}
        </div>
        <div v-else class="text-sm text-muted-foreground">
          {{ $t("Lūkesčiai dar neįvesti.") }}
        </div>
        <div
          v-if="planningProcess.expectations_submitted_at"
          class="text-xs text-muted-foreground"
        >
          {{ $t("Pateikta:") }}
          {{ new Date(planningProcess.expectations_submitted_at).toLocaleDateString("lt-LT") }}
        </div>
        <div v-if="canUpdate" class="flex gap-2">
          <Button variant="outline" size="sm" @click="startEditing">
            {{ $t("Redaguoti") }}
          </Button>
          <Button
            v-if="!planningProcess.expectations_submitted_at"
            size="sm"
            :disabled="submitForm.processing || !planningProcess.expectations_text"
            @click="submitExpectations"
          >
            {{ $t("Pateikti") }}
          </Button>
        </div>
      </div>

      <div v-else class="flex flex-col gap-4">
        <Textarea
          v-model="form.expectations_text"
          :placeholder="$t('Įveskite lūkesčius...')"
          rows="5"
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
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  canUpdate: boolean;
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
