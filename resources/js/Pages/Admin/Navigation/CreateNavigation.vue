<template>
  <Suspense v-if="navigationElement.parent_id !== 0 || location === 'footer'">
    <NavigationForm
      remember-key="CreateNavigation"
      :navigation="navigationElement"
      :parent-elements
      :topic-options
      @submit:form="submitForm"
    />
  </Suspense>
  <NavigationParentForm
    v-else
    remember-key="CreateNavigationParent"
    :navigation="navigationElement"
    @submit:form="submitForm"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import NavigationForm from '@/Components/AdminForms/NavigationForm.vue';
import NavigationParentForm from '@/Components/AdminForms/NavigationParentForm.vue';

interface TopicOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = defineProps<{
  // eslint-disable-next-line vue/prop-name-casing
  parent_id: number | string;
  lang?: 'lt' | 'en';
  location?: 'header' | 'footer';
  parentElements?: App.Entities.Navigation[];
  topicOptions?: TopicOption[];
}>();

const navigationElement = {
  id: null,
  parent_id: parseInt(String(props.parent_id), 10),
  name: '',
  lang: props.lang ?? 'lt',
  url: '#',
  is_active: true,
  extra_attributes: props.location === 'footer' ? { location: 'footer' } : {},
} as unknown as App.Entities.Navigation;

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('navigation.store'));
}
</script>
