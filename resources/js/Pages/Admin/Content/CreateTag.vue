<template>
  <PageContent title="Nauja žyma" :heading-icon="Icons.TAG">
    <UpsertModelLayout>
      <TagForm remember-key="CreateTag" :tag @submit:form="(form: any) => form.post(route('tags.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TagForm from "@/Components/AdminForms/TagForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const tag = ref({
  id: "",
  name: { lt: "", en: "" },
  description: { lt: "", en: "" },
  alias: "",
  created_at: "",
  updated_at: "",
} as unknown as App.Entities.Tag);
</script>
