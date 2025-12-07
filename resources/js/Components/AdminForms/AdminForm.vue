<template>
  <ThemeProvider>
    <NForm v-bind="$attrs">
      <div class="flex flex-col">
        <slot />
      </div>
      <div class="mt-3 flex justify-end gap-4">
        <slot name="buttons">
          <Button v-if="enableDelete" variant="ghost" class="text-red-600 hover:text-red-700 hover:bg-red-50" @click="showDeleteDialog = true">
            <IFluentDelete24Filled />
            {{ $t('Ištrinti') }}
          </Button>
          <Button @click="$emit('submit:form')">
            <IFluentSave24Filled />
            {{ $t('Išsaugoti') }}
          </Button>
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
