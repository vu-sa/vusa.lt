<template>
  <FormPage
    :title="isCreate ? $t('Naujas ryšys') : (form.name || $t('Ryšys'))"
    :entity-type="ModelEnum.RELATIONSHIP"
    :back-href="route('relationships.index')"
    :back-label="$t('Ryšiai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :mode="isCreate ? 'create' : 'edit'"
    :available-locales="[]"
    :max-width="$slots.default ? '4xl' : '2xl'"
    @submit="$emit('submit:form', form)"
  >
    <FormSection :title="$t('forms.context.main_info')">
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required :error="form.errors.name">
        <Input id="name" v-model="form.name" type="text" :placeholder="$t('forms.placeholders.short_relationship_name')" />
      </FormFieldWrapper>
      <FormFieldWrapper id="slug" :label="$t('forms.fields.technical_slug')" :error="form.errors.slug">
        <Input id="slug" v-model="form.slug" type="text" :placeholder="$t('forms.placeholders.slug_example')" />
      </FormFieldWrapper>
      <FormFieldWrapper id="description" :label="$t('forms.fields.description')" :error="form.errors.description">
        <Textarea id="description" v-model="form.description" :placeholder="$t('forms.placeholders.short_description')" />
      </FormFieldWrapper>
    </FormSection>

    <!-- Connections are edited here until they move to ShowRelationship. -->
    <slot />

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <div class="flex flex-wrap items-center justify-between gap-3 border border-destructive/20 bg-destructive/5 p-4">
        <div>
          <h3 class="text-sm font-semibold text-destructive">
            {{ $t('Šalinti ryšį') }}
          </h3>
          <p class="text-xs text-muted-foreground">
            {{ $t('Bus pašalinti ir visi su šiuo ryšiu susieti įrašai.') }}
          </p>
        </div>
        <Button variant="destructive" size="sm" type="button" class="pointer-coarse:min-h-11" @click="isDeleteDialogOpen = true">
          {{ $t('Šalinti') }}
        </Button>
      </div>
    </template>
  </FormPage>

  <ConfirmDialog
    v-model:open="isDeleteDialogOpen"
    :title="$t('Šalinti ryšį?')"
    :description="$t('Bus pašalinti ir visi su šiuo ryšiu susieti įrašai.')"
    :confirm-label="$t('Šalinti')"
    destructive
    @confirm="$emit('delete')"
  />
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import FormFieldWrapper from './FormFieldWrapper.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { ModelEnum } from '@/Types/enums';

const { relationship, rememberKey, enableDelete = false } = defineProps<{
  relationship: App.Entities.Relationship;
  rememberKey?: 'CreateRelationship';
  enableDelete?: boolean;
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = rememberKey === 'CreateRelationship';
const isDeleteDialogOpen = ref(false);

const form = rememberKey ? useForm(rememberKey, relationship) : useForm(relationship);

defineExpose({ form });
</script>
