<template>
  <div data-slot="institution-relations">
    <OverviewSection variant="home" :title="$t('Ryšiai')" :icon="Link2" :count="rows.length">
      <template v-if="canManage" #actions>
        <SpotlightPopover
          :title="$t('Ryšiai tvarkomi čia')"
          :description="$t('Ryšius tarp institucijų ir jų tipų dabar kuri ir keiti jų kortelėse, skirtuke Ryšiai.')"
          :is-dismissed="spotlight.isDismissed.value"
          @dismiss="spotlight.dismiss()"
        >
          <SectionHeaderAction :icon="Plus" :label="$t('Pridėti ryšį')" data-testid="relationship-link-add" @click="openCreate" />
        </SpotlightPopover>
      </template>

      <div v-if="rows.length" class="flex flex-col gap-6">
        <section v-for="group in groups" :key="group.key" :data-group="group.key">
          <h3 class="mb-2 text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
            {{ group.label }} · {{ group.rows.length }}
          </h3>
          <CollectionRowList :items="group.rows" :item-key="row => row.id" :row-muted="row => row.authorized === false">
            <template #default="{ item: row }">
              <CollectionRow :actions="rowActions(row)" :class="ROW_PADDING_CLASS" data-testid="relationship-row" @select="key => selectAction(key, row)">
                <template #lead>
                  <EntityTypeMark type="institution" size="md" />
                </template>
                <div class="flex items-center gap-3">
                  <CollectionPrimaryCell :title="row.name ?? '—'" :href="route('institutions.show', row.id)" :sub="subLine(row)" class="flex-1" />
                  <InactiveInstitutionBadge :active="row.is_active" />
                  <Lock v-if="row.authorized === false" class="size-3.5 shrink-0 text-muted-foreground" :aria-label="$t('relationships.not_authorized')" />
                </div>
              </CollectionRow>
            </template>
          </CollectionRowList>
        </section>
      </div>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('Ryšių nėra.') }}
      </p>

      <p class="mt-1 text-xs text-muted-foreground" data-slot="institution-relations-note">
        {{ $t('Ryšys leidžia vienos institucijos nariams matyti kitos posėdžius ir darbotvarkes.') }}
        <a :href="relationsDocsHref" target="_blank" rel="noopener noreferrer" class="underline underline-offset-4 hover:text-foreground">{{ $t('Plačiau') }}</a>
      </p>
    </OverviewSection>

    <RelationshipLinkSheet
      v-if="canManage"
      v-model:open="sheetOpen"
      subject="institution"
      :record-id
      :record-name
      :link="editing"
      :kinds
    />

    <ConfirmDialog
      :open="removeTarget !== null"
      :title="$t('Pašalinti ryšį?')"
      :description="$t('Susietų institucijų nariai gali nebematyti vieni kitų posėdžių.')"
      :confirm-label="$t('Pašalinti')"
      destructive
      @update:open="value => { if (!value) removeTarget = null; }"
      @confirm="remove"
    />
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Link2, Lock, Pencil, Plus, Trash2 } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_PADDING_CLASS } from '@/Components/Collection/rowList';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { ConfirmDialog, OverviewSection, SectionHeaderAction } from '@/Components/Patterns';
import RelationshipLinkSheet from '@/Components/Relationships/RelationshipLinkSheet.vue';
import type { RelationKindOption, RelationshipLinkRow } from '@/Components/Relationships/types';
import { useDocsHref } from '@/Composables/useDocsHref';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';

import InactiveInstitutionBadge from './InactiveInstitutionBadge.vue';

export interface RelatedInstitutionItem {
  id: string | number;
  name: string | null;
  direction?: 'outgoing' | 'incoming' | 'mutual';
  via?: 'direct' | 'type';
  kind_label?: string;
  cross_tenant?: boolean;
  authorized?: boolean;
  is_active?: boolean;
}

type Direction = 'outgoing' | 'incoming' | 'mutual' | 'other';

interface RelationRow {
  id: string;
  name: string | null;
  direction: Direction;
  via: 'direct' | 'type' | undefined;
  kindLabel: string | undefined;
  crossTenant: boolean;
  authorized: boolean | undefined;
  is_active: boolean | undefined;
  /** The direct links behind the row: what an editor can change here. */
  links: RelationshipLinkRow[];
}

/**
 * Every institution this one is related to, in one list: the relations its type brings and the
 * direct links, which are editable from their row. `related` already contains each direct link.
 */
const props = defineProps<{
  related: RelatedInstitutionItem[];
  links: RelationshipLinkRow[];
  recordId: string;
  recordName: string;
  canManage: boolean;
  kinds: RelationKindOption[];
}>();

// The editor moved here from the retired Sistema → Ryšiai section.
const spotlight = useFeatureSpotlight('relationship-links-v1');
const relationsDocsHref = useDocsHref('/sistema/rysiai');

const rows = computed<RelationRow[]>(() => {
  const linksByOther = new Map<string, RelationshipLinkRow[]>();
  for (const link of props.links) {
    linksByOther.set(link.other.id, [...(linksByOther.get(link.other.id) ?? []), link]);
  }

  const fromRelations = props.related.map((item): RelationRow => ({
    id: String(item.id),
    name: item.name,
    direction: item.direction ?? 'other',
    via: item.via,
    kindLabel: item.kind_label,
    crossTenant: Boolean(item.cross_tenant),
    authorized: item.authorized,
    is_active: item.is_active,
    links: linksByOther.get(String(item.id)) ?? [],
  }));

  // A link the relation summary leaves out (e.g. to an institution it skips) must stay removable.
  const shown = new Set(fromRelations.map(row => row.id));
  const orphans = [...linksByOther.entries()]
    .filter(([otherId]) => !shown.has(otherId))
    .map(([otherId, links]): RelationRow => ({
      id: otherId,
      name: links[0]!.other.name,
      direction: links.some(link => link.mutual) ? 'mutual' : links[0]!.direction,
      via: 'direct',
      kindLabel: links[0]!.kind_label,
      crossTenant: false,
      authorized: true,
      is_active: links[0]!.other.is_active,
      links,
    }));

  return [...fromRelations, ...orphans];
});

const ORDER: Direction[] = ['outgoing', 'incoming', 'mutual', 'other'];

/** Who this body oversees, who oversees it, then peers — the question a reader arrives with. */
const groups = computed(() => ORDER
  .map(key => ({ key, label: $t(`relationships.group_${key}`), rows: rows.value.filter(row => row.direction === key) }))
  .filter(group => group.rows.length > 0));

const subLine = (row: RelationRow) => [
  row.kindLabel,
  row.via === 'type' ? $t('Per tipą') : $t('Tiesioginis ryšys'),
  row.crossTenant ? $t('Tarp padalinių') : null,
].filter(Boolean).join(' · ');

const linkLabel = (row: RelationRow, link: RelationshipLinkRow, label: string) =>
  row.links.length > 1 ? `${label}: ${link.kind_label}` : label;

const rowActions = (row: RelationRow): AdminRecordAction[] => props.canManage
  ? row.links.flatMap(link => [
      { key: `edit:${link.id}`, label: linkLabel(row, link, $t('Redaguoti ryšį')), icon: Pencil, role: 'edit' as const, menuOnly: true },
      { key: `remove:${link.id}`, label: linkLabel(row, link, $t('Pašalinti ryšį')), icon: Trash2, destructive: true },
    ])
  : [];

const sheetOpen = ref(false);
const editing = ref<RelationshipLinkRow | null>(null);
const removeTarget = ref<RelationshipLinkRow | null>(null);

function openCreate(): void {
  spotlight.dismiss();
  editing.value = null;
  sheetOpen.value = true;
}

function selectAction(key: string, row: RelationRow): void {
  const [kind, id] = key.split(':');
  const link = row.links.find(candidate => String(candidate.id) === id);
  if (!link) {
    return;
  }

  if (kind === 'edit') {
    editing.value = link;
    sheetOpen.value = true;
  }
  else if (kind === 'remove') {
    removeTarget.value = link;
  }
}

function remove(): void {
  const link = removeTarget.value;
  if (!link) {
    return;
  }

  router.delete(route('institutionLinks.destroy', link.id), {
    preserveScroll: true,
    onFinish: () => { removeTarget.value = null; },
  });
}
</script>
