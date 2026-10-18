<template>
  <div>
    <UserForm :user :can-update-identity :existing-duplicates="duplicates" @submit:form="onSubmit" @merge="openMerge" />
    <AccessChangeWarningDialog
      :open
      :report
      @update:open="open = $event"
      @confirm="confirm"
      @cancel="cancel"
    />
    <MergeRecordsDialog
      :open="mergeMatch !== null"
      type="users"
      :records="mergeRecords"
      :keepable-ids="mergeMatch?.keepable_ids"
      :submit-url="route('users.mergeUsers')"
      target-field="kept_user_id"
      source-field="source_user_ids"
      :extra-payload="{ login_email: loginEmail, redirect_to_kept: true, dutiable_email_ids: dutiableEmailIds }"
      :submit-disabled="mergeDetailsLoading"
      @close="mergeMatch = null"
      @merged="mergeMatch = null"
    >
      <template #details="{ targetId }">
        <UserMergeDetails
          v-model="loginEmail"
          v-model:dutiable-email-ids="dutiableEmailIds"
          :records="mergeRecords"
          :target-id
          @loading="mergeDetailsLoading = $event"
        />
      </template>
    </MergeRecordsDialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage, type InertiaForm } from '@inertiajs/vue3';

import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import type { DuplicateUserMatch } from '@/Components/AdminForms/DuplicateUserWarning.vue';
import UserForm from '@/Components/AdminForms/UserForm.vue';
import MergeRecordsDialog from '@/Components/Merge/MergeRecordsDialog.vue';
import UserMergeDetails from '@/Components/Merge/UserMergeDetails.vue';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';

const props = withDefaults(defineProps<{
  user: App.Entities.User;
  canUpdateIdentity: boolean;
  /** Deferred: other records of the same person. */
  duplicates?: DuplicateUserMatch[];
}>(), {
  duplicates: () => [],
});

const { report, open, guardedSubmit, confirm, cancel } = useAccessChangeGuard();

const onSubmit = (form: unknown) =>
  guardedSubmit(acknowledge =>
    (form as InertiaForm<Record<string, unknown>>)
      .transform((data: Record<string, unknown>) => ({ ...data, acknowledge_access_change: acknowledge }))
      .patch(route('users.update', props.user.id), { preserveScroll: true, preserveState: true, except: ['duplicates'] }),
  );

const mergeMatch = ref<DuplicateUserMatch | null>(null);
const loginEmail = ref<string | null>(null);
const dutiableEmailIds = ref<string[]>([]);
const mergeDetailsLoading = ref(false);

const mergeRecords = computed(() => mergeMatch.value
  ? [
      { id: props.user.id, label: props.user.name, context: props.user.email, email: props.user.email, can_update_identity: props.canUpdateIdentity },
      {
        id: mergeMatch.value.id, label: mergeMatch.value.name, context: mergeMatch.value.email,
        email: mergeMatch.value.email, can_update_identity: mergeMatch.value.can_update_identity === true,
      },
    ]
  : []);

const openMerge = (match: DuplicateUserMatch) => {
  mergeMatch.value = match;
};

const userName = computed(() => {
  if (props.user.show_pronouns) {
    return `${props.user.name} (${props.user.pronouns[usePage().props.app.locale]})`;
  }

  return props.user.name;
});

</script>
