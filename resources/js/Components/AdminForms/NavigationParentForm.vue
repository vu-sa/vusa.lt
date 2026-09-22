<template>
  <FormPage
    :title="isCreate ? $t('navigation.builder.add_root') : (form.name || $t('navigation.title'))"
    :head-title="isCreate ? $t('navigation.builder.add_root') : (form.name || $t('navigation.title'))"
    :back-href="route('navigation.index')"
    :back-label="$t('navigation.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :mode="isCreate ? 'create' : 'edit'"
    max-width="4xl"
    @submit="$emit('submit:form', form)"
  >
    <template v-if="!isCreate" #header-actions>
      <slot name="aside-header" />
    </template>

    <FormSection
      :title="$t('forms.context.main_info')"
      :description="$t('forms.helpers.navigation_parent_info')"
    >
      <div class="grid gap-3 lg:grid-cols-2">
        <FormFieldWrapper id="name" :label="$t('forms.fields.name')" required :error="form.errors.name">
          <Input id="name" v-model="form.name" type="text" :placeholder="$t('forms.placeholders.enter_title')" />
        </FormFieldWrapper>
      </div>
    </FormSection>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <div class="flex items-center justify-between border border-destructive/20 bg-destructive/5 p-4">
        <div>
          <h3 class="text-sm font-semibold text-destructive">
            {{ $t('navigation.form.delete_link') }}
          </h3>
          <p class="text-xs text-muted-foreground">
            {{ $t('navigation.form.delete_link_desc') }}
          </p>
        </div>
        <Button variant="destructive" size="sm" type="button" @click="isDeleteDialogOpen = true">
          {{ $t('Ištrinti') }}
        </Button>
      </div>
    </template>
  </FormPage>

  <ConfirmDialog
    :open="isDeleteDialogOpen"
    :title="$t('Ištrinti navigacijos elementą?')"
    :description="$t('Ar tikrai norite perkelti šį elementą į šiukšliadėžę?')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="isDeleteDialogOpen = $event"
    @confirm="$emit('delete')"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = withDefaults(defineProps<{
  navigation: App.Entities.Navigation;
  rememberKey?: 'CreateNavigationParent';
  enableDelete?: boolean;
}>(), {
  rememberKey: undefined,
});

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = props.rememberKey ? useForm(props.rememberKey, props.navigation) : useForm(props.navigation);

if (!form.extra_attributes) {
  form.extra_attributes = {};
}

const isCreate = computed(() => !form.id);
const isDeleteDialogOpen = ref(false);
</script>
