<template>
  <CollectionPage
    :source
    collection="forms"
    entity-type="form"
    :eyebrow="$t('shell.workspaces.organizacija.title') + ' · ' + $t('Formos')"
    :title="$t('Formos')"
    :lead="$t('Kurk ir valdyk narių registracijas, apklausas ir grįžtamojo ryšio formas.')"
    default-view="table"
    :available-views="['table', 'rows']"
    :item-key="formKey"
    :trash="{ count: deletedCount ?? 0, active: isDeleted }"
    :columns
    :search-placeholder="$t('Ieškoti formų…')"
  >
    <template #actions>
      <Button v-if="can.create && !isDeleted" as-child variant="brand" size="lg">
        <Link :href="route('forms.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja forma') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-14 items-center justify-between gap-4 px-3 py-2.5 sm:px-4">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <Link
              :href="route('forms.show', item.id)"
              data-collection-open
              class="truncate font-medium hover:text-brand"
            >
              {{ localizedName(item) }}
            </Link>
            <span v-if="localizedPath(item)" class="bg-muted px-1.5 py-0.5 font-mono text-xs text-muted-foreground">
              /{{ localizedPath(item) }}
            </span>
          </div>
          <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span v-if="item.tenant?.shortname" class="font-medium">{{ item.tenant.shortname }}</span>
            <span v-if="item.tenant?.shortname">·</span>
            <span>{{ $t('Registracijos') }}: {{ item.registrations_count ?? 0 }}</span>
            <span>·</span>
            <span>{{ $t('Atnaujinta') }}: {{ formatDate(new Date(item.updated_at)) }}</span>
          </div>
        </div>
        <div class="flex shrink-0 items-center gap-1">
          <Button as-child variant="outline" size="icon">
            <Link :href="route('forms.show', item.id)">
              <ChevronRight class="size-4" />
            </Link>
          </Button>
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'name'" class="min-w-0">
        <Link
          :href="route('forms.show', item.id)"
          data-collection-open
          class="block truncate font-medium hover:text-brand"
        >
          {{ localizedName(item) }}
        </Link>
      </div>

      <div v-else-if="column.key === 'path'" class="truncate">
        <span v-if="localizedPath(item)" class="bg-muted px-1.5 py-0.5 font-mono text-xs text-muted-foreground">
          /{{ localizedPath(item) }}
        </span>
        <span v-else class="text-muted-foreground">—</span>
      </div>

      <span v-else-if="column.key === 'tenant'" class="truncate text-xs">
        {{ item.tenant?.shortname ?? '—' }}
      </span>

      <span v-else-if="column.key === 'registrations_count'" class="tabular-nums">
        {{ item.registrations_count ?? 0 }}
      </span>

      <span v-else-if="column.key === 'updated_at'" class="tabular-nums text-muted-foreground">
        {{ formatDate(new Date(item.updated_at)) }}
      </span>

      <div v-else-if="column.key === 'actions'" class="flex items-center justify-end gap-1">
        <template v-if="isDeleted">
          <Button variant="outline" size="icon" :title="$t('Atkurti')" @click="restoreForm(item)">
            <RotateCcw class="size-4" />
          </Button>
          <Button
            v-if="canForceDelete"
            variant="outline"
            size="icon"
            class="text-destructive hover:text-destructive"
            :title="$t('Ištrinti visam laikui')"
            @click="targetFormToForceDelete = item"
          >
            <Trash2 class="size-4" />
          </Button>
        </template>
        <template v-else>
          <Button v-if="item.can?.update" as-child variant="outline" size="icon" :title="$t('Redaguoti')">
            <Link :href="route('forms.edit', item.id)">
              <Edit class="size-4" />
            </Link>
          </Button>
          <Button as-child variant="outline" size="icon" :title="$t('Atidaryti')">
            <Link :href="route('forms.show', item.id)">
              <ChevronRight class="size-4" />
            </Link>
          </Button>
        </template>
      </div>
    </template>

    <template #empty>
      <EmptyState
        :mode="isFiltered ? 'no-results' : 'empty'"
        :icon="FormIcon"
        :title="isDeleted ? $t('Ištrintų formų nėra') : $t('Formų dar nėra')"
        :description="isDeleted ? $t('Šiukšliadėžėje nėra pašalintų formų.') : $t('Kurk naujas registracijos formas studentams ir nariams.')"
        :action-label="can.create && !isDeleted ? $t('Nauja forma') : undefined"
        @action="router.visit(route('forms.create'))"
      />
    </template>
  </CollectionPage>

  <ConfirmDialog
    :open="targetFormToForceDelete !== null"
    :title="$t('Ištrinti formą visam laikui?')"
    :description="$t('Šis veiksmas negrįžtamas. Forma ir visi jos atsakymai bus visiškai pašalinti.')"
    :confirm-label="$t('Ištrinti visam laikui')"
    destructive
    @update:open="!$event && (targetFormToForceDelete = null)"
    @confirm="forceDeleteForm"
  />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ChevronRight, Edit, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { FormIcon } from '@/Components/icons';
import { useDatabaseCollectionSource, type DatabaseFacetDefinition } from '@/Composables/useCollectionSource';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { formatDate } from '@/Utils/dateTime';

type FormRow = App.Entities.Form & {
  can?: {
    view?: boolean;
    update?: boolean;
    delete?: boolean;
  };
};

const props = defineProps<{
  forms: {
    data: FormRow[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  filters?: Record<string, unknown>;
  sorting?: { id: string; desc: boolean }[];
  showDeleted?: boolean;
  deletedCount?: number;
  can: {
    create: boolean;
  };
}>();

const isDeleted = computed(() => Boolean(props.showDeleted));
const canForceDelete = computed(() => Boolean(usePage().props.auth?.can?.forceDelete?.form));

const targetFormToForceDelete = ref<FormRow | null>(null);

const formKey = (item: FormRow) => String(item.id);

const localizedName = (form: FormRow): string => {
  if (!form.name) return '';
  if (typeof form.name === 'object') {
    const locale = getActiveLanguage() as 'lt' | 'en';
    return getTranslatedValue(form.name as unknown as Record<string, string>, locale);
  }
  return String(form.name);
};

const localizedPath = (form: FormRow): string => {
  if (!form.path) return '';
  if (typeof form.path === 'object') {
    const locale = getActiveLanguage() as 'lt' | 'en';
    return getTranslatedValue(form.path as unknown as Record<string, string>, locale);
  }
  return String(form.path);
};

const tenants = computed(() => usePage().props.tenants || []);

const facets = computed<DatabaseFacetDefinition[]>(() => [
  ...(tenants.value.length > 0
    ? [{
        field: 'tenant.id',
        label: $tChoice('entities.tenant.model', 1),
        values: tenants.value.map(tenant => ({ value: String(tenant.id), label: $t(tenant.shortname) })),
      }]
    : []),
]);

const source = useDatabaseCollectionSource<FormRow>({
  endpoint: route('api.v1.admin.forms.index'),
  initial: {
    items: props.forms.data,
    total: props.forms.meta.total,
    perPage: props.forms.meta.per_page,
    currentPage: props.forms.meta.current_page,
    lastPage: props.forms.meta.last_page,
  },
  defaultSort: 'updated_at:desc',
  sortOptions: [
    { value: 'updated_at:desc', label: $t('Naujausios atnaujintos') },
    { value: 'created_at:desc', label: $t('Naujausios sukurtos') },
  ],
  preserveUrlKeys: ['showDeleted'],
  facets: facets.value,
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('forms.fields.name') },
  { key: 'path', label: $t('forms.fields.link'), class: 'w-48' },
  { key: 'tenant', label: $tChoice('entities.tenant.model', 1), class: 'w-28' },
  { key: 'registrations_count', label: $t('Registracijos'), class: 'w-32' },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-24 text-right' },
]);

const isFiltered = computed(() => source.query.value.trim() !== '' || source.activeFilterCount.value > 0);

function restoreForm(item: FormRow): void {
  router.patch(route('forms.restore', item.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Forma atkurta.'));
    },
    onError: () => {
      toast.error($t('Nepavyko atkurti formos.'));
    },
  });
}

function forceDeleteForm(): void {
  if (!targetFormToForceDelete.value) return;
  const { id } = targetFormToForceDelete.value;
  targetFormToForceDelete.value = null;

  router.delete(route('forms.forceDelete', id), {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Forma ištrinta visam laikui.'));
    },
    onError: () => {
      toast.error($t('Nepavyko ištrinti formos.'));
    },
  });
}
</script>
