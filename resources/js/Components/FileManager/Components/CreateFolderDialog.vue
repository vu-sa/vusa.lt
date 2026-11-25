<template>
  <!-- The picker lives inside a dialog, so its form is inline rather than a second dialog. -->
  <form
    v-if="inline && open"
    class="mb-3 flex flex-col gap-2 border border-border bg-secondary/30 p-3 sm:flex-row sm:items-end"
    @submit.prevent="create"
  >
    <div class="grid flex-1 gap-1.5">
      <Label for="folderNameInline">{{ $t('files.ui.new_folder_name') }}</Label>
      <Input id="folderNameInline" v-model="name" :placeholder="$t('files.ui.name_placeholder')" />
    </div>
    <div class="flex gap-2">
      <Button type="submit" :disabled="!canSubmit">
        {{ $t('files.ui.create') }}
      </Button>
      <Button type="button" variant="outline" voice="sentence" @click="close">
        {{ $t('files.ui.cancel') }}
      </Button>
    </div>
  </form>

  <Dialog v-else-if="!inline" :open @update:open="(next) => !next && close()">
    <DialogContent class="sm:max-w-md">
      <form class="grid gap-4" @submit.prevent="create">
        <DialogHeader>
          <DialogTitle>{{ $t('files.ui.add_folder_title') }}</DialogTitle>
          <DialogDescription>
            {{ $t('files.ui.add_folder_in', { folder: folderLabel }) }}
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-2">
          <Label for="folderName">{{ $t('files.ui.new_folder_name') }}</Label>
          <Input id="folderName" v-model="name" :placeholder="$t('files.ui.folder_name_placeholder')" autocomplete="off" />
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" voice="sentence" @click="close">
            {{ $t('files.ui.cancel') }}
          </Button>
          <Button type="submit" variant="brand" :disabled="!canSubmit">
            {{ $t('files.ui.create') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

/** Creates a folder in `path`: inline above the picker's list, or as a dialog on the files page. */
const props = defineProps<{
  path: string;
  folderLabel: string;
  inline?: boolean;
}>();

const open = defineModel<boolean>('open', { required: true });
/** Mirrors the request so the manager can show its progress line. */
const busy = defineModel<boolean>('busy', { default: false });

const emit = defineEmits<{ created: [] }>();

const name = ref('');
// Local and synchronous: the parent's `busy` comes back through a prop only after the next render.
const submitting = ref(false);
const canSubmit = computed(() => !busy.value && !submitting.value && name.value.trim() !== '');

function close(): void {
  open.value = false;
  name.value = '';
}

function create(): void {
  if (!canSubmit.value) return;

  submitting.value = true;
  busy.value = true;
  router.post(route('files.createDirectory'), { path: props.path, name: name.value.trim() }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      close();
      emit('created');
    },
    onFinish: () => {
      submitting.value = false;
      busy.value = false;
    },
  });
}
</script>
