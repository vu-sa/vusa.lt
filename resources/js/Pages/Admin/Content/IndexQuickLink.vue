<template>
  <div>
    <PageContent :title="$t('Greitosios nuorodos')" :create-url="showDeleted ? undefined : route('quickLinks.create')">
    <!-- Tenant & Language Controls -->
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div class="flex flex-wrap items-end gap-4">
      <FormFieldWrapper v-if="tenantOptions.length > 1" id="tenant" :label="$t('Padalinys')"
        class="min-w-[16rem]">
        <SingleSelect v-model="selectedTenant" :options="tenantOptions" value-field="id" label-field="shortname"
          :placeholder="$t('Pasirinkti padalinį...')" @update:model-value="handleTenantChange" />
      </FormFieldWrapper>

      <FormFieldWrapper id="lang" :label="$t('Kalba')">
        <ToggleGroup :model-value="currentLang" type="single" class="justify-start"
          @update:model-value="handleLangChange">
          <ToggleGroupItem value="lt" class="gap-2">
            <img src="https://hatscripts.github.io/circle-flags/flags/lt.svg" class="h-4 w-4 rounded-full">
            Lietuvių
          </ToggleGroupItem>
          <ToggleGroupItem value="en" class="gap-2">
            <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" class="h-4 w-4 rounded-full">
            English
          </ToggleGroupItem>
        </ToggleGroup>
      </FormFieldWrapper>
      </div>

      <TrashViewToggle
        v-if="shouldShowDeletedToggle"
        :show-deleted
        :deleted-count
        @update:show-deleted="handleShowDeletedChange"
      />
    </div>

    <Alert
      v-if="showDeleted"
      class="mb-4 flex flex-col gap-3 border-amber-200 bg-amber-50 text-amber-950 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100"
    >
      <div class="flex items-start gap-2.5">
        <Trash2 class="mt-0.5 size-4 shrink-0" />
        <div class="space-y-0.5">
          <AlertTitle class="font-medium">
            {{ $t('trash.showing_deleted_only') }}
          </AlertTitle>
          <AlertDescription class="text-sm text-amber-900 dark:text-amber-100">
            {{ $t('trash.showing_deleted_only_description') }}
          </AlertDescription>
        </div>
      </div>
      <Button
        variant="outline"
        size="sm"
        class="shrink-0 border-amber-300 bg-white text-amber-950 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-100 dark:hover:bg-amber-900/40"
        @click="handleShowDeletedChange(false)"
      >
        {{ $t('trash.exit_trash_view') }}
      </Button>
    </Alert>

    <!-- Empty State -->
    <div v-if="quickLinkList.length === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12">
      <IFluentLink24Regular class="mb-4 h-10 w-10 text-muted-foreground" />
      <p class="text-muted-foreground">
        {{ showDeleted ? $t('trash.no_deleted_records') : $t('Dar nėra greitųjų nuorodų') }}
      </p>
      <Button v-if="!showDeleted" :as="Link" :href="route('quickLinks.create')" variant="secondary" class="mt-4" data-testid="empty-create-button">
        <IFluentAdd24Regular class="h-4 w-4" />
        {{ $t('Sukurti pirmą nuorodą') }}
      </Button>
    </div>

    <!-- Sortable List -->
    <template v-else>
      <div v-if="!showDeleted" class="mb-4 flex items-center justify-end">
        <Button :as="Link" :href="route('quickLinks.create')" variant="secondary" data-testid="inline-create-button">
          <IFluentAdd24Regular class="h-4 w-4" />
          {{ $t('forms.add') }}
        </Button>
      </div>

      <TransitionGroup ref="el" tag="div" class="mb-4 flex flex-col gap-2">
        <div v-for="item in quickLinkList" :key="item.id"
          class="group relative flex items-center gap-3 rounded-lg border bg-background p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
          <Button v-if="!showDeleted" variant="ghost" class="handle shrink-0 cursor-grab active:cursor-grabbing" size="icon-sm">
            <IFluentReOrderDotsVertical24Regular />
          </Button>

          <Icon v-if="item.icon" :icon="`fluent:${item.icon}`" class="size-5 shrink-0 text-muted-foreground" />
          <IFluentLink24Regular v-else class="size-5 shrink-0 text-muted-foreground" />

          <div class="min-w-0 flex-1">
            <div class="font-medium">
              {{ item.text }}
            </div>
            <div class="truncate text-xs text-muted-foreground">
              {{ item.link }}
            </div>
          </div>

          <div class="flex items-center gap-1" :class="showDeleted ? '' : 'opacity-0 transition-opacity group-hover:opacity-100'">
            <Button v-if="!showDeleted" :as="Link" :href="route('quickLinks.edit', item.id)" variant="ghost" size="icon-sm">
              <IFluentEdit24Regular />
            </Button>

            <Button v-if="!showDeleted" variant="ghost" size="icon-sm" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
              @click="confirmDelete(() => handleDelete(item.id))">
              <IFluentDelete24Regular />
            </Button>
            <template v-else>
              <Button variant="ghost" size="sm" class="gap-1.5" data-testid="restore-button" @click="handleRestore(item.id)">
                <RotateCcw class="size-4" />
                {{ $t('trash.restore') }}
              </Button>
              <Button
                v-if="canForceDelete"
                variant="ghost"
                size="sm"
                class="gap-1.5 text-destructive hover:text-destructive"
                data-testid="force-delete-button"
                @click="openForceDeleteDialog(item)"
              >
                <Trash2 class="size-4" />
                {{ $t('trash.permanently_delete') }}
              </Button>
            </template>
          </div>
        </div>
      </TransitionGroup>

      <Button v-if="!showDeleted" variant="secondary" :disabled="!hasChanges" @click="handleOrderUpdate">
        <IFluentSave24Regular class="h-4 w-4" />
        {{ $t('Išsaugoti tvarką') }}
      </Button>
    </template>
    </PageContent>

    <DeleteConfirmationDialog
      v-model:is-open="isOpen"
      :title="deleteTitle"
      :message="deleteMessage"
      :is-deleting
      @confirm="executeDelete"
      @cancel="cancelDelete"
    />

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
import { Icon } from '@iconify/vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { RotateCcw, Trash2 } from 'lucide-vue-next';

import DeleteConfirmationDialog from '@/Components/Dialogs/DeleteConfirmationDialog.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { Button } from '@/Components/ui/button';
import { SingleSelect } from '@/Components/ui/single-select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import TrashViewToggle from '@/Components/Tables/TrashViewToggle.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import { useDeleteConfirmation } from '@/Composables/useDeleteConfirmation';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import ConfirmDangerousActionDialog from '@/Components/ui/data-table/ConfirmDangerousActionDialog.vue';

interface QuickLinkListItem {
  id: number;
  text: string;
  link: string;
  icon?: string | null;
  order?: number | null;
}

const props = defineProps<{
  quickLinks: App.Entities.QuickLink[];
  tenant: App.Entities.Tenant | null;
  tenants: Array<{ id: number; shortname: string; type: string }>;
  currentLang: string;
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const el = ref<HTMLElement | null>(null);
const itemPendingForceDelete = ref<QuickLinkListItem | null>(null);
const isForceDeleteDialogOpen = ref(false);
const page = usePage();

const quickLinkList = ref<QuickLinkListItem[]>(
  props.quickLinks.map(quickLink => ({
    id: quickLink.id,
    text: quickLink.text,
    link: quickLink.link,
    icon: quickLink.icon,
    order: quickLink.order,
  })),
);

const showDeleted = computed(() => props.showDeleted ?? false);
const deletedCount = computed(() => props.deletedCount ?? 0);
const hasDeletedCount = computed(() => deletedCount.value > 0);
const shouldShowDeletedToggle = computed(() => showDeleted.value || hasDeletedCount.value);
const canForceDelete = computed(() => (page.props.auth?.can as { forceDelete?: Record<string, boolean> } | undefined)?.forceDelete?.quickLink ?? false);
const forceDeleteConfirmationText = computed(() => itemPendingForceDelete.value?.text?.trim() || String(itemPendingForceDelete.value?.id ?? ''));

const initialOrder = props.quickLinks.map(q => q.id);

const hasChanges = computed(() => {
  const currentOrder = quickLinkList.value.map(q => q.id);
  return JSON.stringify(currentOrder) !== JSON.stringify(initialOrder);
});

useSortable(el, quickLinkList, {
  handle: '.handle',
  forceFallback: true,
  animation: 150,
});

watch(() => quickLinkList.value, () => {
  quickLinkList.value.forEach((item, index) => {
    item.order = index + 1;
  });
}, { deep: true });

const tenantOptions = computed(() => props.tenants);

const selectedTenant = computed({
  get: () => props.tenant ? tenantOptions.value.find(t => t.id === props.tenant.id) ?? null : null,
  set: () => {},
});

function handleTenantChange(tenant: { id: number; shortname: string; type: string } | null) {
  if (tenant) {
    router.get(route('quickLinks.index'), { tenant: tenant.id, lang: props.currentLang, showDeleted: showDeleted.value }, { preserveState: false });
  }
}

function handleLangChange(lang: string) {
  router.get(route('quickLinks.index'), { tenant: props.tenant?.id, lang, showDeleted: showDeleted.value }, { preserveState: false });
}

function handleShowDeletedChange(checked: boolean) {
  router.get(route('quickLinks.index'), { tenant: props.tenant?.id, lang: props.currentLang, showDeleted: checked }, {
    preserveScroll: true,
    preserveState: false,
  });
}

function handleOrderUpdate() {
  const orderList = quickLinkList.value.map((item, index) => ({
    id: item.id,
    order: index + 1,
  }));

  router.post(route('quickLinks.update-order'), {
    orderList,
    tenant_id: props.tenant?.id,
    lang: props.currentLang,
  });
}

function handleDelete(id: number) {
  router.delete(route('quickLinks.destroy', id), {
    preserveScroll: true,
    preserveState: true,
  });
}

function handleRestore(id: number) {
  router.patch(route('quickLinks.restore', id), {}, {
    preserveScroll: true,
  });
}

function openForceDeleteDialog(item: QuickLinkListItem) {
  itemPendingForceDelete.value = item;
  isForceDeleteDialogOpen.value = true;
}

function handleForceDelete() {
  if (!itemPendingForceDelete.value) {
    return;
  }

  router.delete(route('quickLinks.forceDelete', itemPendingForceDelete.value.id), {
    preserveScroll: true,
  });
}

const {
  isOpen,
  isDeleting,
  title: deleteTitle,
  message: deleteMessage,
  confirmDelete,
  executeDelete,
  cancelDelete,
} = useDeleteConfirmation({
  title: 'Ištrinti greitąją nuorodą?',
  message: 'Ar tikrai norite ištrinti šią greitąją nuorodą? Šis veiksmas neatšaukiamas.',
  preserveScroll: true,
  preserveState: true,
});
</script>
