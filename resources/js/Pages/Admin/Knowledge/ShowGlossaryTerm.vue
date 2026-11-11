<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, Check, Pencil } from '@lucide/vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { ConfirmDialog, EmptyState, RecordActionMenu } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { enterKnowledgeRecord } from '@/Composables/useRecordTrail';
import GlossaryTermSheet from '@/Features/Admin/Learning/GlossaryTermSheet.vue';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import type { GlossaryTermPageData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

type TermItem = GlossaryTermPageData['items'][number];

const props = withDefaults(defineProps<{
  term: GlossaryTermPageData;
  canUpdate?: boolean;
  canDelete?: boolean;
}>(), { canUpdate: false, canDelete: false });

const sheetOpen = ref(false);
const deleteOpen = ref(false);
const inPath = computed(() => props.term.items.filter(item => item.inPath));
const elsewhere = computed(() => props.term.items.filter(item => !item.inPath));
const actions = computed<AdminRecordAction[]>(() => props.canUpdate ? [{ key: 'edit', label: $t('Redaguoti'), icon: Pencil }] : []);
const resolved = (item: TermItem) => item.state === 'done' || item.state === 'skipped';
const sourceHref = (item: TermItem) => safeHref(item.source?.url);

const enterTrail = () => enterKnowledgeRecord({ id: props.term.id, title: props.term.term, href: route('glossaryTerms.show', props.term.id) });
onMounted(enterTrail);
watch(() => props.term.id, enterTrail);

function destroy(): void {
  deleteOpen.value = false;
  sheetOpen.value = false;
  router.delete(route('glossaryTerms.destroy', props.term.id));
}
</script>

<template>
  <OverviewPage :title="term.term" :eyebrow="$t('learning.pages.term')">
    <template #heading>
      <div class="min-w-0" data-slot="term-heading">
        <h1 class="mt-3 flex flex-wrap items-baseline gap-x-3 gap-y-1 text-balance text-3xl font-bold leading-tight text-foreground sm:text-4xl">
          {{ term.term }}
          <abbr v-if="term.abbreviation" :title="term.term" class="border border-border px-2 py-0.5 text-base font-bold tracking-wide no-underline sm:text-lg" data-testid="term-abbreviation">{{ term.abbreviation }}</abbr>
        </h1>
      </div>
    </template>
    <template v-if="actions.length" #actions>
      <RecordActionMenu :actions="actions" trigger-size="compact" @select="key => key === 'edit' && (sheetOpen = true)" />
    </template>

    <p class="max-w-prose whitespace-pre-line text-lg leading-relaxed text-foreground" data-slot="term-definition">{{ term.definition }}</p>

    <section v-if="inPath.length" aria-labelledby="term-explained-in" class="space-y-3 border-t border-border pt-6" data-slot="term-items">
      <div>
        <h2 id="term-explained-in" class="text-xl font-bold leading-tight text-foreground sm:text-2xl">{{ $t('learning.glossary.explained_in') }}</h2>
        <p class="mt-1 text-sm text-muted-foreground">{{ $t('learning.glossary.explained_in_hint') }}</p>
      </div>
      <ul class="divide-y divide-border border border-border">
        <li v-for="item in inPath" :key="item.id" class="grid grid-cols-[1.5rem_minmax(0,1fr)_auto] items-start gap-x-3 px-4 py-3" :data-item="item.id">
          <span
            :class="[
              'mt-0.5 flex size-5 items-center justify-center border',
              resolved(item) ? 'border-foreground bg-foreground text-background' : item.importance === 'essential' ? 'border-brand' : 'border-muted-foreground',
              item.importance === 'optional' && !resolved(item) && 'border-dashed',
            ]"
            role="img"
            :aria-label="$t(`learning.states.${resolved(item) ? item.state : 'pending'}`)"
          >
            <Check v-if="resolved(item)" class="size-3.5" aria-hidden="true" />
          </span>
          <div class="min-w-0">
            <Link :href="item.url" :class="['font-semibold leading-6 underline decoration-border underline-offset-4 hover:decoration-foreground', resolved(item) ? 'text-muted-foreground' : 'text-foreground']">{{ item.title }}</Link>
            <p class="mt-0.5 flex flex-wrap gap-x-2 text-xs text-muted-foreground">
              <span v-if="item.importance" :class="item.importance === 'essential' && 'font-semibold text-brand'">{{ $t(`learning.importance.${item.importance}`) }}</span>
              <template v-if="item.section">
                <span aria-hidden="true">·</span><span>{{ item.section.title }}</span>
              </template>
            </p>
          </div>
          <Button v-if="sourceHref(item)" as="a" :href="sourceHref(item)" target="_blank" rel="noopener noreferrer" variant="outline" voice="sentence" size="sm">
            {{ $t('learning.path.read_document') }}<ArrowUpRight class="size-3.5" aria-hidden="true" />
          </Button>
        </li>
      </ul>
    </section>

    <section v-if="elsewhere.length" aria-labelledby="term-elsewhere" class="space-y-3" data-slot="term-elsewhere">
      <h2 id="term-elsewhere" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ inPath.length ? $t('learning.glossary.elsewhere') : $t('learning.glossary.explained_in') }}</h2>
      <ul class="divide-y divide-border border-y border-border">
        <li v-for="item in elsewhere" :key="item.id" class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 py-2.5">
          <Link :href="item.url" class="inline-flex min-h-11 items-center text-sm font-medium text-foreground underline decoration-border underline-offset-4 hover:decoration-foreground sm:min-h-0">{{ item.title }}</Link>
          <span v-if="item.section" class="text-xs text-muted-foreground">{{ item.section.title }}</span>
        </li>
      </ul>
    </section>

    <EmptyState v-if="!term.items.length" :title="$t('learning.glossary.not_linked')" />

    <GlossaryTermSheet v-if="canUpdate" v-model:open="sheetOpen" :term="term">
      <template v-if="canDelete" #danger-zone>
        <Button type="button" variant="destructive" voice="sentence" data-testid="delete-term" @click="deleteOpen = true">
          {{ $t('learning_library.glossary.delete_confirm') }}
        </Button>
      </template>
    </GlossaryTermSheet>
    <ConfirmDialog
      v-if="canDelete"
      v-model:open="deleteOpen"
      :title="$t('learning_library.glossary.delete_title')"
      :description="$t('learning_library.glossary.delete_description')"
      :confirm-label="$t('learning_library.glossary.delete_confirm')"
      destructive
      @confirm="destroy"
    />
  </OverviewPage>
</template>
