<template>
  <PageContent title="Pradinio puslapio mygtukų tvarkymas" :back-url="route('quickLinks.index')"
    :heading-icon="Icons.QUICK_LINK">
    <UpsertModelLayout :errors="$page.props.errors" :model="quickLinks">
      <h2>{{ tenant.shortname }} greitosios nuorodos</h2>
      <div ref="el" class="mb-4 flex flex-col gap-1 rounded-xs border p-4 shadow-xs">
        <NButtonGroup v-for="item in quickLinkList" :key="item.id" size="small">
          <NButton secondary class="handle">
            <template #icon>
              <IFluentDrag24Regular />
            </template>
          </NButton>
          <NButton tag="a" :href="route('quickLinks.edit', item.id)" target="_blank" quaternary>
            <strong class="mr-2">{{ item.text }}</strong>
            <span class="text-xs text-zinc-500">{{ item.link }}</span>
          </NButton>
        </NButtonGroup>
      </div>
      <NButton type="primary" @click="handleOrderUpdate">
        Atnaujinti
      </NButton>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useSortable } from "@vueuse/integrations/useSortable";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  quickLinks: App.Entities.QuickLink[];
  tenant: App.Entities.Tenant;
}>();

const el = ref<HTMLElement | null>(null);

const quickLinkList = ref(
  props.quickLinks.map((quickLink) => ({
    id: quickLink.id,
    text: quickLink.text,
    link: quickLink.link,
    order: quickLink.order,
  })),
);

useSortable(el, quickLinkList, { handle: ".handle", animation: 100 });
// const order: Record<"id" | "position", number>[] = [];

const handleOrderUpdate = () => {
  const orderList: Record<"id" | "order", number>[] = [];
  quickLinkList.value.forEach((item, index) => {
    orderList.push({ id: item.id, order: index + 1 });
  });
  router.post(route("quickLinks.update-order"), { orderList });
};
</script>
