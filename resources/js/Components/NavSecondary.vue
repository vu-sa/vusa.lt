<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'

import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/ui/sidebar'

const props = defineProps<{
  items: {
    title: string
    url: string
    icon: LucideIcon
  }[]
}>()

const emit = defineEmits<{
  (e: 'itemClick', url: string): void
}>()

const sectionTitle = usePage().props.app.locale === 'en' ? 'Support' : 'Pagalba'

const handleItemClick = (url: string) => {
  emit('itemClick', url)
}
</script>

<template>
  <SidebarGroup>
    <SidebarGroupLabel>{{ sectionTitle }}</SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in items" :key="item.title">
          <SidebarMenuButton as-child size="sm" @click="handleItemClick(item.url)">
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
