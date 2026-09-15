<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>

      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <FormFieldWrapper id="slug" :label="$t('forms.fields.slug')">
        <Input id="slug" v-model="form.slug" placeholder="Pvz: mokymai" />
      </FormFieldWrapper>

      <MultiLocaleTiptapFormItem v-model:input="form.description" :label="$t('forms.fields.description')" />

      <FormFieldWrapper id="sort_order" :label="$t('forms.fields.sort_order')">
        <Input id="sort_order" v-model.number="form.sort_order" type="number" min="0" />
      </FormFieldWrapper>

      <FormFieldWrapper id="is_active" :label="$t('forms.fields.is_active')">
        <label class="flex items-center gap-2 text-sm text-muted-foreground">
          <Switch id="is_active" v-model="form.is_active" />
          {{ $t('forms.fields.is_active') }}
        </label>
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import MultiLocaleTiptapFormItem from '@/Components/FormItems/MultiLocaleTiptapFormItem.vue';

const { eventType, rememberKey } = defineProps<{
  eventType: App.Entities.EventType;
  rememberKey?: string;
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = rememberKey ? useForm(rememberKey, eventType as any) : useForm(eventType as any);
</script>
