<template>
  <Dialog :open="true" @update:open="(open) => !open && $emit('close')">
    <DialogContent class="rounded-none sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>
          {{ paths.length > 1
            ? $t('files.ui.confirm_delete_files_title', { count: String(paths.length) })
            : $t('files.ui.confirm_delete_file_title') }}
        </DialogTitle>
        <DialogDescription class="break-words">
          {{ description }}
        </DialogDescription>
      </DialogHeader>

      <div class="max-h-[50vh] space-y-3 overflow-y-auto" data-testid="delete-usage-check">
        <p v-if="usage.scanning.value" class="flex items-center gap-2 text-xs text-muted-foreground">
          <Spinner class="size-4" /> {{ $t('files.ui.checking_usage') }}
        </p>

        <template v-else-if="usage.results.value">
          <FileUsageList v-if="paths.length === 1 && usage.results.value[0]" :usage="usage.results.value[0]" />
          <template v-else-if="paths.length > 1">
            <p v-if="used.length === 0" class="border-l-2 border-status-success bg-status-success-surface p-3 text-xs text-foreground">
              {{ $t('files.ui.delete_none_used', { count: String(usage.results.value.length) }) }}
            </p>
            <template v-else>
              <p class="border-l-2 border-status-attention bg-status-attention-surface p-3 text-xs text-foreground">
                {{ $t('files.ui.delete_some_used', { used: String(used.length), count: String(usage.results.value.length) }) }}
              </p>
              <FileUsageList v-for="file in used" :key="file.path" :usage="file" :title="nameOf(file.path)" />
            </template>
          </template>
        </template>

        <div v-else class="space-y-3 border-l-2 border-status-attention bg-status-attention-surface p-3 text-xs text-foreground">
          <p>
            {{ usage.failed.value
              ? $t('files.ui.delete_check_failed')
              : $t(paths.length > 1 ? 'files.ui.delete_check_first_many' : 'files.ui.delete_check_first') }}
          </p>
          <Button variant="outline" voice="sentence" size="sm" @click="usage.scan(paths)">
            <SearchCheck class="size-4" aria-hidden="true" />
            {{ $t('files.ui.check_usage') }}
          </Button>
        </div>
      </div>

      <DialogFooter>
        <Button type="button" variant="outline" voice="sentence" @click="$emit('close')">
          {{ $t('files.ui.cancel') }}
        </Button>
        <Button
          type="button"
          variant="destructive"
          voice="brand"
          :disabled="!usage.results.value"
          data-testid="delete-files-confirm"
          @click="$emit('confirm')"
        >
          {{ $t('files.ui.delete') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { SearchCheck } from '@lucide/vue';

import { useFilesUsageScan } from '../useFileUsageScan';

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
import { Spinner } from '@/Components/ui/spinner';

/** Deleting stays locked until the usage check has run, so a link on some page is not broken unseen. */
const props = defineProps<{
  paths: string[];
}>();

defineEmits<{
  close: [];
  confirm: [];
}>();

const usage = useFilesUsageScan();

const nameOf = (path: string) => path.split('/').pop() ?? path;

const used = computed(() => (usage.results.value ?? []).filter(file => !file.is_safe_to_delete));

const description = computed(() => {
  const names = props.paths.map(nameOf);
  if (names.length === 1) {
    return $t('files.ui.confirm_delete_file_body', { name: names[0] });
  }
  const listed = names.slice(0, 5).join(', ') + (names.length > 5 ? '…' : '');
  return `${$t('files.ui.confirm_delete_files_body', { count: String(names.length) })} ${listed}`;
});
</script>
