<template>
  <OverviewPage :title="section.title" :eyebrow="$t(`learning.section_kinds.${section.kind}`)" :lead="section.description || undefined">
    <template #actions>
      <p class="text-sm text-muted-foreground" data-slot="section-meta">
        <template v-if="section.date">
          <time :datetime="section.date" class="font-semibold text-foreground">{{ formatDate(section.date) }}</time>
          <span aria-hidden="true"> · </span>
        </template>
        <span class="tabular-nums">{{ transChoice('learning.wiki.alphabet_lead', pages.length, { count: String(pages.length) }) }}</span>
      </p>
      <Button v-if="editing" type="button" variant="outline" voice="sentence" size="sm" data-testid="edit-section" @click="editOpen = true">
        <Pencil class="size-4" aria-hidden="true" />{{ $t('learning_library.section_manager.edit_action') }}
      </Button>
      <Button v-if="canReorder && pages.length > 1 && !reordering" type="button" variant="outline" voice="sentence" size="sm" data-testid="reorder-section-items" @click="startReorder">
        <ArrowDownUp class="size-4" aria-hidden="true" />{{ $t('Keisti tvarką') }}
      </Button>
    </template>

    <div class="max-w-3xl">
      <div v-if="reordering" class="space-y-4" data-slot="section-reorder">
        <!-- Above the list: a collection can be long, and the save must stay in reach. -->
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="text-sm text-muted-foreground">
            {{ $t('learning.wiki.section_reorder_hint') }}
          </p>
          <div class="flex flex-wrap gap-2">
            <Button type="button" variant="ghost" voice="sentence" :disabled="saving" @click="reordering = false">
              {{ $t('Atšaukti') }}
            </Button>
            <Button type="button" :disabled="saving || !orderChanged" data-testid="save-section-order" @click="saveOrder">
              {{ $t('Išsaugoti tvarką') }}
            </Button>
          </div>
        </div>
        <SortableRowList
          v-model:items="draft"
          frame="box"
          :item-key="(page: WikiPageSummary) => page.id"
          :item-label="(page: WikiPageSummary) => page.title"
          :disabled="saving"
        >
          <template #default="{ item: page, index }">
            <p class="flex min-w-0 items-baseline gap-2 py-2.5">
              <span class="w-5 shrink-0 text-right text-xs tabular-nums text-muted-foreground">{{ index + 1 }}</span>
              <span class="truncate font-semibold">{{ page.title }}</span>
            </p>
          </template>
        </SortableRowList>
      </div>

      <WikiLinkPreview v-else-if="pages.length" :pages="previews">
        <WikiPageList :pages :done-ids="done" preview />
      </WikiLinkPreview>

      <EmptyState v-else :title="$t('learning.wiki.section_empty')" />
    </div>

    <LearningSectionsSheet
      v-if="editing"
      v-model:open="editOpen"
      :sections="editing.options"
      :scopes="editing.scopes"
      :can-create="false"
      :locale
      :edit-only="section.id"
    />
  </OverviewPage>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice } from 'laravel-vue-i18n';
import { ArrowDownUp, Pencil } from '@lucide/vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import SortableRowList from '@/Components/Collection/SortableRowList.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import WikiLinkPreview from '@/Features/Admin/Knowledge/WikiLinkPreview.vue';
import WikiPageList from '@/Features/Admin/Knowledge/WikiPageList.vue';
import LearningSectionsSheet from '@/Features/Admin/Learning/LearningSectionsSheet.vue';
import type { LearningSectionOption, LearningSectionScopes } from '@/Features/Admin/Learning/types';
import type { WikiPageSummary, WikiSection } from '@/Types/learning';

const props = defineProps<{
  section: WikiSection;
  pages: WikiPageSummary[];
  doneIds: string[];
  canReorder: boolean;
  /** This section as the Skyriai sheet edits it, for its editors only. */
  editing?: { options: LearningSectionOption[]; scopes: LearningSectionScopes } | null;
}>();
const editOpen = ref(false);
const locale = computed(() => (usePage().props.app as { locale?: string } | undefined)?.locale === 'en' ? 'en' : 'lt');

const { formatDate } = useDateFormatter();
const done = computed(() => new Set(props.doneIds));
const previews = computed(() => Object.fromEntries(props.pages.map(page => [page.id, page])));

const reordering = ref(false);
const draft = ref<WikiPageSummary[]>([]);
const saving = ref(false);
const orderChanged = computed(() => draft.value.some((page, index) => page.id !== props.pages[index]?.id));

function startReorder(): void {
  draft.value = [...props.pages];
  reordering.value = true;
}

function saveOrder(): void {
  saving.value = true;
  router.post(route('knowledgeSections.items.reorder', props.section.id), { items: draft.value.map(page => page.id) }, {
    preserveScroll: true,
    onSuccess: () => { reordering.value = false; },
    onFinish: () => { saving.value = false; },
  });
}
</script>
