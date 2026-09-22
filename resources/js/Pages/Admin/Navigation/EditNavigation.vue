<template>
  <Suspense v-if="navigationElement.parent_id !== 0 || navigationElement.extra_attributes?.location === 'footer'">
    <NavigationForm
      enable-delete
      :navigation="navigationElement"
      :parent-elements
      :topic-options
      @submit:form="submitForm"
      @delete="deleteElement"
    >
      <template #aside-header>
        <ActivityLogSheet subject-type="navigation" :subject-id="String(navigationElement.id)" />
      </template>
    </NavigationForm>
  </Suspense>
  <NavigationParentForm
    v-else
    enable-delete
    :navigation="navigationElement"
    @submit:form="submitForm"
    @delete="deleteElement"
  >
    <template #aside-header>
      <ActivityLogSheet subject-type="navigation" :subject-id="String(navigationElement.id)" />
    </template>
  </NavigationParentForm>
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import NavigationForm from '@/Components/AdminForms/NavigationForm.vue';
import NavigationParentForm from '@/Components/AdminForms/NavigationParentForm.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { NavigationIcon } from '@/Components/icons';

interface TopicOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = defineProps<{
  navigationElement: App.Entities.Navigation;
  parentElements?: App.Entities.Navigation[];
  topicOptions?: TopicOption[];
}>();

usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm('Navigacija', 'navigation.index', props.navigationElement.name || 'Redaguoti elementą', NavigationIcon),
);

const navigationElement = {
  ...props.navigationElement,
  extra_attributes: props.navigationElement.extra_attributes || {},
};

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).patch(route('navigation.update', navigationElement.id), {
    preserveScroll: true,
  });
}

function deleteElement(): void {
  router.delete(route('navigation.destroy', navigationElement.id));
}
</script>
