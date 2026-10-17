<template>
  <Dialog open @update:open="(open) => !open && $emit('close')">
    <DialogContent class="rounded-none sm:max-w-md">
      <form class="grid gap-4" @submit.prevent="submit">
        <DialogHeader>
          <DialogTitle>{{ isFolder ? $t('files.ui.rename_folder_title') : $t('files.ui.rename_file_title') }}</DialogTitle>
          <DialogDescription class="break-words">
            {{ name }}
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-2">
          <Label for="file-rename-input">{{ $t('files.ui.new_name') }}</Label>
          <div class="flex items-center">
            <Input id="file-rename-input" ref="input" v-model="baseName" class="flex-1" autocomplete="off" />
            <span v-if="extension" class="flex h-11 items-center border border-l-0 border-border bg-secondary/50 px-3 text-sm text-muted-foreground">.{{ extension }}</span>
          </div>
          <p v-if="error" class="text-xs text-destructive">
            {{ error }}
          </p>
        </div>

        <div v-if="isFolder" class="border-l-2 border-status-attention bg-status-attention-surface p-3 text-xs text-foreground">
          {{ $t('files.ui.rename_folder_warning') }}
        </div>
        <template v-else>
          <p v-if="usage.scanning.value" class="flex items-center gap-2 text-xs text-muted-foreground">
            <Spinner class="size-4" /> {{ $t('files.ui.checking_usage') }}
          </p>
          <template v-else-if="usage.result.value && !usage.result.value.is_safe_to_delete">
            <div class="border-l-2 border-status-attention bg-status-attention-surface p-3 text-xs text-foreground">
              {{ $t('files.ui.rename_used_warning', { count: String(usage.result.value.total_usages) }) }}
            </div>
            <FileUsageList :usage="usage.result.value" />
          </template>
        </template>

        <DialogFooter>
          <Button type="button" variant="outline" voice="sentence" @click="$emit('close')">
            {{ $t('files.ui.cancel') }}
          </Button>
          <Button type="submit" variant="brand" :disabled="processing || !baseName.trim() || unchanged">
            <Spinner v-if="processing" class="size-4" />
            {{ $t('files.ui.rename') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { useFileUsageScan } from '../useFileUsageScan';

import FileUsageList from './FileUsageList.vue';

import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { getFileExtension } from '@/Utils/fileIcons';

const props = defineProps<{
  path: string;
  name: string;
  isFolder: boolean;
}>();

const emit = defineEmits<{
  close: [];
  renamed: [from: string, to: string];
}>();

const usage = useFileUsageScan();

/** A file keeps its extension (the server enforces it too), so only the base name is editable. */
const extension = computed(() => (props.isFolder ? '' : getFileExtension(props.name)));
const originalBase = extension.value ? props.name.slice(0, -(extension.value.length + 1)) : props.name;

const baseName = ref(originalBase);
const processing = ref(false);
const error = ref<string | null>(null);

const unchanged = computed(() => baseName.value.trim() === originalBase);

onMounted(() => {
  if (!props.isFolder) {
    usage.scan(props.path);
  }
});

function submit() {
  if (processing.value || unchanged.value || !baseName.value.trim()) return;

  const name = extension.value ? `${baseName.value.trim()}.${extension.value}` : baseName.value.trim();
  processing.value = true;
  error.value = null;

  router.post(route('files.rename'), { path: props.path, name }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('renamed', props.path, `${props.path.split('/').slice(0, -1).join('/')}/${name}`);
    },
    onError: (errors) => {
      error.value = (errors.name ?? errors.error ?? Object.values(errors)[0] ?? null) as string | null;
    },
    onFinish: () => {
      processing.value = false;
    },
  });
}
</script>
