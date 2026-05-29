<template>
  <SidebarGroup class="group-data-[collapsible=icon]:hidden">
    <SidebarGroupLabel class="flex items-center gap-2">
      <History class="h-3 w-3 text-muted-foreground" />
      {{ $t('Neseniai aplankyta') }}
    </SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu v-if="items.length">
        <SidebarMenuItem v-for="item in items" :key="item.id" class="group/recent relative">
          <SidebarMenuButton as-child>
            <Link :href="item.href" class="flex items-center">
              <component :is="item.icon" class="h-4 w-4 shrink-0" />
              <span class="truncate">{{ item.title }}</span>
            </Link>
          </SidebarMenuButton>
          <SidebarMenuAction
            :class="[
              'transition-opacity',
              isPinned({ routeName: item.routeName, href: item.href })
                ? 'opacity-100 text-amber-500'
                : 'opacity-0 group-hover/recent:opacity-100 focus-visible:opacity-100',
            ]"
            :title="isPinned({ routeName: item.routeName, href: item.href }) ? $t('Atsegti') : $t('Prisegti puslapį')"
            :aria-label="isPinned({ routeName: item.routeName, href: item.href }) ? $t('Atsegti') : $t('Prisegti puslapį')"
            @click="togglePin({ routeName: item.routeName, href: item.href, title: item.title })"
          >
            <Star
              class="h-4 w-4"
              :fill="isPinned({ routeName: item.routeName, href: item.href }) ? 'currentColor' : 'none'" />
          </SidebarMenuAction>
        </SidebarMenuItem>
        <SidebarMenuItem>
          <SidebarMenuButton
            class="text-xs text-muted-foreground hover:text-foreground transition-colors"
            @click="showHistoryDialog = true"
          >
            <span class="truncate">{{ $t('Rodyti visus') }} ({{ totalCount }})</span>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
      <div v-else class="px-2 py-1.5 text-xs text-muted-foreground">
        {{ $t('Čia bus rodomi neseniai aplankyti puslapiai') }}
      </div>
    </SidebarGroupContent>
  </SidebarGroup>

  <RecentPagesDialog v-model:open="showHistoryDialog" />
</template>

<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { History, Star } from 'lucide-vue-next';

import { resolvePageIcon } from '@/Composables/adminPageCatalog';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import RecentPagesDialog from './RecentPagesDialog.vue';
import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuAction,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/ui/sidebar';

const { recentPages, isPinned, togglePin } = useUIPreferences();
const showHistoryDialog = ref(false);

interface RecentEntry {
  id: string;
  title: string;
  href: string;
  routeName?: string;
  icon: Component;
}

const MAX_SIDEBAR_RECENT = 5;

const items = computed<RecentEntry[]>(() => {
  return recentPages.value
    .slice(0, MAX_SIDEBAR_RECENT)
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

const totalCount = computed(() => recentPages.value.length);
</script>
