<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t('forms.context.main_info') }}
      </template>
      <template #description>
        {{ $t('forms.helpers.navigation_parent_info') }}
      </template>
      <div class="grid gap-3 lg:grid-cols-2">
        <FormFieldWrapper id="name" :label="$t('forms.fields.name')" required :error="form.errors.name">
          <Input id="name" v-model="form.name" type="text" :placeholder="$t('forms.placeholders.enter_title')" />
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper id="menu_width" :label="$t('navigation.form.menu_width')" :hint="$t('navigation.form.menu_width_hint')">
        <ToggleGroup v-model="menuWidth" type="single" class="justify-start">
          <ToggleGroupItem value="wide">{{ $t('navigation.form.menu_width_wide') }}</ToggleGroupItem>
          <ToggleGroupItem value="medium">{{ $t('navigation.form.menu_width_medium') }}</ToggleGroupItem>
          <ToggleGroupItem value="narrow">{{ $t('navigation.form.menu_width_narrow') }}</ToggleGroupItem>
          <ToggleGroupItem value="auto">{{ $t('navigation.form.menu_width_auto') }}</ToggleGroupItem>
        </ToggleGroup>
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { Input } from '@/Components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';

const { navigation, rememberKey } = defineProps<{
  navigation: App.Entities.Navigation;
  rememberKey?: 'CreateNavigationParent';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = rememberKey ? useForm(rememberKey, navigation) : useForm(navigation);

if (!form.extra_attributes) {
  form.extra_attributes = {};
}

const menuWidth = computed({
  get: () => form.extra_attributes.menu_width ?? 'wide',
  set: (val: string) => { form.extra_attributes.menu_width = val; },
});
</script>
