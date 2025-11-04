<template>
  <ThemeProvider>
    <NForm v-bind="$attrs">
      <div class="flex flex-col">
        <slot />
      </div>
      <div class="mt-3 flex justify-end gap-4">
        <slot name="buttons">
          <NButton v-if="enableDelete" text type="error" @click="showDeleteDialog = true">
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
  </ThemeProvider>

  <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ $t('Ištrinti įrašą') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Ar tikrai norite ištrinti šį įrašą?') }}
        </DialogDescription>
      </DialogHeader>
      
      <DialogFooter>
        <Button variant="outline" @click="showDeleteDialog = false">
          {{ $t('Atšaukti') }}
        </Button>
        <Button variant="destructive" @click="handleDelete">
          {{ $t('Ištrinti') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { inject, watch, ref, type Ref } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import ThemeProvider from '@/Components/Providers/ThemeProvider.vue';

const { model } = defineProps<{
  model: Record<string, any>;
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form'): void;
  (event: 'delete'): void;
}>();

const showDeleteDialog = ref(false);

const handleDelete = () => {
  emit('delete');
  showDeleteDialog.value = false;
};

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
