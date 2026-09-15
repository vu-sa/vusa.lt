<template>
  <PageContent title="Naujas renginio tipas" :heading-icon="CalendarIcon">
    <UpsertModelLayout>
      <EventTypeForm remember-key="CreateEventType" :event-type="eventType" @submit:form="(form: any) => form.post(route('eventTypes.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import EventTypeForm from '@/Components/AdminForms/EventTypeForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { CalendarIcon } from '@/Components/icons';

const eventType = {
  name: { lt: '', en: '' },
  slug: '',
  description: { lt: '', en: '' },
  is_active: true,
  sort_order: 0,
} as unknown as App.Entities.EventType;
</script>
