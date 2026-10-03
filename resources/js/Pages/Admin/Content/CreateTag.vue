<template>
  <TagForm
    remember-key="CreateTag"
    :post-tag="tag"
    @submit:form="submitForm"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import TagForm from '@/Components/AdminForms/TagForm.vue';
import { tagTemplate } from '@/Types/formTemplates';

const tag = tagTemplate as unknown as App.Entities.Tag;

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('tags.store'));
}
</script>
