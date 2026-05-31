<template>
  <SidebarGroup v-if="items.length" class="group-data-[collapsible=icon]:hidden">
    <SidebarGroupLabel class="flex items-center gap-2">
      <Pin class="h-3 w-3 text-muted-foreground" />
      {{ $t('Prisegti') }}
    </SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="item in items" :key="item.id" class="group/pin relative">
          <SidebarMenuButton as-child>
            <Link :href="item.href" class="flex items-center">
              <component :is="item.icon" class="h-4 w-4 shrink-0" />
              <span class="truncate">{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
          <SidebarMenuAction
            class="opacity-0 group-hover/pin:opacity-100 focus-visible:opacity-100"
            :title="$t('Atsegti')"
            :aria-label="$t('Atsegti')"
            @click="togglePin({ routeName: item.routeName, href: item.href, title: item.title })"
          >
            <PinOff class="h-4 w-4" />
          </SidebarMenuAction>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Pin, PinOff } from 'lucide-vue-next';

import { resolvePageIcon } from '@/Composables/adminPageCatalog';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuAction,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/ui/sidebar';

const { pinnedPages, togglePin } = useUIPreferences();

interface PinnedEntry {
  id: string;
  title: string;
  href: string;
  routeName?: string;
  icon: Component;
}

const items = computed<PinnedEntry[]>(() => {
  return pinnedPages.value
    .filter(page => page.href)
    .map((page) => {
      return {
        id: page.id,
        title: page.title,
        href: page.href as string,
        routeName: page.routeName,
        icon: resolvePageIcon(page.routeName, page.href),
      };
    });
});
</script>
