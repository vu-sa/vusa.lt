<script setup lang="ts">
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuAction,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/Components/ui/sidebar'
import { ChevronRight, type LucideIcon } from 'lucide-vue-next'
import { usePage, Link } from '@inertiajs/vue3'
import { trans as $t } from "laravel-vue-i18n"

defineProps<{
  items: {
    title: string
    url: string
    icon: LucideIcon
    isActive?: boolean
    dataTour?: string
    items?: {
      title: string
      url: string
    }[]
  }[]
}>()

// Compute section title based on the current locale
const sectionTitle = $t('Funkcijos')
</script>

<template>
  <SidebarGroup>
    <SidebarGroupLabel>{{ sectionTitle }}</SidebarGroupLabel>
    <SidebarGroupContent>
    <SidebarMenu>
      <Collapsible v-for="item in items" :key="item.title" as-child :default-open="item.isActive">
        <SidebarMenuItem :data-tour="item.dataTour">
          <SidebarMenuButton as-child :tooltip="item.title" :is-active="item.isActive">
            <Link :href="item.url">
              <component :is="item.icon" />
              <span>{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
          <template v-if="item.items?.length">
            <CollapsibleTrigger as-child>
              <SidebarMenuAction class="data-[state=open]:rotate-90">
                <ChevronRight />
                <span class="sr-only">Toggle</span>
              </SidebarMenuAction>
            </CollapsibleTrigger>
            <CollapsibleContent>
              <SidebarMenuSub>
                <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                  <SidebarMenuSubButton as-child>
                    <a :href="subItem.url">
                      <span>{{ subItem.title }}</span>
                    </a>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </CollapsibleContent>
          </template>
        </SidebarMenuItem>
      </Collapsible>
    </SidebarMenu>
</SidebarGroupContent>
  </SidebarGroup>
</template>
