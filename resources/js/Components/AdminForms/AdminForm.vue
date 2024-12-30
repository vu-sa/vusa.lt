<template>
  <NForm v-bind="$attrs">
    <div class="flex flex-col">
      <slot />
    </div>
    <div class="mt-3 flex justify-end gap-4">
      <slot name="buttons">
        <NButton v-if="enableDelete" text type="error" @click="handleDelete">
          <template #icon>
            <IFluentDelete24Filled />
          </template>
          {{ $t('Ištrinti') }}
        </NButton>
        <NButton type="primary" @click="$emit('submit:form')">
          <template #icon>
            <IFluentSave24Filled />
          </template>
          {{ $t('Išsaugoti') }}
        </NButton>
      </slot>
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { useDialog } from 'naive-ui';
import { trans as $t } from 'laravel-vue-i18n';
import { inject, watch, type Ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';

const { model } = defineProps<{
  model: Record<string, any>;
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form'): void;
  (event: 'delete'): void;
}>();

const dialog = useDialog();

// NOTE: Duplicated in ActionColumns.vue
const handleDelete = () => {
  dialog.warning({
    title: $t('Ištrinti įrašą'),
    content: $t('Ar tikrai norite ištrinti šį įrašą?'),
    positiveText: $t('Ištrinti'),
    negativeText: $t('Atšaukti'),
    onPositiveClick: () => {
      emit('delete');
    },
  });
}

const autosave = inject<Ref<boolean> | null>('autosave', null);

// add autosave on form dirty with debounce
const autosaveFn = useDebounceFn(() => {
  emit('submit:form');
}, 5000);

watch(() => model.isDirty, () => {
  if (model?.isDirty && autosave) {
    autosaveFn();
  }
}, { deep: true });
</script>
