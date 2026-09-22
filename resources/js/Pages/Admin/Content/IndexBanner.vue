<template>
  <CollectionPage
    :source
    collection="banners"
    entity-type="banner"
    :eyebrow="isDeleted ? $t('Ištrinti baneriai') : ($t('shell.workspaces.website.title') + ' · ' + $t('Baneriai'))"
    :title="isDeleted ? $t('Ištrinti baneriai') : $t('Baneriai')"
    :lead="isDeleted ? $t('Peržiūrėk ištrintus banerius arba atkurk juos.') : $t('Tvarkyk reklaminius skydelius ir partnerių banerius.')"
    default-view="table"
    :available-views="['table', 'rows']"
    :item-key="bannerKey"
    :columns
    :search-placeholder="$t('Ieškoti banerių…')"
  >
    <template #actions>
      <Button v-if="isDeleted" as-child variant="ghost">
        <Link :href="route('banners.index')">
          ‹ {{ $t('Visi baneriai') }}
        </Link>
      </Button>
      <Button v-else-if="deletedCount > 0" as-child variant="ghost">
        <Link :href="route('banners.index', { showDeleted: 'true' })">
          <Trash2 aria-hidden="true" />
          {{ $t('Ištrinti') }} ({{ deletedCount }})
        </Link>
      </Button>
      <Button v-if="canCreate && !isDeleted" as-child variant="brand">
        <Link :href="route('banners.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas baneris') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-14 items-center justify-between gap-4 px-3 py-2.5 sm:px-4">
        <div class="flex min-w-0 flex-1 items-center gap-3">
          <div v-if="item.image_url" class="size-10 shrink-0 overflow-hidden border border-border bg-muted">
            <img :src="item.image_url" :alt="item.title" class="size-full object-cover">
          </div>
          <div v-else class="flex size-10 shrink-0 items-center justify-center border border-border bg-muted text-muted-foreground">
            <ImageIcon class="size-5" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <Link
                :href="route('banners.edit', item.id)"
                data-collection-open
                class="truncate font-medium hover:text-brand"
              >
                {{ item.title }}
              </Link>
              <span
                v-if="!item.is_active"
                class="border border-border bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
              >
                {{ $t('Neaktyvus') }}
              </span>
            </div>
            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
              <span v-if="item.tenant?.shortname" class="font-medium">{{ item.tenant.shortname }}</span>
              <span v-if="item.tenant?.shortname && item.link_url">·</span>
              <a
                v-if="item.link_url"
                :href="item.link_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex max-w-[16rem] items-center gap-1 truncate hover:underline"
              >
                <span class="truncate">{{ item.link_url }}</span>
                <ExternalLink class="size-3" />
              </a>
            </div>
          </div>
        </div>
        <div class="flex shrink-0 items-center gap-1">
          <Button as-child variant="ghost" size="icon-sm">
            <Link :href="route('banners.edit', item.id)">
              <ChevronRight class="size-4" />
            </Link>
          </Button>
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'title'" class="flex min-w-0 items-center gap-3">
        <div v-if="item.image_url" class="size-8 shrink-0 overflow-hidden border border-border bg-muted">
          <img :src="item.image_url" :alt="item.title" class="size-full object-cover">
        </div>
        <div v-else class="flex size-8 shrink-0 items-center justify-center border border-border bg-muted text-muted-foreground">
          <ImageIcon class="size-4" />
        </div>
        <Link
          :href="route('banners.edit', item.id)"
          data-collection-open
          class="block truncate font-medium hover:text-brand"
        >
          {{ item.title }}
        </Link>
      </div>

      <div v-else-if="column.key === 'link_url'" class="truncate">
        <a
          v-if="item.link_url"
          :href="item.link_url"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex max-w-[18rem] items-center gap-1 text-sm hover:underline"
        >
          <span class="truncate">{{ item.link_url }}</span>
          <ExternalLink class="size-3.5 text-muted-foreground" />
        </a>
        <span v-else class="text-muted-foreground">—</span>
      </div>

      <span v-else-if="column.key === 'tenant'" class="truncate text-xs">
        {{ item.tenant?.shortname ?? '—' }}
      </span>

      <span v-else-if="column.key === 'is_active'">
        <span
          v-if="item.is_active"
          class="border border-[var(--status-success-border)] bg-[var(--status-success-surface)] px-1.5 py-0.5 text-xs font-medium text-[var(--status-success)]"
        >
          {{ $t('Aktyvus') }}
        </span>
        <span
          v-else
          class="border border-border bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
        >
          {{ $t('Neaktyvus') }}
        </span>
      </span>

      <div v-else-if="column.key === 'actions'" class="flex items-center justify-end gap-1">
        <template v-if="isDeleted">
          <Button variant="ghost" size="icon-sm" :title="$t('Atkurti')" @click="restoreBanner(item)">
            <RotateCcw class="size-4" />
          </Button>
          <Button
            v-if="canForceDelete"
            variant="ghost"
            size="icon-sm"
            class="text-destructive hover:text-destructive"
            :title="$t('Ištrinti visam laikui')"
            @click="targetBannerToForceDelete = item"
          >
            <Trash2 class="size-4" />
          </Button>
        </template>
        <template v-else>
          <Button as-child variant="ghost" size="icon-sm" :title="$t('Redaguoti')">
            <Link :href="route('banners.edit', item.id)">
              <Edit class="size-4" />
            </Link>
          </Button>
        </template>
      </div>
    </template>

    <template #empty>
      <EmptyState
        :mode="isFiltered ? 'no-results' : 'empty'"
        :icon="BannerIcon"
        :title="isDeleted ? $t('Ištrintų banerių nėra') : $t('Banerių dar nėra')"
        :description="isDeleted ? $t('Šiukšliadėžėje nėra pašalintų banerių.') : $t('Sukurk pirmąjį banerį, kad jis būtų rodomas svetainėje.')"
        :action-label="canCreate && !isDeleted ? $t('Naujas baneris') : undefined"
        @action="router.visit(route('banners.create'))"
      />
    </template>
  </CollectionPage>

  <ConfirmDialog
    :open="targetBannerToForceDelete !== null"
    :title="$t('Ištrinti banerį visam laikui?')"
    :description="$t('Šis veiksmas negrįžtamas. Baneris bus visiškai pašalintas.')"
    :confirm-label="$t('Ištrinti visam laikui')"
    destructive
    @update:open="!$event && (targetBannerToForceDelete = null)"
    @confirm="forceDeleteBanner"
  />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Edit, ExternalLink, Image as ImageIcon, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { BannerIcon } from '@/Components/icons';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';

const entityName = 'banner';

type BannerRow = App.Entities.Banner;

const props = defineProps<{
  banners: {
    data: BannerRow[];
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
}>();

const isDeleted = computed(() => Boolean(props.showDeleted));
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.banner ?? true));
const canForceDelete = computed(() => Boolean(usePage().props.auth?.can?.forceDelete?.banner));

const targetBannerToForceDelete = ref<BannerRow | null>(null);

const bannerKey = (item: BannerRow) => String(item.id);

const source = useDatabaseCollectionSource<BannerRow>({
  endpoint: route('api.v1.admin.banners.index'),
  initial: {
    items: props.banners.data,
    total: props.banners.meta.total,
    perPage: props.banners.meta.per_page,
    currentPage: props.banners.meta.current_page,
    lastPage: props.banners.meta.last_page,
  },
  defaultSort: 'title:asc',
  sortOptions: [
    { value: 'title:asc', label: $t('Pagal pavadinimą (A–Z)') },
    { value: 'title:desc', label: $t('Pagal pavadinimą (Z–A)') },
  ],
  preserveUrlKeys: ['showDeleted'],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('Pavadinimas') },
  { key: 'link_url', label: $t('Nuoroda'), class: 'w-64' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'is_active', label: $t('Būsena'), class: 'w-28' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-24 text-right' },
]);

const isFiltered = computed(() => source.query.value.trim() !== '' || source.activeFilterCount.value > 0);

function restoreBanner(item: BannerRow): void {
  router.patch(route('banners.restore', item.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Baneris atkurtas.'));
    },
    onError: () => {
      toast.error($t('Nepavyko atkurti banerio.'));
    },
  });
}

function forceDeleteBanner(): void {
  if (!targetBannerToForceDelete.value) return;
  const { id } = targetBannerToForceDelete.value;
  targetBannerToForceDelete.value = null;

  router.delete(route('banners.forceDelete', id), {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
      toast.success($t('Baneris ištrintas visam laikui.'));
    },
    onError: () => {
      toast.error($t('Nepavyko ištrinti banerio.'));
    },
  });
}
</script>
