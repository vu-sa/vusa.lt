<template>
  <PageContent title="Nauja studijų programa" :heading-icon="Icons.STUDY_PROGRAM">
    <UpsertModelLayout>
      <StudyProgramForm remember-key="CreateStudyProgram" :study-program :tenants @submit:form="(form: any) => form.post(route('studyPrograms.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import StudyProgramForm from "@/Components/AdminForms/StudyProgramForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  tenants: Array<App.Entities.Tenant>;
  degreeOptions: Array<{ label: string; value: string }>;
}>();

const studyProgram = ref({
  id: "",
  name: [""],
  degree: "",
  tenant_id: null,
  created_at: "",
  updated_at: "",
  translations: {},
} as unknown as App.Entities.StudyProgram);
</script>
