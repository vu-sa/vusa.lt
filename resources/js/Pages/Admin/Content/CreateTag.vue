<template>
  <!-- @deprecated Superseded by TagSheetForm in Phase 5.7; remove with legacy tag URLs in Phase 8. -->
  <PageContent title="Nauja žyma" :heading-icon="TagIcon">
    <UpsertModelLayout>
      <TagForm remember-key="CreateTag" :post-tag="tag" @submit:form="(form: any) => form.post(route('tags.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import TagForm from '@/Components/AdminForms/TagForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { tagTemplate } from '@/Types/formTemplates';
import { TagIcon } from '@/Components/icons';

const tag = tagTemplate as unknown as App.Entities.Tag;
</script>
