<template>
  <SectionCard
    :title="$t('Susieti dokumentai')"
    :icon="FileText"
    :count="documents.length"
    :empty="documents.length === 0"
  >
    <template #action>
      <div v-if="canUpdate" class="flex items-center gap-2">
        <FilePicker
          v-if="sharepointPickerAvailable"
          :loading="uploading"
          size="sm"
          @pick="uploadFromSharepoint"
        >
          <span class="flex items-center gap-1.5">
            <Upload class="size-3.5" />
            {{ $t('Įkelti iš SharePoint') }}
          </span>
        </FilePicker>

        <CollectionSelectDialog
          v-model:open="pickerOpen"
          collection="documents"
          multiple
          :base-filter-by="institutionFilter"
          :disabled-ids="linkedIds"
          :title="$t('Susieti dokumentą')"
          :description="$t('meetings.documents.picker_explainer')"
          :confirm-label="$t('Susieti')"
          :search-placeholder="$t('Ieškoti dokumento pagal pavadinimą...')"
          :empty-message="$t('meetings.documents.none_available')"
          @confirm="linkDocuments"
        >
          <template #trigger>
            <Button type="button" variant="outline" size="sm">
              <Link2 class="mr-1.5 size-3.5" />
              {{ $t('Susieti dokumentą') }}
            </Button>
          </template>
        </CollectionSelectDialog>
      </div>
    </template>

    <template #empty>
      <EmptyState
        :title="$t('meetings.documents.empty_title')"
        :description="$t('meetings.documents.empty_description')"
      />
    </template>

    <ul class="divide-y divide-border">
      <li v-for="document in documents" :key="document.id" class="flex items-center gap-3 py-2.5">
        <FileText class="size-4 shrink-0 text-muted-foreground" />
        <div class="min-w-0 flex-1">
          <a
            v-if="document.anonymous_url"
            :href="document.anonymous_url"
            target="_blank"
            rel="noopener noreferrer"
            class="block truncate text-sm font-medium hover:underline"
          >{{ document.title || document.name }}</a>
          <span v-else class="block truncate text-sm font-medium">{{ document.title || document.name }}</span>
          <span class="text-xs text-muted-foreground">
            {{ document.content_type }}
            <template v-if="document.document_date"> · {{ document.document_date }}</template>
          </span>
        </div>
        <button
          v-if="canUpdate"
          type="button"
          class="shrink-0 text-muted-foreground transition-colors hover:text-destructive"
          :title="$t('Atsieti dokumentą')"
          @click="unlink(document.id)"
        >
          <X class="size-4" />
        </button>
      </li>
    </ul>
  </SectionCard>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { FileText, Link2, Upload, X } from 'lucide-vue-next';

import { EmptyState, SectionCard } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import FilePicker from '@/Features/Admin/SharepointFilePicker/FilePicker.vue';
import type { Item } from '@/Features/Admin/SharepointFilePicker/picker';

export interface MeetingDocument {
  id: number;
  title: string | null;
  name: string | null;
  content_type: string | null;
  document_date: string | null;
  anonymous_url: string | null;
}

const props = defineProps<{
  meetingId: string;
  documents: MeetingDocument[];
  /** Institutions of the meeting; a document may only be linked from one of them. */
  institutionIds?: string[];
  canUpdate?: boolean;
}>();

const page = usePage();
const pickerOpen = ref(false);
const uploading = ref(false);

// The SharePoint picker only works over https — same guard as the documents index.
const sharepointPickerAvailable = computed(() => String(page.props.app.url).startsWith('https'));

const institutionFilter = computed(() =>
  props.institutionIds?.length
    ? `institution_id:=[${props.institutionIds.map(id => `\`${id}\``).join(',')}]`
    : undefined,
);

/** Already-linked rows are shown as unselectable rather than silently failing on confirm. */
const linkedIds = computed(() => new Set(props.documents.map(document => String(document.id))));

const linkDocuments = (hits: NormalizedSearchHit[]) => {
  hits.forEach((hit) => {
    router.post(
      route('meetings.documents.store', { meeting: props.meetingId }),
      { document_id: Number(hit.recordId) },
      { preserveScroll: true },
    );
  });
  pickerOpen.value = false;
};

const uploadFromSharepoint = (items: Item[]) => {
  uploading.value = true;

  router.post(
    route('meetings.documents.storeFromSharepoint', { meeting: props.meetingId }),
    {
      documents: items.map(item => ({
        name: item.name,
        site_id: item.sharepointIds?.siteId,
        list_id: item.sharepointIds?.listId,
        list_item_unique_id: item.sharepointIds?.listItemUniqueId,
      })),
    },
    { preserveScroll: true, onFinish: () => (uploading.value = false) },
  );
};

const unlink = (documentId: number) => {
  router.delete(
    route('meetings.documents.destroy', { meeting: props.meetingId, document: documentId }),
    { preserveScroll: true },
  );
};
</script>
