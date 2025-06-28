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
          <NFormItem required>
            <template #label>
              <span class="inline-flex items-center gap-1">
                <NIcon :component="Icons.STUDY_PROGRAM" />
                Tikslinė studijų programa
              </span>
            </template>
            <NSelect v-model:value="form.target_study_program_id" filterable :options="studyProgramOptions" 
              :render-label="renderStudyProgramLabel" placeholder="Pasirinkite studijų programą" />
          </NFormItem>
          <NFormItem required>
            <template #label>
              <span class="inline-flex items-center gap-1">
                <NIcon :component="Icons.STUDY_PROGRAM" />
                Sujungiamos studijų programos
              </span>
            </template>
            <NSelect v-model:value="form.source_study_program_ids" multiple filterable :options="studyProgramOptions" 
              :render-label="renderStudyProgramLabel" placeholder="Pasirinkite studijų programas" />
          </NFormItem>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { NTag, type SelectRenderLabel } from "naive-ui";
import { useForm } from "@inertiajs/vue3";
import { h, computed } from "vue";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";

const { studyPrograms } = defineProps<{
  studyPrograms: App.Entities.StudyProgram[];
}>();

const form = useForm({
  target_study_program_id: null,
  source_study_program_ids: [],
});

const studyProgramOptions = computed(() => 
  studyPrograms.map(program => ({
    label: Array.isArray(program.name) ? program.name.join('') : program.name,
    value: program.id,
    degree: program.degree,
  }))
);

const renderStudyProgramLabel: SelectRenderLabel = (option: any) => {
  return h("div", { class: "flex items-center gap-2" }, [
    h("span", option.label),
    option?.degree ? h(NTag, { size: "tiny" }, option.degree) : null,
  ]);
};

function handleFormSubmit() {
  form.post(route("studyPrograms.mergeStudyPrograms"), {
    onSuccess: () => {
      form.reset();
    },
  });
}
</script>
