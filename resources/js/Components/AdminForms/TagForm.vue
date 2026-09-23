<template>
  <!-- @deprecated Superseded by TagSheetForm; remove together with the tags.create/edit pages. -->
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <MdSuspenseWrapper directory="tags" :locale="$page.props.app.locale" file="description" />
      </template>

      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <MultiLocaleTiptapFormItem v-model:input="form.description" :label="$t('forms.fields.description')" />

      <FormFieldWrapper id="alias" :label="$t('forms.fields.alias')" :helper-text="$t('forms.helpers.tag_alias_hint')">
        <Input id="alias" v-model="form.alias" placeholder="Pvz: stipendijos" />
      </FormFieldWrapper>

      <FormFieldWrapper id="is_topic" :label="$t('forms.fields.is_topic')" :helper-text="$t('forms.helpers.is_topic_hint')">
        <label class="flex items-center gap-2 text-sm text-muted-foreground">
          <Switch id="is_topic" v-model="form.is_topic" />
          {{ $t('forms.fields.is_topic') }}
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
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';

const { postTag, rememberKey } = defineProps<{
  postTag: App.Entities.Tag;
  rememberKey?: 'CreateTag';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = rememberKey ? useForm(rememberKey, postTag as any) : useForm(postTag as any);
</script>
