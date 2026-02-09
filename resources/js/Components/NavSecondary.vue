<template>
  <SidebarGroup>
    <SidebarGroupLabel>{{ sectionTitle }}</SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in items" :key="item.title" :data-tour="item.dataTour">
          <SidebarMenuButton as-child @click="handleItemClick(item.url)">
            <div class="flex items-center">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </div>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>

<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
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
