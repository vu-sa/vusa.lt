<template>
  <PageContent title="Nauja žyma" :heading-icon="Icons.TAG">
    <UpsertModelLayout>
      <TagForm remember-key="CreateTag" :tag @submit:form="(form: any) => form.post(route('tags.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TagForm from "@/Components/AdminForms/TagForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import { tagTemplate } from "@/Types/formTemplates";

const tag = tagTemplate as unknown as App.Entities.Tag;
</script>
