<template>
  <div>
    <UserForm :user :can-update-identity @submit:form="onSubmit" />
    <AccessChangeWarningDialog
      :open
      :report
      @update:open="open = $event"
      @confirm="confirm"
      @cancel="cancel"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, type InertiaForm } from '@inertiajs/vue3';

import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import UserForm from '@/Components/AdminForms/UserForm.vue';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';

const props = defineProps<{
  user: App.Entities.User;
  canUpdateIdentity: boolean;
}>();

const { report, open, guardedSubmit, confirm, cancel } = useAccessChangeGuard();

const onSubmit = (form: unknown) =>
  guardedSubmit(acknowledge =>
    (form as InertiaForm<Record<string, unknown>>)
      .transform((data: Record<string, unknown>) => ({ ...data, acknowledge_access_change: acknowledge }))
      .patch(route('users.update', props.user.id), { preserveScroll: true, preserveState: true }),
  );

const userName = computed(() => {
  if (props.user.show_pronouns) {
    return `${props.user.name} (${props.user.pronouns[usePage().props.app.locale]})`;
  }

  return props.user.name;
});

</script>
