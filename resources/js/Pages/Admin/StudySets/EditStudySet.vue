<template>
  <PageContent :title="title" :heading-icon="Icons.STUDY_SET">
    <UpsertModelLayout>
      <StudySetForm :study-set="studySet" :tenants
        @submit:form="(form: any) => form.patch(route('studySets.update', studySet.id))"
        @delete="() => router.delete(route('studySets.destroy', studySet.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import StudySetForm from "@/Components/AdminForms/StudySetForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import { useTranslatedTitle } from "@/Composables/useTranslatedTitle";

const props = defineProps<{
  studySet: any;
  assignableTenants: Array<{ id: number; shortname: string }>;
}>();

const title = useTranslatedTitle('Redaguoti komplektą', props.studySet.name);

const tenants = computed(() => props.assignableTenants || []);
</script>
