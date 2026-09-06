<template>
  <PageContent :title="navigationElement.name" :back-url="route('navigation.index')" :heading-icon="NavigationIcon">
    <template #aside-header>
      <ActivityLogSheet subject-type="navigation" :subject-id="navigationElement.id" />
    </template>
    <UpsertModelLayout>
      <Suspense v-if="navigationElement.parent_id !== 0 || navigationElement.extra_attributes?.location === 'footer'">
        <NavigationForm enable-delete :navigation="navigationElement" :parent-elements :category-options
          @submit:form="(form) => form.patch(route('navigation.update', navigationElement.id), { preserveScroll: true })"
          @delete="() => router.delete(route('navigation.destroy', navigationElement.id))" />
      </Suspense>
      <NavigationParentForm v-else
        :navigation="navigationElement"
        @submit:form="(form) => form.patch(route('navigation.update', navigationElement.id), { preserveScroll: true })"
        @delete="() => router.delete(route('navigation.destroy', navigationElement.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import NavigationForm from '@/Components/AdminForms/NavigationForm.vue';
import NavigationParentForm from '@/Components/AdminForms/NavigationParentForm.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { NavigationIcon } from '@/Components/icons';

interface CategoryOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = defineProps<{
  navigationElement: App.Entities.Navigation;
  parentElements?: App.Entities.Navigation[];
  categoryOptions?: CategoryOption[];
}>();

const navigationElement = {
  ...props.navigationElement,
  extra_attributes: props.navigationElement.extra_attributes || {},
};
</script>
