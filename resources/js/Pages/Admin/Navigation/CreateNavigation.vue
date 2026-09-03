<template>
  <PageContent title="Naujas navigacijos elementas" :back-url="route('navigation.index')"
    :heading-icon="NavigationIcon">
    <UpsertModelLayout>
      <Suspense v-if="navigationElement.parent_id !== 0 || location === 'footer'">
        <NavigationForm remember-key="CreateNavigation" :navigation="navigationElement" :parent-elements :category-options
          @submit:form="(form) => form.post(route('navigation.store'))" />
      </Suspense>
      <NavigationParentForm v-else remember-key="CreateNavigationParent" :navigation="navigationElement"
        @submit:form="(form) => form.post(route('navigation.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import NavigationForm from '@/Components/AdminForms/NavigationForm.vue';
import NavigationParentForm from '@/Components/AdminForms/NavigationParentForm.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { NavigationIcon } from '@/Components/icons';

interface CategoryOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = defineProps<{
  parent_id: number | string;
  lang?: 'lt' | 'en';
  location?: 'header' | 'footer';
  parentElements?: App.Entities.Navigation[];
  categoryOptions?: CategoryOption[];
}>();

const navigationElement = {
  id: null,
  parent_id: parseInt(props.parent_id),
  name: '',
  lang: props.lang ?? 'lt',
  url: '#',
  is_active: true,
  extra_attributes: props.location === 'footer' ? { location: 'footer' } : {},
};
</script>
