<template>
  <SheetForm
    required-note
    v-model:open="open"
    :created-at="mailingList?.created_at"
    :updated-at="mailingList?.updated_at"
    :title="mailingList ? $t('mailing_lists.edit.title') : $t('mailing_lists.create.title')"
    :description="mailingList ? undefined : $t('mailing_lists.create.description')"
    :save-label="mailingList ? $t('mailing_lists.edit.submit') : $t('mailing_lists.create.submit')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :initial-focus="mailingList ? 'mailing_list_display_name' : 'mailing_list_local_part'"
    @submit="submit"
  >
    <div class="space-y-6">
      <FormFieldWrapper
        v-if="!mailingList"
        id="mailing_list_local_part"
        :label="$t('mailing_lists.fields.local_part')"
        :hint="$t('mailing_lists.hints.local_part')"
        :error="form.errors.local_part"
        required
      >
        <div class="flex items-center gap-2">
          <Input id="mailing_list_local_part" v-model="form.local_part" autocomplete="off" class="flex-1" data-testid="mailing-list-local-part" />
          <span class="shrink-0 text-sm text-muted-foreground">@{{ domain }}</span>
        </div>
      </FormFieldWrapper>

      <FormFieldWrapper id="mailing_list_display_name" :label="$t('mailing_lists.fields.display_name')" :error="form.errors.display_name" required>
        <Input id="mailing_list_display_name" v-model="form.display_name" />
      </FormFieldWrapper>

      <FormFieldWrapper id="mailing_list_description" :label="$t('mailing_lists.fields.description')" :error="form.errors.description">
        <Textarea id="mailing_list_description" v-model="form.description" rows="3" />
      </FormFieldWrapper>

      <TenantSelectField id="mailing_list_tenant" v-model="form.tenant_id" :tenants :error="form.errors.tenant_id" />
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

import type { MailingListSummary } from './types';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField, { pickDefaultTenantId } from '@/Components/AdminForms/TenantSelectField.vue';
import { SheetForm } from '@/Components/Patterns';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

const props = defineProps<{
  tenants: App.Entities.Tenant[];
  domain?: string;
  /** Editing an existing list; creating when absent. */
  mailingList?: MailingListSummary | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
  local_part: '',
  display_name: props.mailingList?.display_name ?? '',
  description: props.mailingList?.description ?? '',
  tenant_id: props.mailingList?.tenant?.id ?? pickDefaultTenantId(props.tenants),
});

watch(open, (isOpen) => {
  if (isOpen) {
    form.defaults({
      local_part: '',
      display_name: props.mailingList?.display_name ?? '',
      description: props.mailingList?.description ?? '',
      tenant_id: props.mailingList?.tenant?.id ?? pickDefaultTenantId(props.tenants),
    });
    form.reset();
    form.clearErrors();
  }
});

function submit(): void {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      open.value = false;
    },
  };

  if (props.mailingList) {
    form.patch(route('mailingLists.update', props.mailingList.id), { ...options, except: ['roster', 'diff', 'messages'] });
  }
  else {
    form.post(route('mailingLists.store'), options);
  }
}
</script>
