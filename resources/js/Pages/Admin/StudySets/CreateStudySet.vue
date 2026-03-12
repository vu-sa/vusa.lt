<template>
  <PageContent title="Naujas individualių studijų komplektas" :heading-icon="Icons.STUDY_SET">
    <UpsertModelLayout>
      <StudySetForm remember-key="CreateStudySet" :study-set="studySet" :tenants
        @submit:form="(form: any) => form.post(route('studySets.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import StudySetForm from "@/Components/AdminForms/StudySetForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  assignableTenants: Array<{ id: number; shortname: string }>;
}>();

const studySet = ref({
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  order: 0,
  is_visible: true,
  tenant_id: null,
  courses: [],
  reviews: [],
});

const tenants = computed(() => usePage().props.assignableTenants || []);
</script>
