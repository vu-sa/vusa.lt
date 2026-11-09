<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { handoverFields, type HandoverField, type WritableHandoverDuty } from '@/Types/learning';

const props = defineProps<{ duty: WritableHandoverDuty }>();
const saving = ref(false);
const errors = ref<Record<string, string>>({});
const saved = ref(false);
const fields = reactive<Record<HandoverField, string>>({
  situation: props.duty.note?.situation ?? '',
  open_issues: props.duty.note?.open_issues ?? '',
  people: props.duty.note?.people ?? '',
  resources: props.duty.note?.resources ?? '',
  advice: props.duty.note?.advice ?? '',
});

function save() {
  if (saving.value) return;
  saving.value = true;
  saved.value = false;
  errors.value = {};
  const options = {
    preserveScroll: true,
    onError: (validationErrors: Record<string, string>) => { errors.value = validationErrors; },
    onSuccess: () => { saved.value = true; },
    onFinish: () => { saving.value = false; },
  };
  if (props.duty.note) router.patch(route('handoverNotes.update', props.duty.note.id), { ...fields }, options);
  else router.post(route('handoverNotes.store', props.duty.dutyId), { ...fields }, options);
}
</script>

<template>
  <form class="max-w-2xl space-y-5" :aria-busy="saving" data-slot="handover-editor" @submit.prevent="save">
    <p class="text-sm text-muted-foreground">{{ $t('learning_people.readers') }}. {{ $t('learning_people.fill_one') }}</p>
    <FormFieldWrapper
      v-for="field in handoverFields" :id="`handover-${duty.dutyId}-${field}`" :key="field"
      :label="$t(`learning_people.fields.${field}.label`)" :hint="$t(`learning_people.fields.${field}.hint`)"
      :error="errors[field]"
    >
      <Textarea
        :id="`handover-${duty.dutyId}-${field}`" :model-value="fields[field]" @update:model-value="fields[field] = String($event)" :name="field" :rows="3" :maxlength="5000"
        :aria-invalid="Boolean(errors[field])" :aria-describedby="`handover-${duty.dutyId}-${field}-help`"
        class="min-h-24 resize-y"
      />
      <span :id="`handover-${duty.dutyId}-${field}-help`" class="sr-only">
        {{ $t(`learning_people.fields.${field}.hint`) }}<template v-if="errors[field]"> {{ errors[field] }}</template>
      </span>
    </FormFieldWrapper>
    <div class="flex min-h-11 flex-wrap items-center gap-3">
      <Button type="submit" :disabled="saving" voice="sentence" class="min-h-11">{{ $t('learning_people.save') }}</Button>
      <p v-if="Object.keys(errors).length" role="alert" class="text-sm text-destructive">{{ $t('learning_people.save_failed') }}</p>
      <p v-else-if="saved" role="status" class="text-sm text-muted-foreground">{{ $t('learning_people.saved') }}</p>
    </div>
  </form>
</template>
