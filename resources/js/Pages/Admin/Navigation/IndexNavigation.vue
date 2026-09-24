<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ $t('shell.workspaces.website.title') }} · {{ $t('navigation.title') }}
        </p>
        <h1 class="text-2xl font-bold tracking-tight text-foreground font-heading">
          {{ showDeleted ? $t('trash.showing_deleted_only') : $t('navigation.title') }}
        </h1>
        <p class="text-sm text-muted-foreground">
          {{ showDeleted ? $t('trash.showing_deleted_only_description') : $t('navigation.builder.footer_description') }}
        </p>
      </div>

      <div v-if="shouldShowDeletedToggle" class="flex items-center gap-2">
        <TrashViewToggle
          :show-deleted
          :deleted-count
          @update:show-deleted="handleShowDeletedChange"
        />
      </div>
    </div>

    <!-- Trash Notice -->
    <Alert
      v-if="showDeleted"
      class="flex flex-col gap-3 border-status-warning/20 bg-status-warning/10 text-foreground sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-start gap-2.5">
        <Trash2 class="mt-0.5 size-4 shrink-0 text-status-warning" />
        <div class="space-y-0.5">
          <AlertTitle class="font-medium">
            {{ $t('trash.showing_deleted_only') }}
          </AlertTitle>
          <AlertDescription class="text-sm text-muted-foreground">
            {{ $t('trash.showing_deleted_only_description') }}
          </AlertDescription>
        </div>
      </div>
      <Button
        variant="outline"
        size="sm"
        class="shrink-0 bg-background hover:bg-muted"
        @click="handleShowDeletedChange(false)"
      >
        {{ $t('trash.exit_trash_view') }}
      </Button>
    </Alert>

    <!-- Trash View Content -->
    <template v-if="showDeleted">
      <div v-if="contents.length === 0" class="flex flex-col items-center justify-center border border-dashed py-12">
        <EmptyState
          mode="empty"
          :icon="NavigationIcon"
          :title="$t('trash.no_deleted_records')"
          :description="$t('trash.showing_deleted_only_description')"
        />
      </div>

      <div v-else class="flex flex-col gap-2">
        <div
          v-for="item in contents"
          :key="item.id"
          class="flex flex-col gap-3 border border-border bg-background p-3 transition-colors hover:bg-muted/50 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="min-w-0">
            <div class="font-medium">
              {{ item.name || item.url || `#${item.id}` }}
            </div>
            <div v-if="item.url" class="truncate text-xs text-muted-foreground">
              {{ item.url }}
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-1">
            <Button variant="ghost" size="sm" class="gap-1.5 pointer-coarse:size-11" data-testid="restore-button" @click="handleRestore(item.id)">
              <RotateCcw class="size-4" />
              {{ $t('trash.restore') }}
            </Button>
            <Button
              v-if="canForceDelete"
              variant="ghost"
              size="sm"
              class="gap-1.5 text-destructive hover:text-destructive pointer-coarse:size-11"
              data-testid="force-delete-button"
              @click="openForceDeleteDialog(item)"
            >
              <Trash2 class="size-4" />
              {{ $t('trash.permanently_delete') }}
            </Button>
          </div>
        </div>
      </div>
    </template>

    <!-- Main Navigation Tabs -->
    <Tabs v-else default-value="header">
      <TabsList>
        <TabsTrigger value="header">
          {{ $t('navigation.builder.tab_header') }}
        </TabsTrigger>
        <TabsTrigger value="footer">
          {{ $t('navigation.builder.tab_footer') }}
        </TabsTrigger>
      </TabsList>

      <TabsContent value="header" class="pt-4">
        <NavigationBuilder
          :roots="(contents as AdminNavigationRoot[])"
          :lang="lang ?? 'lt'"
          :translation-summary
          @update:lang="handleLangChange"
        />
      </TabsContent>

      <TabsContent value="footer" class="pt-4">
        <FooterNavigationManager
          :columns="footerColumns"
          :lang="lang ?? 'lt'"
          :max-columns="FOOTER_MAX_COLUMNS"
          @toggle-link-active="handleToggleFooterLinkActive"
          @delete-link="handleDeleteFooterLink"
          @delete-column="handleDeleteFooterColumn"
        />
      </TabsContent>
    </Tabs>

    <ConfirmDangerousActionDialog
      v-model:open="isForceDeleteDialogOpen"
      :title="$t('trash.permanently_delete')"
      :description="$t('trash.permanently_delete_description')"
      :confirmation-text="forceDeleteConfirmationText"
      :confirm-label="$t('trash.permanently_delete')"
      @confirm="handleForceDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { NavigationIcon } from '@/Components/icons';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import ConfirmDangerousActionDialog from '@/Components/ui/data-table/ConfirmDangerousActionDialog.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import TrashViewToggle from '@/Components/Tables/TrashViewToggle.vue';
import FooterNavigationManager from '@/Features/Admin/NavigationBuilder/FooterNavigationManager.vue';
import NavigationBuilder from '@/Features/Admin/NavigationBuilder/NavigationBuilder.vue';
import type { AdminFooterColumn, AdminNavigationLink, AdminNavigationRoot, TranslationSummary } from '@/Features/Admin/NavigationBuilder/types';

// Kept in sync with NavigationService::FOOTER_MAX_COLUMNS — purely a UI cap (hides the
// "add column" button once reached); the server is the actual enforcement.
const FOOTER_MAX_COLUMNS = 4;

interface TrashedNavigationItem {
  id: number;
  name?: string | null;
  url?: string | null;
  parent_id?: number | null;
  order?: number | null;
  deleted_at?: string | null;
  [key: string]: unknown;
}

const props = defineProps<{
  navigation: (AdminNavigationRoot | TrashedNavigationItem)[];
  footerNavigation?: AdminFooterColumn[];
  lang?: 'lt' | 'en';
  showDeleted?: boolean;
  deletedCount?: number;
  translationSummary?: TranslationSummary;
}>();

const itemPendingForceDelete = ref<TrashedNavigationItem | null>(null);
const isForceDeleteDialogOpen = ref(false);
const page = usePage();

// The trash branch renders `navigation` as a flat list; the live branch (NavigationBuilder)
// owns its own local editable buffer internally. This is just a typed pass-through.
const contents = computed(() => props.navigation);

// The footer manager keeps no local buffer (unlike NavigationBuilder, it doesn't drag-reorder),
// so it reads straight from the Inertia prop — each mutation below round-trips through the
// server and Inertia refreshes this prop from the redirect response.
const footerColumns = computed(() => props.footerNavigation ?? []);

const showDeleted = computed(() => props.showDeleted ?? false);
const deletedCount = computed(() => props.deletedCount ?? 0);
const hasDeletedCount = computed(() => deletedCount.value > 0);
const shouldShowDeletedToggle = computed(() => showDeleted.value || hasDeletedCount.value);
const canForceDelete = computed(() => (page.props.auth?.can as { forceDelete?: Record<string, boolean> } | undefined)?.forceDelete?.navigation ?? false);
const forceDeleteConfirmationText = computed(() => itemPendingForceDelete.value?.name?.trim() || itemPendingForceDelete.value?.url?.trim() || String(itemPendingForceDelete.value?.id ?? ''));

const handleLangChange = (newLang: 'lt' | 'en') => {
  router.get(route('navigation.index'), { lang: newLang }, {
    preserveScroll: true,
    preserveState: false,
  });
};

const handleShowDeletedChange = (checked: boolean) => {
  router.get(route('navigation.index'), { showDeleted: checked, lang: props.lang }, {
    preserveScroll: true,
    preserveState: false,
  });
};

const handleRestore = (id: number) => {
  router.patch(route('navigation.restore', id), {}, {
    preserveScroll: true,
  });
};

const openForceDeleteDialog = (item: TrashedNavigationItem) => {
  itemPendingForceDelete.value = item;
  isForceDeleteDialogOpen.value = true;
};

const handleForceDelete = () => {
  if (!itemPendingForceDelete.value) {
    return;
  }

  router.delete(route('navigation.forceDelete', itemPendingForceDelete.value.id), {
    preserveScroll: true,
  });
};

const handleToggleFooterLinkActive = (link: AdminNavigationLink, value: boolean) => {
  router.patch(route('navigation.update', { navigation: link.id }), {
    name: link.name,
    url: link.url,
    parent_id: link.parent_id,
    lang: link.lang,
    is_active: value,
    extra_attributes: link.extra_attributes ?? {},
  }, { preserveScroll: true });
};

const handleDeleteFooterLink = (link: AdminNavigationLink) => {
  router.delete(route('navigation.destroy', { navigation: link.id }), { preserveScroll: true });
};

// Deleting a column deletes its children too — Navigation::booted() cascades a root
// delete over its whole subtree (see app/Models/Navigation.php).
const handleDeleteFooterColumn = (column: AdminFooterColumn) => {
  router.delete(route('navigation.destroy', { navigation: column.id }), { preserveScroll: true });
};
</script>
