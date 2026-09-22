<template>
  <!-- @deprecated Superseded by EventTypeSheetForm in Phase 9.6; remove with legacy eventType URLs in Phase 10. -->
  <PageContent :title="pageTitle" :back-url="route('eventTypes.index')" :heading-icon="CalendarIcon">
    <UpsertModelLayout>
      <EventTypeForm :event-type="eventType" @submit:form="(form: unknown) => (form as { patch: (url: string) => void }).patch(route('eventTypes.update', eventType.id))"
        @delete="() => router.delete(route('eventTypes.destroy', eventType.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import EventTypeForm from '@/Components/AdminForms/EventTypeForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { CalendarIcon } from '@/Components/icons';

const props = defineProps<{
  eventType: App.Entities.EventType;
}>();

const pageTitle = computed(() => {
  const { name } = props.eventType;

  if (typeof name === 'object' && name !== null) {
    const localized = name as Record<string, string>;
    return localized.lt || localized.en || $t('Redaguoti renginio tipą');
  }

  return String(name ?? $t('Redaguoti renginio tipą'));
});
</script>
