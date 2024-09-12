<template>
  <PageContent :title="navigationElement.name" :back-url="route('navigation.index')" :heading-icon="Icons.NAVIGATION">
    <UpsertModelLayout>
      <Suspense v-if="navigationElement.parent_id !== 0">
        <NavigationForm enable-delete :navigation="navigationElement" :parent-elements :type-options
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
import { router } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import NavigationForm from "@/Components/AdminForms/NavigationForm.vue";
import NavigationParentForm from "@/Components/AdminForms/NavigationParentForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  navigationElement: App.Entities.Navigation;
  parentElements?: App.Entities.Navigation[];
  typeOptions?: any;
}>();

const navigationElement = {
  ...props.navigationElement,
  extra_attributes: props.navigationElement.extra_attributes || {},
};
</script>
