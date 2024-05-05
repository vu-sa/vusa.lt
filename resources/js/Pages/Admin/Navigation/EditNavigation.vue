<template>
  <PageContent :title="navigationElement.name" :back-url="route('navigation.index')" :heading-icon="Icons.NAVIGATION">
    <UpsertModelLayout :errors="$page.props.errors" :model="navigationElement">
      <Suspense v-if="navigationElement.parent_id !== 0">
        <NavigationForm model-route="navigation.update" delete-model-route="navigation.destroy"
          :navigation="navigationElement" :parent-elements :type-options />
      </Suspense>
      <NavigationParentForm v-else model-route="navigation.update" delete-model-route="navigation.destroy"
        :navigation="navigationElement" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
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
