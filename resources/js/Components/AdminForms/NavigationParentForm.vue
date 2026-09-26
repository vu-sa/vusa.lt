<template>
  <FormPage
    :title="isCreate ? $t('navigation.builder.add_root') : (form.name || $t('navigation.title'))"
    :bar-title
    entity-type="navigation"
    :back-href="route('navigation.index')"
    :back-label="$t('navigation.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids="['name']"
    :mode="isCreate ? 'create' : 'edit'"
    :activity-subject="navigation?.id ? { type: 'navigation', id: navigation.id } : undefined"
    :created-at="isCreate ? undefined : (navigation?.created_at as string | undefined)"
    :updated-at="isCreate ? undefined : (navigation?.updated_at as string | undefined)"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate" #header-actions>
      <slot name="aside-header" />
    </template>

    <div class="space-y-6">
      <FormFieldWrapper id="name" :label="$t('forms.fields.name')" required :error="form.errors.name">
        <Input
          id="name"
          v-model="form.name"
          type="text"
          :placeholder="$t('forms.placeholders.enter_title')"
          :class="['h-11', fieldSurfaceClass]"
        />
      </FormFieldWrapper>
    </div>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <Button
        type="button"
        variant="outline"
        class="border-destructive/40 text-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="isDeleteDialogOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('navigation.form.delete_link') }}
      </Button>
    </template>
  </FormPage>

  <ConfirmDialog
    v-model:open="isDeleteDialogOpen"
    :title="$t('Ištrinti navigacijos elementą?')"
    :description="$t('Ar tikrai norite perkelti šį elementą į šiukšliadėžę?')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="emit('delete')"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Trash2 } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';

const props = withDefaults(defineProps<{
  navigation: App.Entities.Navigation;
  rememberKey?: 'CreateNavigationParent';
  enableDelete?: boolean;
}>(), {
  rememberKey: undefined,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = props.rememberKey ? useForm(props.rememberKey, props.navigation) : useForm(props.navigation);

if (!form.extra_attributes) {
  form.extra_attributes = {};
}

const isCreate = computed(() => !form.id);
const isDeleteDialogOpen = ref(false);

const barTitle = computed(() =>
  form.name?.trim() || (isCreate.value ? $t('navigation.builder.add_root') : $t('navigation.title')),
);

defineExpose({
  form,
});
</script>
