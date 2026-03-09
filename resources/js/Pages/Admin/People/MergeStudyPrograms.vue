<template>
  <PageContent title="Studijų programų suliejimas" :heading-icon="Icons.STUDY_PROGRAM">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            Sulieti studijų programas
          </template>
          <template #description>
            <div class="typography">
              <p>
                Pasirinkus studijų programas, <strong>visos</strong> pareigybės ir priskyrimai bus perduoti į tikslinę studijų programą.
              </p>
              <p>
                Šis veiksmas yra <strong>neatstatomas</strong>! Sujungtos studijų programos bus ištrinti.
              </p>
            </div>
          </template>
          <FormFieldWrapper id="target_study_program_id" label="Tikslinė studijų programa" required>
            <Select v-model="targetIdString">
              <SelectTrigger>
                <SelectValue placeholder="Pasirinkite studijų programą" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="program in studyProgramOptions" :key="program.value" :value="String(program.value)">
                  <span class="flex items-center gap-2">
                    {{ program.label }}
                    <Badge v-if="program.degree" size="tiny" variant="secondary">{{ program.degree }}</Badge>
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
          <FormFieldWrapper id="source_study_program_ids" label="Sujungiamos studijų programos" required>
            <MultiSelect
              v-model="selectedSourcePrograms"
              :options="studyProgramOptions"
              label-field="label"
              value-field="value"
              placeholder="Pasirinkite studijų programas"
            >
              <template #option="{ item }">
                <span class="flex items-center gap-2">
                  {{ item.label }}
                  <Badge v-if="item.degree" size="tiny" variant="secondary">{{ item.degree }}</Badge>
                </span>
              </template>
            </MultiSelect>
          </FormFieldWrapper>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

import { Badge } from "@/Components/ui/badge";
import { MultiSelect } from "@/Components/ui/multi-select";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import FormFieldWrapper from "@/Components/AdminForms/FormFieldWrapper.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const { studyPrograms } = defineProps<{
  studyPrograms: App.Entities.StudyProgram[];
}>();

const form = useForm({
  target_study_program_id: null as number | null,
  source_study_program_ids: [] as number[],
});

const studyProgramOptions = computed(() =>
  studyPrograms.map(program => ({
    label: Array.isArray(program.name) ? program.name.join('') : program.name,
    value: program.id,
    degree: program.degree,
  }))
);

// Bridge string <-> number for Select
const targetIdString = computed({
  get: () => form.target_study_program_id != null ? String(form.target_study_program_id) : '',
  set: (val: string) => { form.target_study_program_id = val ? Number(val) : null; },
});

// Bridge object array <-> id array for MultiSelect
const selectedSourcePrograms = computed({
  get: () => studyProgramOptions.value.filter(opt => form.source_study_program_ids.includes(opt.value)),
  set: (items) => { form.source_study_program_ids = items.map(item => item.value); },
});

function handleFormSubmit() {
  form.post(route("studyPrograms.mergeStudyPrograms"), {
    onSuccess: () => {
      form.reset();
    },
  });
}
</script>
