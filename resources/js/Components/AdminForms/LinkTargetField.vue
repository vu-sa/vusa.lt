<template>
  <FormFieldWrapper :id :label="label ?? $t('navigation.form.link_target')">
    <div data-slot="link-target-field" class="flex flex-wrap items-center gap-2">
      <MultiCollectionSelectDialog
        v-model:open="pickerOpen"
        :collections
        :title="dialogTitle ?? $t('navigation.form.link_target')"
        :confirm-label="$t('Pasirinkti')"
        :search-placeholder="$t('navigation.form.link_target_search')"
        @confirm="onTargetConfirm"
      >
        <template #trigger>
          <Button type="button" variant="outline" class="justify-between font-normal">
            <span class="truncate" :class="{ 'text-muted-foreground': !lastPickedLabel }">
              {{ lastPickedLabel ?? $t('navigation.form.link_target_placeholder') }}
            </span>
            <ChevronDown class="ml-2 size-4 opacity-50" />
          </Button>
        </template>
      </MultiCollectionSelectDialog>

      <span class="text-xs text-muted-foreground">{{ $t('navigation.form.or') }}</span>

      <NativeSelect
        class="w-auto min-w-40"
        size="sm"
        :placeholder="$t('navigation.form.link_target_topic')"
        :model-value="topicSelectValue"
        :options="topicSelectOptions"
        @update:model-value="onTopicSelected"
      />

      <Loader2 v-if="isResolvingUrl" class="size-4 animate-spin text-muted-foreground" />
    </div>
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { ChevronDown, Loader2 } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { MultiCollectionSelectDialog } from '@/Components/Pickers';
import type { AdminSearchCollectionId, NormalizedSearchHit } from '@/Components/Search/adminSearchCollections';
import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import { useApiMutation } from '@/Composables/useApi';

export interface LinkTargetTopicOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = withDefaults(defineProps<{
  id?: string;
  label?: string;
  dialogTitle?: string;
  collections?: AdminSearchCollectionId[];
  topicOptions?: LinkTargetTopicOption[];
}>(), {
  id: 'link_target',
  label: undefined,
  dialogTitle: undefined,
  collections: () => ['pages', 'news', 'calendar', 'institutions', 'documents'],
  topicOptions: () => [],
});

const emit = defineEmits<{
  select: [url: string];
}>();

const pickerOpen = ref(false);
const lastPickedLabel = ref<string | null>(null);
const topicSelectValue = ref<string | undefined>(undefined);

const resolveUrlBody = ref<{ collection: string; id: string | number } | null>(null);
const { execute: executeResolveUrl, data: resolveUrlData, isFetching: isResolvingUrl } = useApiMutation<
  { url: string },
  { collection: string; id: string | number } | null
>(
  route('api.v1.admin.navigation.resolveUrl'),
  'POST',
  resolveUrlBody,
  { showSuccessToast: false },
);

async function resolveTarget(collection: string, id: string | number, label: string): Promise<void> {
  resolveUrlBody.value = { collection, id };
  await executeResolveUrl();
  if (resolveUrlData.value?.url) {
    lastPickedLabel.value = label;
    emit('select', resolveUrlData.value.url);
  }
}

function onTargetConfirm(hits: NormalizedSearchHit[]): void {
  const [hit] = hits;
  if (!hit) {
    return;
  }
  topicSelectValue.value = undefined;
  void resolveTarget(hit.collection, hit.recordId, hit.title);
}

const topicSelectOptions = computed(() =>
  props.topicOptions.map(topic => ({ value: String(topic.id), label: topic.name })),
);

function onTopicSelected(val: string | number | null | undefined): void {
  const value = val == null || val === '' ? undefined : String(val);
  topicSelectValue.value = value;
  const topic = props.topicOptions.find(item => String(item.id) === value);
  if (topic) {
    void resolveTarget('topics', topic.id, topic.name);
  }
}
</script>
