<template>
  <PageContent :title="$t('Greitosios nuorodos')" :create-url="route('quickLinks.create')">
    <!-- Tenant & Language Controls -->
    <div class="mb-6 flex flex-wrap items-end gap-4">
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

    <!-- Empty State -->
    <div v-if="quickLinkList.length === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12">
      <IFluentLink24Regular class="mb-4 h-10 w-10 text-muted-foreground" />
      <p class="text-muted-foreground">
        {{ $t('Dar nėra greitųjų nuorodų') }}
      </p>
      <Button :as="Link" :href="route('quickLinks.create')" variant="secondary" class="mt-4">
        <IFluentAdd24Regular class="h-4 w-4" />
        {{ $t('Sukurti pirmą nuorodą') }}
      </Button>
    </div>

    <!-- Sortable List -->
    <template v-else>
      <TransitionGroup ref="el" tag="div" class="mb-4 flex flex-col gap-2">
        <div v-for="item in quickLinkList" :key="item.id"
          class="group relative flex items-center gap-3 rounded-lg border bg-background p-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
          <Button variant="ghost" class="handle shrink-0 cursor-grab active:cursor-grabbing" size="icon-sm">
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

          <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
            <Button :as="Link" :href="route('quickLinks.edit', item.id)" variant="ghost" size="icon-sm">
              <IFluentEdit24Regular />
            </Button>

            <Button variant="ghost" size="icon-sm" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
              @click="confirmDelete(() => handleDelete(item.id))">
              <IFluentDelete24Regular />
            </Button>
          </div>
        </div>
      </TransitionGroup>

      <Button variant="secondary" :disabled="!hasChanges" @click="handleOrderUpdate">
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
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';

import DeleteConfirmationDialog from '@/Components/Dialogs/DeleteConfirmationDialog.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { Button } from '@/Components/ui/button';
import { SingleSelect } from '@/Components/ui/single-select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import { useDeleteConfirmation } from '@/Composables/useDeleteConfirmation';

const props = defineProps<{
  quickLinks: App.Entities.QuickLink[];
  tenant: App.Entities.Tenant | null;
  tenants: Array<{ id: number; shortname: string; type: string }>;
  currentLang: string;
}>();

const el = ref<HTMLElement | null>(null);

const quickLinkList = ref(
  props.quickLinks.map(quickLink => ({
    id: quickLink.id,
    text: quickLink.text,
    link: quickLink.link,
    icon: quickLink.icon,
    order: quickLink.order,
  })),
);

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
    router.get(route('quickLinks.index'), { tenant: tenant.id, lang: props.currentLang }, { preserveState: false });
  }
}

function handleLangChange(lang: string) {
  router.get(route('quickLinks.index'), { tenant: props.tenant?.id, lang }, { preserveState: false });
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
