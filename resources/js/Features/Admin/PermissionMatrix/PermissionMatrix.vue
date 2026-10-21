<template>
  <div class="space-y-5" data-slot="permission-matrix">
    <div class="flex flex-wrap items-center gap-2">
      <div class="relative min-w-56 flex-1 sm:max-w-xs">
        <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
        <Input
          v-model="search"
          type="search"
          :placeholder="$t('responsibilities.matrix.search')"
          :aria-label="$t('responsibilities.matrix.search')"
          class="pl-9"
          data-testid="matrix-search"
        />
      </div>
      <button
        type="button"
        :class="controlVariants({ active: onlyGranted, voice: 'sentence' })"
        :aria-pressed="onlyGranted"
        data-testid="matrix-only-granted"
        @click="onlyGranted = !onlyGranted"
      >
        {{ $t('responsibilities.matrix.only_granted') }}
        <span :class="controlCountClass">{{ grantedResourceCount }}</span>
      </button>
      <button
        v-for="area in catalog.areas"
        v-show="areaHasRows(area)"
        :key="area"
        type="button"
        :class="controlVariants({ active: activeArea === area, voice: 'sentence' })"
        :aria-pressed="activeArea === area"
        @click="activeArea = activeArea === area ? null : area"
      >
        {{ $t(`shell.workspaces.${area}.title`) }}
      </button>
    </div>

    <p v-if="!visibleAreas.length" class="py-6 text-sm text-muted-foreground">
      {{ $t('responsibilities.matrix.nothing_found') }}
    </p>

    <!-- Desktop: one grid, abilities as columns. -->
    <table v-if="visibleAreas.length" class="hidden w-full border-collapse text-sm xl:table" data-testid="matrix-table">
      <thead class="sticky top-[var(--shell-chrome-height,0px)] z-20 bg-background">
        <tr class="border-b border-border">
          <th scope="col" class="w-[34%] py-2 pr-4 text-left text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
            {{ $t('responsibilities.matrix.resource') }}
          </th>
          <th
            v-for="ability in ABILITIES"
            :key="ability"
            scope="col"
            class="px-1 py-2 text-left text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
          >
            {{ abilityLabel(ability) }}
          </th>
        </tr>
      </thead>
      <tbody v-for="area in visibleAreas" :key="area.key">
        <tr>
          <th :colspan="ABILITIES.length + 1" scope="rowgroup" class="pt-6 pb-1 text-left text-[11px] font-bold tracking-wide text-brand uppercase">
            {{ $t(`shell.workspaces.${area.key}.title`) }}
          </th>
        </tr>
        <tr v-for="resource in area.resources" :key="resource.key" class="border-b border-border/70 align-top" :data-resource="resource.key">
          <th scope="row" class="py-2.5 pr-4 text-left font-normal">
            <span class="flex items-center gap-2 font-medium text-foreground">
              <component :is="resourceIcon(resource.key, resource.model)" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
              {{ resourceLabel(resource.key, resource.model) }}
            </span>
            <span v-if="resource.baseline" class="mt-0.5 block text-xs text-muted-foreground">
              <Lock class="mr-1 inline size-3 align-[-1px]" aria-hidden="true" />
              <template v-if="resource.baselineSegments?.length">
                <template v-for="(segment, index) in resource.baselineSegments" :key="index">
                  <strong v-if="segment.strong" class="font-semibold text-foreground">{{ segment.text }}</strong><template v-else>{{ segment.text }}</template>
                </template>
              </template>
              <template v-else>{{ resource.baseline }}</template>
            </span>
          </th>
          <td v-for="ability in ABILITIES" :key="ability" class="px-1 py-2">
            <PermissionCell
              :resource
              :ability
              :value="draft.scopeOf(resource.key, ability)"
              :changed="isChanged(resource.key, ability)"
              :retired="retiredScopes(resource.key, ability)"
              :readonly
              @update="scope => draft.setScope(resource.key, ability, scope)"
            />
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Phone and narrow tablet: one block per resource, abilities as rows. -->
    <div v-if="visibleAreas.length" class="space-y-8 xl:hidden">
      <section v-for="area in visibleAreas" :key="area.key" class="space-y-4">
        <h3 class="text-[11px] font-bold tracking-wide text-brand uppercase">
          {{ $t(`shell.workspaces.${area.key}.title`) }}
        </h3>
        <div v-for="resource in area.resources" :key="resource.key" class="border-t border-border pt-3">
          <p class="flex items-center gap-2 text-sm font-medium text-foreground">
            <component :is="resourceIcon(resource.key, resource.model)" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            {{ resourceLabel(resource.key, resource.model) }}
          </p>
          <p v-if="resource.baseline" class="mt-0.5 text-xs text-muted-foreground">
            <template v-if="resource.baselineSegments?.length">
              <template v-for="(segment, index) in resource.baselineSegments" :key="index">
                <strong v-if="segment.strong" class="font-semibold text-foreground">{{ segment.text }}</strong>
                <template v-else>
                  {{ segment.text }}
                </template>
              </template>
            </template>
            <template v-else>
              {{ resource.baseline }}
            </template>
          </p>
          <dl class="mt-2 divide-y divide-border/60">
            <div v-for="ability in ABILITIES" :key="ability" class="grid min-h-11 gap-2 py-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
              <dt class="text-sm text-muted-foreground sm:pt-2">
                {{ abilityLabel(ability, resource.softDeletes) }}
              </dt>
              <dd class="min-w-0">
                <PermissionCell
                  :resource
                  :ability
                  :value="draft.scopeOf(resource.key, ability)"
                  :changed="isChanged(resource.key, ability)"
                  :retired="retiredScopes(resource.key, ability)"
                  :readonly
                  @update="scope => draft.setScope(resource.key, ability, scope)"
                />
              </dd>
            </div>
          </dl>
        </div>
      </section>
    </div>

    <div
      v-if="!readonly && draft.isDirty.value"
      class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t border-border bg-background/95 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0"
      data-testid="matrix-save-bar"
    >
      <p class="min-w-0 flex-1 text-sm">
        <span class="font-medium text-foreground">{{ changeSummary }}</span>
        <span class="block truncate text-xs text-muted-foreground" :title="changeDetail">{{ changeDetail }}</span>
      </p>
      <Button variant="outline" voice="sentence" :disabled="processing" @click="draft.reset()">
        {{ $t('responsibilities.matrix.discard') }}
      </Button>
      <Button variant="brand" :disabled="processing" data-testid="matrix-save" @click="emit('save', draft.names.value)">
        {{ $t('Išsaugoti') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, toRef } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Lock, Search } from '@lucide/vue';

import PermissionCell from './PermissionCell.vue';
import { ABILITIES, abilityLabel, permissionLabel, resourceIcon, resourceLabel } from './permissionLabels';
import type { CatalogResource, PermissionAbility, PermissionCatalog, PermissionScope } from './types';
import { usePermissionDraft } from './usePermissionDraft';

import { Button } from '@/Components/ui/button';
import { controlCountClass, controlVariants } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';

const props = defineProps<{
  catalog: PermissionCatalog;
  /** Catalog permissions the responsibility holds now. */
  granted: string[];
  readonly?: boolean;
  processing?: boolean;
}>();

const emit = defineEmits<{ save: [permissions: string[]] }>();

const draft = usePermissionDraft(toRef(props, 'granted'));
const search = ref('');
const onlyGranted = ref(props.granted.length > 0 && props.readonly);
const activeArea = ref<string | null>(null);

const holdsAny = (resource: CatalogResource) => draft.names.value.some(name => name.startsWith(`${resource.key}.`));

const grantedResourceCount = computed(() => props.catalog.resources.filter(holdsAny).length);

const matches = (resource: CatalogResource) => {
  const query = search.value.trim().toLocaleLowerCase();

  return (!query || resourceLabel(resource.key, resource.model).toLocaleLowerCase().includes(query) || resource.key.toLowerCase().includes(query))
    && (!onlyGranted.value || holdsAny(resource));
};

const areaHasRows = (area: string) => props.catalog.resources.some(resource => resource.area === area);

const visibleAreas = computed(() => props.catalog.areas
  .filter(area => !activeArea.value || activeArea.value === area)
  .map(area => ({ key: area, resources: props.catalog.resources.filter(resource => resource.area === area && matches(resource)) }))
  .filter(area => area.resources.length > 0));

function retiredScopes(resource: string, ability: PermissionAbility): PermissionScope[] {
  const prefix = `${resource}.${ability}.`;

  return props.catalog.retired.filter(name => name.startsWith(prefix)).map(name => name.slice(prefix.length) as PermissionScope);
}

const isChanged = (resource: string, ability: PermissionAbility) => [...draft.added.value, ...draft.removed.value]
  .some(name => name.startsWith(`${resource}.${ability}.`));

const changeSummary = computed(() => $tChoice(
  'responsibilities.matrix.changes',
  draft.added.value.length + draft.removed.value.length,
  { added: draft.added.value.length, removed: draft.removed.value.length },
));

const changeDetail = computed(() => [
  ...draft.added.value.map(name => `+ ${permissionLabel(name)}`),
  ...draft.removed.value.map(name => `− ${permissionLabel(name)}`),
].join(', '));

defineExpose({ draft });
</script>
