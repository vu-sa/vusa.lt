<template>
  <SidebarGroup class="group-data-[collapsible=icon]:hidden">
    <!-- Dynamic label: clickable when empty, with settings icon when has institutions -->
    <SidebarGroupLabel class="text-xs font-medium text-muted-foreground flex items-center justify-between">
      <button
        v-if="!hasInstitutions && !isFetching"
        class="hover:text-foreground transition-colors cursor-pointer text-left"
        @click="showManageModal = true"
      >
        {{ $t('visak.follow_institutions_question') }}
      </button>
      <span v-else>{{ $t('visak.followed_institutions') }}</span>

      <!-- Settings icon when has institutions -->
      <TooltipProvider v-if="hasInstitutions">
        <Tooltip>
          <TooltipTrigger as-child>
            <button
              class="p-0.5 rounded hover:bg-sidebar-accent/50 text-muted-foreground hover:text-foreground transition-colors"
              @click="showManageModal = true"
            >
              <Settings2 class="h-3.5 w-3.5" />
            </button>
          </TooltipTrigger>
          <TooltipContent side="right">
            {{ $t('visak.manage_subscriptions') }}
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </SidebarGroupLabel>
    <SidebarGroupContent>
      <!-- Loading state -->
      <div v-if="isFetching" class="space-y-2 px-2">
        <Skeleton class="h-8 w-full" />
        <Skeleton class="h-8 w-full" />
      </div>

      <!-- Institutions list (no empty state text - the label acts as CTA) -->
      <SidebarMenu v-if="hasInstitutions">
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
                  <p class="font-medium">
                    {{ institution.name }}
                  </p>
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

  <!-- Manage Subscriptions Modal -->
  <ManageSubscriptionsModal
    :is-open="showManageModal"
    @update:is-open="showManageModal = $event"
  />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref, defineAsyncComponent } from 'vue';
import { Building2, Calendar, ChevronRight, BellOff, Settings2 } from 'lucide-vue-next';

import { useApi } from '@/Composables/useApi';
import { Skeleton } from '@/Components/ui/skeleton';
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuItem,
  SidebarMenuButton,
} from '@/Components/ui/sidebar';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip';

// Async load the modal to avoid circular dependency issues
const ManageSubscriptionsModal = defineAsyncComponent(
  () => import('@/Pages/Admin/Dashboard/Components/ManageSubscriptionsModal.vue'),
);

interface FollowedInstitution {
  id: string;
  name: string;
  short_name: string | null;
  alias: string;
  meeting_periodicity_days: number;
  next_meeting: {
    id: string;
    title: string;
    start_time: string;
  } | null;
  subscription: {
    is_followed: boolean;
    is_muted: boolean;
    is_duty_based: boolean;
  };
}

const { data, isFetching, execute } = useApi<FollowedInstitution[]>(
  route('api.v1.admin.institutions.followed'),
  { immediate: false },
);

const institutions = computed(() => data.value ?? []);

const hasInstitutions = computed(() => institutions.value.length > 0);

// Modal state
const showManageModal = ref(false);

// Format date for display
const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('lt-LT', { month: 'short', day: 'numeric' });
};

// Load data on mount
onMounted(() => {
  execute();
});
</script>
