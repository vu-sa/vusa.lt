<template>
  <SidebarGroup>
    <SidebarGroupLabel>{{ sectionTitle }}</SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in items" :key="item.title" :data-tour="item.dataTour">
          <SidebarMenuButton as-child @click="handleItemClick(item.url)">
            <a :href="item.url" :target="item.url.startsWith('#') ? undefined : '_blank'" rel="noopener noreferrer" class="flex items-center">
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
import { trans as $t } from 'laravel-vue-i18n';

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
  }[];
}>();

const emit = defineEmits<(e: 'itemClick', url: string) => void>();

const sectionTitle = $t('Pagalba');

const handleItemClick = (url: string) => {
  emit('itemClick', url);
};
</script>
