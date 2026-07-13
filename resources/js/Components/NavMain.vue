<template>
  <SidebarGroup>
    <SidebarGroupLabel>{{ sectionTitle }}</SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu>
        <Collapsible v-for="item in items" :key="item.title" as-child :default-open="item.isActive">
          <SidebarMenuItem :data-tour="item.dataTour">
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
              <SidebarMenuButton as-child :tooltip="item.title" :is-active="item.isActive">
                <!-- Opening the page is engagement enough; don't make them find the popover button. -->
                <Link :href="item.url" prefetch @click="item.spotlight.dismiss()">
                  <component :is="item.icon" />
                  <span>{{ item.title }}</span>
                </Link>
              </SidebarMenuButton>
            </SpotlightPopover>

            <SidebarMenuButton v-else as-child :tooltip="item.title" :is-active="item.isActive">
              <Link :href="item.url" prefetch>
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
                      <Link :href="subItem.url" prefetch>
                        <span>{{ subItem.title }}</span>
                      </Link>
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

<script setup lang="ts">
import { ChevronRight, type LucideIcon } from 'lucide-vue-next';
import { usePage, Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

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
} from '@/Components/ui/sidebar';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';

defineProps<{
  items: {
    title: string;
    url: string;
    icon: LucideIcon;
    isActive?: boolean;
    dataTour?: string;
    /** Draws a pulsing spotlight on the entry until the user engages with it. */
    spotlight?: {
      title: string;
      description: string;
      isDismissed: boolean;
      dismiss: () => void;
    };
    items?: {
      title: string;
      url: string;
    }[];
  }[];
}>();

// Compute section title based on the current locale
const sectionTitle = $t('Funkcijos');
</script>
