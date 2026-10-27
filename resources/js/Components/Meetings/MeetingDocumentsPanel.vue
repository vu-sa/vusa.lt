<template>
  <SectionCard
    :title="$t('Susieti dokumentai')"
    :icon="FileText"
    :count="documents.length"
    :empty="documents.length === 0"
  >
    <template #action>
      <div v-if="canUpdate" class="flex flex-wrap items-center gap-2">
        <MeetingPendingDocumentsDialog
          v-if="hasPending"
          :meeting-id
          :suggestions="pendingDocuments ?? []"
          @confirm="linkInTurn"
        />

        <FilePicker v-if="pickerAvailable" @pick="linkFromSharepoint">
          <template #trigger>
            <Button type="button" variant="outline" size="sm" voice="sentence" :disabled="picking">
              <Spinner v-if="picking" class="mr-1.5 size-3.5" />
              <FileUp v-else class="mr-1.5 size-3.5" />
              {{ $t('Pasirinkti iš SharePoint') }}
            </Button>
          </template>
        </FilePicker>

        <CollectionSelectDialog
          v-model:open="pickerOpen"
          collection="documents"
          multiple
          :base-filter-by="documentFilter"
          :disabled-ids="linkedIds"
          :title="$t('Susieti dokumentą')"
          :description="$t('meetings.documents.picker_explainer')"
          :confirm-label="$t('Susieti')"
          :search-placeholder="$t('Ieškoti dokumento pagal pavadinimą...')"
          :empty-message="$t('meetings.documents.none_available')"
          @confirm="linkDocuments"
        >
          <template #trigger>
            <Button type="button" variant="outline" size="sm" voice="sentence">
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

    <ul v-if="documents.length > 0" class="divide-y divide-border">
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
          <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
            <!-- SharePoint records each file's language, and a body's paperwork is often
                 filed in both — so say which this one is. -->
            <span
              v-if="languageLabel(document)"
              class="border border-border px-1 py-px text-[11px] font-semibold uppercase tracking-wide"
            >{{ languageLabel(document) }}</span>
            <span>
              {{ document.content_type }}
              <template v-if="document.document_date"> · {{ document.document_date }}</template>
            </span>
          </span>
        </div>
        <button
          v-if="canUpdate"
          type="button"
          class="flex size-8 shrink-0 items-center justify-center text-muted-foreground transition-colors hover:text-destructive pointer-coarse:size-11"
          :title="$t('Atsieti dokumentą')"
          :aria-label="$t('Atsieti dokumentą')"
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
import { FileText, FileUp, Link2, X } from '@lucide/vue';
import { toast } from 'vue-sonner';

import MeetingPendingDocumentsDialog from './MeetingPendingDocumentsDialog.vue';

import type { DocumentFolderRow } from '@/Components/Files';
import { EmptyState, SectionCard } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import CollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectDialog.vue';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import FilePicker from '@/Features/Admin/SharepointFilePicker/FilePicker.vue';
import { isPickerAvailable, pickedDocuments, type Item } from '@/Features/Admin/SharepointFilePicker/picker';

export interface MeetingDocument {
  id: number;
  title: string | null;
  name: string | null;
  content_type: string | null;
  document_date: string | null;
  anonymous_url: string | null;
  /** Normalized `lt` / `en` / `unknown` — see Document::languageCode(). */
  language_code?: string | null;
}

/** `unknown` earns no chip — an unlabelled file is not a claim about its language. */
const languageLabel = (document: MeetingDocument): string =>
  (document.language_code && document.language_code !== 'unknown'
    ? document.language_code.toUpperCase()
    : '');

const props = defineProps<{
  meetingId: string;
  documents: MeetingDocument[];
  /** Institutions of the meeting; a document may only be linked from one of them… */
  institutionIds?: string[];
  /** …or from any institution of these tenants — internal bodies' paperwork is filed under
   *  the central institution of the same tenant, not under the body itself. */
  tenantShortnames?: string[];
  canUpdate?: boolean;
  /** Unpublished SharePoint files of the meeting's padaliniai, offered to publish and link. */
  pendingDocuments?: DocumentFolderRow[];
}>();

const hasPending = computed(() => Boolean(props.canUpdate && props.pendingDocuments?.length));

const pickerOpen = ref(false);

const documentFilter = computed(() => {
  const clauses: string[] = [];

  if (props.institutionIds?.length) {
    clauses.push(`institution_id:=[${props.institutionIds.map(id => `\`${id}\``).join(',')}]`);
  }
  if (props.tenantShortnames?.length) {
    clauses.push(`tenant_shortname:=[${props.tenantShortnames.map(name => `\`${name}\``).join(',')}]`);
  }

  return clauses.length ? clauses.join(' || ') : undefined;
});

/** Already-linked rows are shown as unselectable rather than silently failing on confirm. */
const linkedIds = computed(() => new Set(props.documents.map(document => String(document.id))));

/** One at a time: a new Inertia visit cancels the one still in flight. */
const linkInTurn = ([documentId, ...rest]: number[]) => {
  if (documentId === undefined) {
    return;
  }
  router.post(
    route('meetings.documents.store', { meeting: props.meetingId }),
    { document_id: documentId },
    {
      preserveScroll: true,
      // A file already linked to another meeting is refused, not moved.
      onError: errors => errors.document_id && toast.error(errors.document_id),
      onFinish: () => linkInTurn(rest),
    },
  );
};

const linkDocuments = (hits: NormalizedSearchHit[]) => {
  linkInTurn(hits.map(hit => Number(hit.recordId)));
  pickerOpen.value = false;
};

const pickerAvailable = computed(() => isPickerAvailable(usePage().props.app?.url));
const picking = ref(false);

/** The habit from before discovery: pick the protokolas in SharePoint; it is published and linked. */
const linkFromSharepoint = (items: Item[]) => {
  picking.value = true;
  router.post(
    route('meetings.documents.storeFromSharepoint', { meeting: props.meetingId }),
    { documents: pickedDocuments(items) },
    {
      preserveScroll: true,
      onError: errors => errors.documents && toast.error(errors.documents),
      onFinish: () => { picking.value = false; },
    },
  );
};

const unlink = (documentId: number) => {
  router.delete(
    route('meetings.documents.destroy', { meeting: props.meetingId, document: documentId }),
    { preserveScroll: true },
  );
};
</script>
