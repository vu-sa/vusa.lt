<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import CommentComposer from '@/Components/Discussions/CommentComposer.vue';
import type { WritableHandoverDuty } from '@/Types/learning';

const props = defineProps<{ duty: WritableHandoverDuty }>();
const saving = ref(false);
const error = ref('');
const saved = ref(false);
const template = ['institution', 'unfinished', 'people', 'documents', 'advice']
  .map(key => `<p><strong>${$t(`learning_people.prompts.${key}`)}</strong></p><p></p>`).join('');
const content = props.duty.note?.body ?? template;

function save(body: string) {
  if (saving.value) return;
  saving.value = true;
  saved.value = false;
  error.value = '';
  const options = {
    preserveScroll: true,
    onError: (errors: Record<string, string>) => { error.value = errors.body || $t('learning_people.save_failed'); },
    onSuccess: () => { saved.value = true; },
    onFinish: () => { saving.value = false; },
  };
  if (props.duty.note) router.patch(route('handoverNotes.update', props.duty.note.id), { body }, options);
  else router.post(route('handoverNotes.store', props.duty.dutyId), { body }, options);
}
</script>

<template>
  <div class="space-y-3" :aria-busy="saving" data-slot="handover-editor">
    <p class="text-sm text-muted-foreground">{{ $t('learning_people.readers') }}</p>
    <CommentComposer :content="content" :submit-label="$t('learning_people.save')" :placeholder="$t('learning_people.editor_placeholder')" :submitting="saving" quiet-mentions @submit="save" />
    <p v-if="error" role="alert" class="text-sm text-destructive">{{ error }}</p>
    <p v-else-if="saved" role="status" class="text-sm text-muted-foreground">{{ $t('learning_people.saved') }}</p>
  </div>
</template>
