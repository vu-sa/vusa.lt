<template>
  <PageContent title="Naujas navigacijos elementas" :back-url="route('navigation.index')" :heading-icon="Icons.NAVIGATION">
    <UpsertModelLayout :errors="$page.props.errors" :model="navigationElement">
      <Suspense v-if="navigationElement.parent_id !== 0">
        <NavigationForm model-route="navigation.store" :navigation="navigationElement" :parent-elements
          :type-options />
      </Suspense>
      <NavigationParentForm v-else model-route="navigation.store" :navigation="navigationElement" />
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
  parent_id: number;
  parentElements?: App.Entities.Navigation[];
  typeOptions?: any;
}>();

const navigationElement = {
  id: null,
  parent_id: parseInt(props.parent_id), 
  name: "",
  lang: "lt",
  url: "#",
  is_active: true,
  extra_attributes: {},
};
</script>
