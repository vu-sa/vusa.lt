<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { trans as $t } from "laravel-vue-i18n"
import type { LucideIcon } from 'lucide-vue-next'
import {
  CalendarPlus,
  FileText,
  Building2,
  Sparkles,
} from 'lucide-vue-next'

import {
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/ui/sidebar'

const emit = defineEmits<{
  (e: 'newMeeting'): void
  (e: 'newNews'): void
  (e: 'newReservation'): void
}>()

interface QuickAction {
  title: string
  icon: LucideIcon
  action: () => void
  gradient: string
}

const quickActions = computed<QuickAction[]>(() => {
  const actions: QuickAction[] = []
  const page = usePage()

  if (page.props.auth?.can?.create?.meeting) {
    actions.push({
      title: $t('Naujas susitikimas'),
      icon: CalendarPlus,
      action: () => emit('newMeeting'),
      gradient: 'from-amber-500/15 to-orange-500/15 hover:from-amber-500/25 hover:to-orange-500/25 dark:from-amber-400/10 dark:to-orange-400/10 dark:hover:from-amber-400/20 dark:hover:to-orange-400/20',
    })
  }

  if (page.props.auth?.can?.create?.news) {
    actions.push({
      title: $t('Nauja naujiena'),
      icon: FileText,
      action: () => emit('newNews'),
      gradient: 'from-blue-500/15 to-cyan-500/15 hover:from-blue-500/25 hover:to-cyan-500/25 dark:from-blue-400/10 dark:to-cyan-400/10 dark:hover:from-blue-400/20 dark:hover:to-cyan-400/20',
    })
  }

  if (page.props.auth?.can?.create?.reservation) {
    actions.push({
      title: $t('Nauja rezervacija'),
      icon: Building2,
      action: () => emit('newReservation'),
      gradient: 'from-emerald-500/15 to-teal-500/15 hover:from-emerald-500/25 hover:to-teal-500/25 dark:from-emerald-400/10 dark:to-teal-400/10 dark:hover:from-emerald-400/20 dark:hover:to-teal-400/20',
    })
  }

  return actions
})

const sectionTitle = $t('Greiti veiksmai')
</script>

<template>
  <SidebarGroup v-if="quickActions.length > 0" class="group-data-[collapsible=icon]:hidden">
    <SidebarGroupLabel class="flex items-center gap-2">
      <Sparkles class="h-3 w-3 text-muted-foreground" />
      {{ sectionTitle }}
    </SidebarGroupLabel>
    <SidebarGroupContent>
      <SidebarMenu>
        <SidebarMenuItem v-for="action in quickActions" :key="action.title">
          <SidebarMenuButton 
            @click="action.action"
            :tooltip="action.title"
            class="transition-colors"
          >
            <div 
              :class="[
                'flex items-center justify-center rounded-md p-1 bg-gradient-to-br transition-colors',
                action.gradient
              ]"
            >
              <component :is="action.icon" class="h-4 w-4" />
            </div>
            <span>{{ action.title }}</span>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
