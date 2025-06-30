<template>
  <PageContent :title="title" :heading-icon="Icons.TAG">
    <UpsertModelLayout>
      <TagForm :tag @submit:form="(form: any) => form.patch(route('tags.update', tag.id))"
        @delete="() => router.delete(route('tags.destroy', tag.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TagForm from "@/Components/AdminForms/TagForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import { useTranslatedTitle } from "@/Composables/useTranslatedTitle";

const props = defineProps<{
  tag: App.Entities.Tag;
}>();

const title = useTranslatedTitle('Redaguoti žymą', props.tag.name);
</script>
