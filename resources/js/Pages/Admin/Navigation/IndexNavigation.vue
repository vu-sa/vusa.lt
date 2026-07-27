<template>
  <div>
    <PageContent :title="$t('Navigacija')">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <div v-if="!showDeleted" class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
        <Label>Rodyti redagavimą</Label>
        <Switch :model-value="showAdminEdit" @update:model-value="val => showAdminEdit = val" />
          </div>
          <div class="flex items-center gap-2">
        <Label>Rodyti stulpelių keitimo rodykles</Label>
        <Switch :model-value="showColumnChangeArrows" @update:model-value="val => showColumnChangeArrows = val" />
          </div>
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

      <template v-if="showDeleted">
        <div v-if="contents.length === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12">
          <p class="text-muted-foreground">
            {{ $t('trash.no_deleted_records') }}
          </p>
        </div>

        <div v-else class="flex flex-col gap-2">
          <div
            v-for="item in contents"
            :key="item.id"
            class="flex flex-col gap-3 rounded-lg border bg-background p-3 transition-colors hover:bg-zinc-50 sm:flex-row sm:items-center sm:justify-between dark:hover:bg-zinc-800/50"
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
            </div>
          </div>
        </div>
      </template>

      <template v-else>
        <TransitionGroup ref="el" tag="div">
          <div v-for="item in contents" :key="item.id"
            class="relative grid w-full grid-cols-[24px__1fr] gap-4 border border-zinc-300 p-3 first:rounded-t-lg last:rounded-b-lg dark:border-zinc-700/40 dark:bg-zinc-800/5">
            <Button class="handle" style="height: 100%;" variant="ghost" size="sm">
              <IFluentReOrderDotsVertical24Regular />
            </Button>
            <div>
              <span class="text-xl font-bold">{{ item.name }}
                <Link v-if="showAdminEdit" :href="route('navigation.edit', { navigation: item.id })">
                  <Button size="icon-xs" variant="secondary" class="rounded-full">
                    <Icon icon="fluent:edit-16-regular" width="12" height="12" />
                  </Button>
                </Link>
              </span>
              <MainNavigationMenuContent :item is-used-without-root are-links-disabled :show-edit-icons="showAdminEdit">
                <template #editIconsLink="{ index, link, links }">
                  <OrderEditDeleteButtons v-if="!showColumnChangeArrows" :index :length="links.length"
                    :edit-route="route('navigation.edit', { navigation: link.id })" @delete="handleDelete(link)"
                    @move-up="moveUp(item, link)" @move-down="moveDown(item, link)" />
                  <div v-else>
                    <ButtonGroup>
                      <Button size="icon-xs" variant="ghost" class="rounded-full" @click="changeColumn(link, 'left')">
                        <Icon icon="fluent:arrow-left-16-regular" width="12" height="12" />
                      </Button>
                      <Button size="icon-xs" variant="ghost" class="rounded-full" @click="changeColumn(link, 'right')">
                        <Icon icon="fluent:arrow-right-16-regular" width="12" height="12" />
                      </Button>
                    </ButtonGroup>
                  </div>
                </template>
                <template #editIconsBg="{ index, link, links }">
                  <OrderEditDeleteButtons v-if="!showColumnChangeArrows" :index :length="links.length"
                    :edit-route="route('navigation.edit', { navigation: link.id })" @delete="handleDelete(link)"
                    @move-up="moveUp(item, link)" @move-down="moveDown(item, link)" />

                  <div v-else>
                    <ButtonGroup>
                      <Button size="icon-xs" variant="ghost" class="rounded-full" @click="changeColumn(link, 'left')">
                        <Icon icon="fluent:arrow-left-16-regular" width="12" height="12" />
                      </Button>
                      <Button size="icon-xs" variant="ghost" class="rounded-full" @click="changeColumn(link, 'right')">
                        <Icon icon="fluent:arrow-right-16-regular" width="12" height="12" />
                      </Button>
                    </ButtonGroup>
                  </div>
                </template>
                <template #editIconsDivider="{ index, link, links }">
                  <OrderEditDeleteButtons :index :length="links.length"
                    :edit-route="route('navigation.edit', { navigation: link.id })" @delete="handleDelete(link)"
                    @move-up="moveUp(item, link)" @move-down="moveDown(item, link)" />
                </template>
              </MainNavigationMenuContent>
            </div>
            <div v-if="showAdminEdit" class="col-span-full ml-auto p-2">
              <ButtonGroup class="ml-auto">
                <Button :as="Link" :href="route('navigation.create', { parent_id: item.id })">
                  <Icon icon="fluent:add-16-regular" />
                  Pridėti elementą
                </Button>
                <AlertDialog>
                  <AlertDialogTrigger as-child>
                    <Button variant="destructive">
                      <Icon icon="fluent:delete-16-regular" />
                      Ištrinti
                    </Button>
                  </AlertDialogTrigger>
                  <AlertDialogContent>
                    <AlertDialogHeader>
                      <AlertDialogTitle>Ar tikrai?</AlertDialogTitle>
                      <AlertDialogDescription>Ar tikrai norite ištrinti šį elementą?</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel>Atšaukti</AlertDialogCancel>
                      <AlertDialogAction @click="handleDelete(item)">
                        Patvirtinti
                      </AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>
              </ButtonGroup>
            </div>
          </div>
        </TransitionGroup>
        <div class="mt-4">
          <ButtonGroup>
            <Button :as="Link" :href="route('navigation.create', { parent_id: 0 })">
              <Icon icon="fluent:add-16-regular" />
              Pridėti pagrindinį navigacijos elementą
            </Button>
            <Button variant="secondary" @click="saveOrder()">
              <Icon icon="fluent:save-16-regular" />
              Išsaugoti rikiavimą
            </Button>
          </ButtonGroup>
        </div>
      </template>
    </PageContent>

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
import { cloneDeep } from 'lodash-es';
import { computed, ref } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { RotateCcw, Trash2 } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import TrashViewToggle from '@/Components/Tables/TrashViewToggle.vue';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import MainNavigationMenuContent from '@/Components/Public/Nav/MainNavigationMenuContent.vue';
import OrderEditDeleteButtons from '@/Components/Buttons/OrderEditDeleteButtons.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import ConfirmDangerousActionDialog from '@/Components/ui/data-table/ConfirmDangerousActionDialog.vue';

interface NavigationItem {
  id: number;
  name?: string | null;
  url?: string | null;
  parent_id?: number | null;
  order?: number | null;
  links?: NavigationItem[][];
  cols?: number;
  extra_attributes?: Record<string, unknown> | null;
  column?: number;
  deleted_at?: string | null;
  [key: string]: unknown;
}

const props = defineProps<{
  navigation: NavigationItem[];
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const el = ref<HTMLElement | null>(null);
const contents = ref<NavigationItem[]>(cloneDeep(props.navigation).map(item => ({
  ...item,
  links: Object.values(item.links || {}) as NavigationItem[][],
})));
const itemPendingForceDelete = ref<NavigationItem | null>(null);
const isForceDeleteDialogOpen = ref(false);
const page = usePage();

const showAdminEdit = ref(true);
const showColumnChangeArrows = ref(false);
const showDeleted = computed(() => props.showDeleted ?? false);
const deletedCount = computed(() => props.deletedCount ?? 0);
const hasDeletedCount = computed(() => deletedCount.value > 0);
const shouldShowDeletedToggle = computed(() => showDeleted.value || hasDeletedCount.value);
const canForceDelete = computed(() => (page.props.auth?.can as { forceDelete?: Record<string, boolean> } | undefined)?.forceDelete?.navigation ?? false);
const forceDeleteConfirmationText = computed(() => itemPendingForceDelete.value?.name?.trim() || itemPendingForceDelete.value?.url?.trim() || String(itemPendingForceDelete.value?.id ?? ''));

useSortable(el, contents, {
  handle: '.handle', animation: 100,
});

const saveOrder = () => {
  router.post(route('navigation.updateOrder'), {
    navigation: contents.value.map(item => ({
      id: item.id,
      links: item.links?.map(col => col.map(link => ({ id: link.id }))),
    })),
  });
};

const moveUp = (parent: NavigationItem, link: NavigationItem) => {
  // Find contents array by parent id
  const contentsIndex = contents.value.findIndex(item => item.id === parent.id);

  if (contentsIndex === -1) {
    return;
  }

  const parentLinks = contents.value[contentsIndex].links ?? [];

  let linkArrayIndex = -1;
  // Find links index by iterating through links array
  for (let i = 0; i < parentLinks.length; i++) {
    if (parentLinks[i]?.find(item => item?.id === link?.id)) {
      linkArrayIndex = i;
      break;
    }
  }

  // If linkArray index is not found, return
  if (linkArrayIndex === -1) {
    return;
  }

  // Find index of the link in the links array
  const linkIndex = parentLinks[linkArrayIndex].findIndex(item => item?.id === link?.id);

  // If link index is found and is not the first item, swap the links but not the linkArrays
  if (linkIndex > 0) {
    const temp = parentLinks[linkArrayIndex][linkIndex];
    parentLinks[linkArrayIndex][linkIndex] = parentLinks[linkArrayIndex][linkIndex - 1];
    parentLinks[linkArrayIndex][linkIndex - 1] = temp;
  }
};

const moveDown = (parent: NavigationItem, link: NavigationItem) => {
  // Find contents array by parent id
  const contentsIndex = contents.value.findIndex(item => item.id === parent.id);

  if (contentsIndex === -1) {
    return;
  }

  const parentLinks = contents.value[contentsIndex].links ?? [];

  let linkArrayIndex = -1;
  // Find links index by iterating through links array
  for (let i = 0; i < parentLinks.length; i++) {
    if (parentLinks[i]?.find(item => item?.id === link?.id)) {
      linkArrayIndex = i;
      break;
    }
  }

  // If linkArray index is not found, return
  if (linkArrayIndex === -1) {
    return;
  }

  // Find index of the link in the links array
  const linkIndex = parentLinks[linkArrayIndex].findIndex(item => item?.id === link?.id);

  // If link index is found and is not the last item, swap the links but not the linkArrays
  if (linkIndex !== -1 && linkIndex < parentLinks[linkArrayIndex].length - 1) {
    const temp = parentLinks[linkArrayIndex][linkIndex];
    parentLinks[linkArrayIndex][linkIndex] = parentLinks[linkArrayIndex][linkIndex + 1];
    parentLinks[linkArrayIndex][linkIndex + 1] = temp;
  }
};

const changeColumn = (link: NavigationItem, direction: 'left' | 'right') => {
  router.post(route('navigation.updateColumn'), {
    id: link.id,
    direction,
  }, { preserveScroll: true });
};

const handleDelete = (link: NavigationItem) => {
  router.delete(route('navigation.destroy', link.id));
};

const handleShowDeletedChange = (checked: boolean) => {
  router.get(route('navigation.index'), { showDeleted: checked }, {
    preserveScroll: true,
    preserveState: false,
  });
};

const handleRestore = (id: number) => {
  router.patch(route('navigation.restore', id), {}, {
    preserveScroll: true,
  });
};

const openForceDeleteDialog = (item: NavigationItem) => {
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
</script>
