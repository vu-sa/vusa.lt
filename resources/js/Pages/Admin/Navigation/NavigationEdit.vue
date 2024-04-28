<template>
  <PageContent :title="navigation.name">
    {{ navigationRoots }}
    <Suspense>
      <NavigationForm :navigation="navigationRoots" :type-options />  
    </Suspense>
  </PageContent>
</template>

<script setup lang="tsx">
import NavigationForm from "@/Components/AdminForms/NavigationForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import { ref } from "vue";

const props = defineProps<{
  navigation: App.Entities.Navigation;
  typeOptions?: any
}>();

// Get navigation with parent_id = 0
const navigationRoots = ref(props.navigation.reduce((acc, nav) => {
  if (nav.parent_id === 0) {
    acc.push(nav);
  }
  return acc;
}, []));

// Add childe elements to navigationRoots
props.navigation.forEach(nav => {
  if (nav.parent_id !== 0) {
    const parent = navigationRoots.value.find(n => n.id === nav.parent_id);
    if (parent) {
      if (!parent.children) {
        parent.children = [];
      }
      parent.children.push(nav);
    }
  }

  // Expand extra_attributes to separate fields
  if (nav.extra_attributes) {
    Object.entries(nav.extra_attributes).forEach(([key, value]) => {
      nav[key] = value;
    });
  }
});
</script>
