<template>
  <SidebarGroup>
    <SidebarGroupLabel>{{ sectionTitle }}</SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in items" :key="item.title" :data-tour="item.dataTour">
          <SpotlightPopover
            v-if="item.spotlight"
            :title="item.spotlight.title"
            :description="item.spotlight.description"
            :is-dismissed="item.spotlight.isDismissed"
            position="right"
            float
            style="display: block; width: 100%;"
            @dismiss="item.spotlight.dismiss"
          >
            <SidebarMenuButton as-child :is-active="item.isActive" @click="handleItemClick(item.url)">
              <Link v-if="item.internal" :href="item.url" prefetch class="flex items-center" @click="item.spotlight.dismiss()">
                <component :is="item.icon" />
                <span>{{ item.title }}</span>
              </Link>
            </SidebarMenuButton>
          </SpotlightPopover>
          <SidebarMenuButton v-else as-child :is-active="item.isActive" @click="handleItemClick(item.url)">
            <Link v-if="item.internal" :href="item.url" prefetch class="flex items-center">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
            <a v-else :href="item.url" :target="item.url.startsWith('#') ? undefined : '_blank'" rel="noopener noreferrer" class="flex items-center">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </a>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>

<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/ui/sidebar';

const props = defineProps<{
  items: {
    title: string;
    url: string;
    icon: LucideIcon;
    dataTour?: string;
    internal?: boolean;
    isActive?: boolean;
    spotlight?: {
      title: string;
      description: string;
      isDismissed: boolean;
      dismiss: () => void;
    };
  }[];
}>();

const emit = defineEmits<(e: 'itemClick', url: string) => void>();

const sectionTitle = $t('Pagalba');

const handleItemClick = (url: string) => {
  emit('itemClick', url);
};
</script>
