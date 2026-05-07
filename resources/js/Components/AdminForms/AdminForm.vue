<template>
  <form v-bind="$attrs" @submit.prevent>
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
            <div v-if="props.model.processing" key="saving" class="flex items-center gap-2 text-muted-foreground">
              <div
                class="h-3 w-3 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-600 dark:border-zinc-600 dark:border-t-zinc-300" />
              <span class="hidden sm:inline">{{ $t('Išsaugoma...') }}</span>
            </div>
            <div v-else-if="props.model.recentlySuccessful" key="saved"
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
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <div class="hidden items-center gap-2 sm:flex" :class="{ 'opacity-50': isCreatePage }">
                  <Switch id="autosave" v-model="autosaveEnabled" :disabled="isCreatePage" />
                  <Label for="autosave" class="text-xs text-muted-foreground" :class="isCreatePage ? 'cursor-not-allowed' : 'cursor-pointer'">
                    {{ $t('Automatinis išsaugojimas') }}
                  </Label>
                </div>
              </TooltipTrigger>
              <TooltipContent v-if="isCreatePage">
                <p>{{ $t('Automatinis išsaugojimas galimas tik redaguojant esamą įrašą') }}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <Separator orientation="vertical" class="hidden h-6 sm:block" />

          <slot name="buttons">
            <Button v-if="enableDelete" variant="ghost" size="icon"
              class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/30 sm:size-auto sm:px-4"
              @click="showDeleteDialog = true">
              <IFluentDelete24Filled class="h-4 w-4" />
              <span class="hidden sm:inline ml-2">{{ $t('Ištrinti') }}</span>
            </Button>
            <Button :disabled="props.model.processing" @click="handleSubmit">
              <IFluentSave24Filled class="h-4 w-4" />
              <span class="hidden sm:inline ml-2">{{ $t('Išsaugoti') }}</span>
            </Button>
          </slot>
        </div>
      </div>
    </div>
  </form>

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
import { watch, ref, computed, onMounted, onUnmounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { router, usePage, type InertiaForm } from '@inertiajs/vue3';

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Label } from '@/Components/ui/label';
import { Separator } from '@/Components/ui/separator';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

const props = defineProps<{
  model: InertiaForm<any>;
  enableDelete?: boolean;
  isCreateForm?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form'): void;
  (event: 'delete'): void;
}>();

const page = usePage();

const showDeleteDialog = ref(false);
const autosaveEnabled = ref(false);
const isSubmitting = ref(false);

// Auto-detect create page from URL path, with ability to override via prop
const isCreatePage = computed(() => {
  // Check if URL path ends with /create
  return page.props.app?.path?.endsWith('/create') || props.isCreateForm === true;
});

const handleSubmit = () => {
  isSubmitting.value = true;
  emit('submit:form');
};

// Reset isSubmitting when Inertia finishes processing (success or error)
watch(() => props.model.processing, (processing, wasProcessing) => {
  if (wasProcessing && !processing) {
    isSubmitting.value = false;
  }
});

const handleDelete = () => {
  emit('delete');
  showDeleteDialog.value = false;
};

// Inertia navigation guard - intercept navigation when form is dirty
let removeBeforeListener: (() => void) | null = null;

onMounted(() => {
  // Register Inertia before event listener
  removeBeforeListener = router.on('before', (event) => {
    // Skip prefetch requests
    if (event.detail.visit.prefetch) {
      return;
    }

    if (props.model.isDirty && !props.model.processing && !isSubmitting.value) {
      // Use native browser confirm dialog
      if (!confirm($t('Turite neišsaugotų pakeitimų. Ar tikrai norite išeiti?'))) {
        event.preventDefault();
      }
    }
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
  if (props.model.isDirty && !props.model.processing && !isSubmitting.value) {
    event.preventDefault();
    // Modern browsers ignore custom messages, but we still need to set returnValue
    (event as BeforeUnloadEvent & { returnValue: string }).returnValue = '';
    return '';
  }
};

// Autosave on form dirty with debounce (only when user enables autosave and not a create form)
const autosaveFn = useDebounceFn(() => {
  if (props.model.isDirty && autosaveEnabled.value && !props.model.processing && !isCreatePage.value) {
    handleSubmit();
  }
}, 5000);

// Watch the model data for changes (deep watch to detect any field changes)
watch(() => props.model.data(), autosaveFn, { deep: true });
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
