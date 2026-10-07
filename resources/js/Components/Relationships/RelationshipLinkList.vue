<template>
  <OverviewSection variant="home" :title :icon="Link2" :count="links.length" data-slot="relationship-link-list">
    <template v-if="canManage" #actions>
      <SpotlightPopover
        :title="$t('Ryšiai tvarkomi čia')"
        :description="$t('Ryšius tarp institucijų ir jų tipų dabar kuri ir keiti jų kortelėse, skirtuke Ryšiai.')"
        :is-dismissed="spotlight.isDismissed.value"
        @dismiss="spotlight.dismiss()"
      >
        <Button size="sm" variant="outline" voice="sentence" class="u-touch" data-testid="relationship-link-add" @click="openCreate">
          <Plus class="size-4" aria-hidden="true" />
          {{ $t('Pridėti ryšį') }}
        </Button>
      </SpotlightPopover>
    </template>

    <p class="text-xs text-muted-foreground">
      {{ description }}
    </p>
    <ul v-if="links.length" class="divide-y divide-border">
      <li v-for="link in links" :key="link.id" class="flex min-h-11 items-center gap-3 py-2" data-testid="relationship-link-row">
        <component
          :is="link.mutual ? ArrowLeftRight : link.direction === 'outgoing' ? ArrowRight : ArrowLeft"
          class="size-4 shrink-0 text-muted-foreground"
          :aria-label="directionLabel(link)"
        />
        <span class="min-w-0 flex-1">
          <Link :href="otherHref(link)" class="block truncate text-sm font-medium text-foreground hover:underline">
            {{ otherName(link) }}
          </Link>
          <span class="block text-xs text-muted-foreground">
            {{ link.kind_label }} · {{ directionLabel(link) }}<template v-if="link.cross_tenant"> · {{ $t('Centrinis → padaliniai') }}</template>
          </span>
        </span>
        <template v-if="canManage">
          <Button variant="ghost" size="sm" voice="sentence" class="pointer-coarse:h-11" data-testid="relationship-link-edit" @click="openEdit(link)">
            {{ $t('Redaguoti') }}
          </Button>
          <Button variant="ghost" size="sm" voice="sentence" class="pointer-coarse:h-11" data-testid="relationship-link-remove" @click="removeTarget = link">
            {{ $t('Pašalinti') }}
          </Button>
        </template>
      </li>
    </ul>
    <p v-else class="text-sm text-muted-foreground">
      {{ $t('Ryšių nėra.') }}
    </p>
  </OverviewSection>

  <RelationshipLinkSheet
    v-if="canManage"
    v-model:open="sheetOpen"
    :subject
    :record-id
    :record-name
    :link="editing"
    :kinds
    :type-options
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
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowLeft, ArrowLeftRight, ArrowRight, Link2, Plus } from 'lucide-vue-next';

import RelationshipLinkSheet from './RelationshipLinkSheet.vue';
import type { RelationKindOption, RelationshipLinkRow, RelationshipSubject, RelationshipTypeOption } from './types';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { ConfirmDialog, OverviewSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const props = defineProps<{
  subject: RelationshipSubject;
  recordId: string;
  recordName: string;
  links: RelationshipLinkRow[];
  canManage: boolean;
  kinds: RelationKindOption[];
  typeOptions?: RelationshipTypeOption[];
}>();

// The editor moved here from the retired Sistema → Ryšiai section.
const spotlight = useFeatureSpotlight('relationship-links-v1');

const title = computed(() => props.subject === 'institution' ? $t('Tiesioginiai ryšiai') : $t('Tipų ryšiai'));
const description = computed(() => props.subject === 'institution'
  ? $t('Ryšys leidžia vienos institucijos nariams matyti kitos posėdžius ir darbotvarkes.')
  : $t('Tipų ryšys galioja visoms šių tipų institucijoms tame pačiame padalinyje arba tarp Centrinio biuro ir padalinių.'));

const sheetOpen = ref(false);
const editing = ref<RelationshipLinkRow | null>(null);
const removeTarget = ref<RelationshipLinkRow | null>(null);

function otherName(link: RelationshipLinkRow): string {
  return props.subject === 'type' && link.other.id === props.recordId ? $t('To paties tipo institucijos') : link.other.name;
}

function otherHref(link: RelationshipLinkRow): string {
  return props.subject === 'institution' ? route('institutions.show', link.other.id) : route('institutionTypes.show', link.other.id);
}

function directionLabel(link: RelationshipLinkRow): string {
  if (link.mutual) {
    return $t('Mato vieni kitus');
  }

  return link.direction === 'outgoing' ? $t('Ši mato kitą') : $t('Kita mato šią');
}

function openCreate(): void {
  spotlight.dismiss();
  editing.value = null;
  sheetOpen.value = true;
}

function openEdit(link: RelationshipLinkRow): void {
  editing.value = link;
  sheetOpen.value = true;
}

function remove(): void {
  const link = removeTarget.value;

  if (!link) {
    return;
  }

  router.delete(route(props.subject === 'institution' ? 'institutionLinks.destroy' : 'institutionTypeLinks.destroy', link.id), {
    preserveScroll: true,
    onFinish: () => { removeTarget.value = null; },
  });
}
</script>
