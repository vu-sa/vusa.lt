<script setup lang="ts">
import { useApi } from '@/Composables/useApi'
import { trans as $t } from 'laravel-vue-i18n'
import { Link } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'
import { Building2, Calendar, ChevronRight, BellOff } from 'lucide-vue-next'
import { Skeleton } from '@/Components/ui/skeleton'
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuItem,
  SidebarMenuButton,
} from '@/Components/ui/sidebar'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip'

interface FollowedInstitution {
  id: string
  name: string
  short_name: string | null
  alias: string
  meeting_periodicity_days: number
  next_meeting: {
    id: string
    title: string
    start_time: string
  } | null
  subscription: {
    is_followed: boolean
    is_muted: boolean
    is_duty_based: boolean
  }
}

const { data, isFetching, execute } = useApi<FollowedInstitution[]>(
  route('api.v1.admin.institutions.followed'),
  { immediate: false }
)

const institutions = computed(() => data.value ?? [])

const hasInstitutions = computed(() => institutions.value.length > 0)

// Format date for display
const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('lt-LT', { month: 'short', day: 'numeric' })
}

// Load data on mount
onMounted(() => {
  execute()
})
</script>

<template>
  <SidebarGroup class="group-data-[collapsible=icon]:hidden">
    <SidebarGroupLabel class="text-xs font-medium text-muted-foreground">
      {{ $t('visak.followed_institutions') }}
    </SidebarGroupLabel>
    <SidebarGroupContent>
      <!-- Loading state -->
      <div v-if="isFetching" class="space-y-2 px-2">
        <Skeleton class="h-8 w-full" />
        <Skeleton class="h-8 w-full" />
      </div>

      <!-- Empty state -->
      <div v-else-if="!hasInstitutions" class="px-2 py-2 text-xs text-muted-foreground">
        {{ $t('Nėra stebimų institucijų') }}
      </div>

      <!-- Institutions list -->
      <SidebarMenu v-else>
        <SidebarMenuItem v-for="institution in institutions" :key="institution.id">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <SidebarMenuButton
                  as-child
                  class="group/item hover:bg-sidebar-accent/50"
                >
                  <Link
                    :href="route('institutions.show', { institution: institution.id })"
                    class="flex items-center gap-2"
                  >
                    <Building2 class="h-4 w-4 shrink-0 text-muted-foreground" />
                    <span class="truncate flex-1 text-sm">
                      {{ institution.short_name || institution.name }}
                    </span>
                    <!-- Muted indicator -->
                    <BellOff
                      v-if="institution.subscription.is_muted"
                      class="h-3 w-3 text-muted-foreground"
                    />
                    <!-- Next meeting indicator -->
                    <span
                      v-if="institution.next_meeting"
                      class="text-xs text-muted-foreground flex items-center gap-0.5"
                    >
                      <Calendar class="h-3 w-3" />
                      {{ formatDate(institution.next_meeting.start_time) }}
                    </span>
                    <ChevronRight
                      class="h-4 w-4 text-muted-foreground opacity-0 group-hover/item:opacity-100 transition-opacity"
                    />
                  </Link>
                </SidebarMenuButton>
              </TooltipTrigger>
              <TooltipContent side="right" class="max-w-xs">
                <div class="space-y-1">
                  <p class="font-medium">{{ institution.name }}</p>
                  <p v-if="institution.next_meeting" class="text-xs text-muted-foreground">
                    {{ $t('Kitas posėdis') }}: {{ institution.next_meeting.title }}
                  </p>
                  <p v-else class="text-xs text-muted-foreground">
                    {{ $t('Nėra suplanuotų posėdžių') }}
                  </p>
                </div>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroupContent>
  </SidebarGroup>
</template>
