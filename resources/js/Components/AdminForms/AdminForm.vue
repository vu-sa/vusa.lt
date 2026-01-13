<template>
  <ThemeProvider>
    <NForm v-bind="$attrs">
      <div class="flex flex-col pb-20">
        <!-- Status header slot (for publish status, preview buttons) -->
        <slot name="status-header" />

        <slot />
      </div>

      <!-- Sticky Bottom Action Bar -->
      <div
        class="fixed bottom-0 left-0 right-0 z-50 border-t bg-white/95 backdrop-blur-sm px-4 py-3 dark:bg-zinc-900/95 dark:border-zinc-800 md:left-(--sidebar-width,16rem)">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
          <!-- Left side: Status indicators -->
          <div class="flex items-center gap-3 text-sm">
            <Transition name="fade" mode="out-in">
              <div v-if="isSaving" key="saving" class="flex items-center gap-2 text-muted-foreground">
                <div
                  class="h-3 w-3 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-600 dark:border-zinc-600 dark:border-t-zinc-300" />
                <span class="hidden sm:inline">{{ $t('Išsaugoma...') }}</span>
              </div>
              <div v-else-if="recentlySaved" key="saved"
                class="flex items-center gap-2 text-green-600 dark:text-green-400">
                <IFluentCheckmarkCircle16Filled class="h-4 w-4" />
                <span class="hidden sm:inline">{{ $t('Išsaugota') }}</span>
              </div>
              <div v-else-if="props.model.isDirty" key="unsaved"
                class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                <div class="h-2 w-2 rounded-full bg-amber-500 animate-pulse" />
                <span class="hidden sm:inline">{{ $t('Neišsaugoti pakeitimai') }}</span>
              </div>
            </Transition>
          </div>

          <!-- Right side: Action buttons -->
          <div class="flex items-center gap-2 sm:gap-3">
            <!-- Autosave toggle -->
            <div class="hidden items-center gap-2 sm:flex">
              <Switch id="autosave" v-model="autosaveEnabled" />
              <Label for="autosave" class="cursor-pointer text-xs text-muted-foreground">
                {{ $t('Automatinis išsaugojimas') }}
              </Label>
            </div>

            <Separator orientation="vertical" class="hidden h-6 sm:block" />

            <slot name="buttons">
              <Button v-if="enableDelete" variant="ghost" size="icon"
                class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/30 sm:size-auto sm:px-4"
                @click="showDeleteDialog = true">
                <IFluentDelete24Filled class="h-4 w-4" />
                <span class="hidden sm:inline ml-2">{{ $t('Ištrinti') }}</span>
              </Button>
              <Button :disabled="isSaving" @click="handleSubmit">
                <IFluentSave24Filled class="h-4 w-4" />
                <span class="hidden sm:inline ml-2">{{ $t('Išsaugoti') }}</span>
              </Button>
            </slot>
          </div>
        </div>
      </div>
    </NForm>
  </ThemeProvider>

  <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
    <DialogContent class="sm:max-w-106.25">
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
import { watch, ref, onMounted, onUnmounted } from 'vue';
import { useDebounceFn, useTimeoutFn } from '@vueuse/core';
import { router } from '@inertiajs/vue3';

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Label } from '@/Components/ui/label';
import { Separator } from '@/Components/ui/separator';
import ThemeProvider from '@/Components/Providers/ThemeProvider.vue';

const props = defineProps<{
  model: Record<string, any>;
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form'): void;
  (event: 'delete'): void;
}>();

const showDeleteDialog = ref(false);
const isSaving = ref(false);
const recentlySaved = ref(false);
const autosaveEnabled = ref(false);

// Clear "saved" indicator after 3 seconds
const { start: startSavedTimeout } = useTimeoutFn(() => {
  recentlySaved.value = false;
}, 3000, { immediate: false });

const handleSubmit = () => {
  isSaving.value = true;
  emit('submit:form');
};

const handleDelete = () => {
  emit('delete');
  showDeleteDialog.value = false;
};

// Watch for form processing state to update status
watch(() => props.model.processing, (processing, wasProcessing) => {
  if (wasProcessing && !processing) {
    // Form finished processing
    isSaving.value = false;
    if (!props.model.hasErrors) {
      recentlySaved.value = true;
      startSavedTimeout();
    }
  }
});

// Inertia navigation guard - intercept navigation when form is dirty
let removeBeforeListener: (() => void) | null = null;

onMounted(() => {
  // Register Inertia before event listener
  removeBeforeListener = router.on('before', (event) => {
    // Skip prefetch requests
    if (event.detail.visit.prefetch) {
      return true;
    }

    if (props.model.isDirty && !isSaving.value) {
      // Use native browser confirm dialog
      return confirm($t('Turite neišsaugotų pakeitimų. Ar tikrai norite išeiti?'));
    }
    return true;
  });

  // Browser beforeunload event for tab close/refresh
  window.addEventListener('beforeunload', handleBeforeUnload);
});

onUnmounted(() => {
  // Clean up listeners
  removeBeforeListener?.();
  window.removeEventListener('beforeunload', handleBeforeUnload);
});

// Browser beforeunload handler
const handleBeforeUnload = (event: BeforeUnloadEvent) => {
  if (props.model.isDirty && !isSaving.value) {
    event.preventDefault();
    // Modern browsers ignore custom messages, but we still need to set returnValue
    event.returnValue = '';
    return '';
  }
};

// Autosave on form dirty with debounce (only when user enables autosave)
const autosaveFn = useDebounceFn(() => {
  if (props.model?.isDirty && autosaveEnabled.value && !isSaving.value) {
    handleSubmit();
  }
}, 5000);

// Watch the model data for changes (deep watch to detect any field changes)
watch(() => props.model.data(), () => {
  if (props.model?.isDirty && autosaveEnabled.value) {
    autosaveFn();
  }
}, { deep: true });
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
