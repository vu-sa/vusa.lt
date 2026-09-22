<template>
  <CalendarForm
    :enable-delete="canUpdate"
    :read-only="!canUpdate"
    :calendar
    :event-types
    :available-tags
    :assignable-tenants
    :meeting
    :submit-url="route('calendar.update', calendar.id)"
    submit-method="patch"
    @submit:form="canUpdate && handleUpdateCalendar($event)"
    @delete="canUpdate && router.delete(route('calendar.destroy', calendar.id))"
  />
</template>

<script setup lang="ts">
import { router, usePage, type InertiaForm } from '@inertiajs/vue3';

import CalendarForm from '@/Components/AdminForms/CalendarForm.vue';
import { CalendarIcon } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

const { calendar, canUpdate } = withDefaults(defineProps<{
  calendar: App.Entities.Calendar;
  canUpdate: boolean;
  eventTypes: App.Entities.EventType[];
  availableTags?: App.Entities.Tag[];
  assignableTenants: App.Entities.Tenant[];
  /** Set when this event announces a meeting. */
  meeting?: {
    id: string;
    start_time: string;
    title: string;
    trashed: boolean;
    agenda_items_count: number;
    institution_name: string | null;
  } | null;
}>(), {
  availableTags: () => [],
  meeting: null,
});

usePageBreadcrumbs(() => {
  const currentLocale = usePage().props.app.locale as 'lt' | 'en';
  const title = (calendar.title as Record<string, string>)?.[currentLocale]
    || (calendar.title as Record<string, string>)?.lt
    || 'Renginys';

  return BreadcrumbHelpers.adminForm('Kalendorius', 'calendar.index', title, CalendarIcon);
});

function handleUpdateCalendar(form: unknown) {
  const inertiaForm = form as InertiaForm<CalendarEventForm>;
  inertiaForm.transform(data => ({
    ...data,
    _method: 'patch',
  })).post(route('calendar.update', calendar.id), {
    preserveScroll: true,
    forceFormData: true,
  });
}
</script>
