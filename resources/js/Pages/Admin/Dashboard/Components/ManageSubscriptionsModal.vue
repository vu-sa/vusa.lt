<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-[600px] max-h-[85vh] overflow-hidden flex flex-col">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <Settings2 class="h-5 w-5" />
          {{ $t('visak.manage_subscriptions') }}
        </DialogTitle>
        <DialogDescription class="space-y-2">
          <p>{{ $t('visak.manage_subscriptions_description') }}</p>
        </DialogDescription>
      </DialogHeader>

      <div class="flex-1 overflow-y-auto space-y-6 py-4">
        <!-- Loading state -->
        <div v-if="isFetching" class="space-y-3">
          <Skeleton class="h-12 w-full" />
          <Skeleton class="h-12 w-full" />
          <Skeleton class="h-12 w-full" />
        </div>

        <template v-else>
          <!-- Additionally Followed Institutions (non-duty-based) -->
          <div class="space-y-3">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
              <Eye class="h-4 w-4" />
              {{ $t('visak.additionally_followed') }}
            </h3>

            <div v-if="additionallyFollowed.length === 0"
              class="text-sm text-zinc-500 dark:text-zinc-400 py-4 text-center bg-zinc-50 dark:bg-zinc-800/50 rounded-lg">
              {{ $t('visak.no_additional_follows') }}
            </div>

            <div v-else class="space-y-2">
              <div v-for="institution in additionallyFollowed" :key="institution.id"
                class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50">
                <div class="flex items-center gap-3 min-w-0">
                  <Building2 class="h-4 w-4 shrink-0 text-zinc-500" />
                  <span class="font-medium text-sm truncate">{{ institution.name }}</span>
                  <BellOff v-if="institution.subscription?.is_muted" class="h-3.5 w-3.5 text-zinc-400 shrink-0" />
                </div>
                <div class="flex items-center gap-1">
                  <!-- Mute/Unmute button -->
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <Button variant="ghost" size="sm" class="h-8 w-8"
                          :disabled="subscriptions.isMuteLoading(institution.id)"
                          @click="handleToggleMute(institution)">
                          <Loader2 v-if="subscriptions.isMuteLoading(institution.id)" class="h-3.5 w-3.5 animate-spin" />
                          <BellOff v-else-if="institution.subscription?.is_muted" class="h-3.5 w-3.5" />
                          <Bell v-else class="h-3.5 w-3.5" />
                        </Button>
                      </TooltipTrigger>
                      <TooltipContent>
                        {{ institution.subscription?.is_muted ? $t('visak.unmute') : $t('visak.mute') }}
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                  <!-- Unfollow button -->
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <Button variant="ghost" size="sm" class="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30"
                          :disabled="subscriptions.isFollowLoading(institution.id)"
                          @click="handleToggleFollow(institution)">
                          <Loader2 v-if="subscriptions.isFollowLoading(institution.id)" class="h-3.5 w-3.5 animate-spin" />
                          <EyeOff v-else class="h-3.5 w-3.5" />
                        </Button>
                      </TooltipTrigger>
                      <TooltipContent>{{ $t('visak.unfollow') }}</TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
              </div>
            </div>
          </div>

          <!-- Muted Institutions -->
          <div class="space-y-3">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
              <BellOff class="h-4 w-4" />
              {{ $t('visak.muted_institutions') }}
            </h3>

            <div v-if="mutedInstitutions.length === 0"
              class="text-sm text-zinc-500 dark:text-zinc-400 py-4 text-center bg-zinc-50 dark:bg-zinc-800/50 rounded-lg">
              {{ $t('visak.no_muted_institutions') }}
            </div>

            <div v-else class="space-y-2">
              <div v-for="institution in mutedInstitutions" :key="institution.id"
                class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50">
                <div class="flex items-center gap-3 min-w-0">
                  <Building2 class="h-4 w-4 shrink-0 text-zinc-500" />
                  <span class="font-medium text-sm truncate">{{ institution.name }}</span>
                  <span v-if="institution.subscription?.is_duty_based"
                    class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-medium shrink-0">
                    {{ $t('visak.duty_based') }}
                  </span>
                </div>
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button variant="ghost" size="sm" class="h-8 w-8"
                        :disabled="subscriptions.isMuteLoading(institution.id)"
                        @click="handleToggleMute(institution)">
                        <Loader2 v-if="subscriptions.isMuteLoading(institution.id)" class="h-3.5 w-3.5 animate-spin" />
                        <Bell v-else class="h-3.5 w-3.5" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>{{ $t('visak.unmute') }}</TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              </div>
            </div>
          </div>
        </template>
      </div>

      <DialogFooter class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
        <Button variant="outline" @click="emit('update:isOpen', false)">
          {{ $t('Uždaryti') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Settings2, Eye, EyeOff, Bell, BellOff, Building2, Loader2 } from 'lucide-vue-next';

import { useInstitutionSubscription } from '../Composables/useInstitutionSubscription';

import { useApi } from '@/Composables/useApi';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

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

interface Props {
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
}>();

const subscriptions = useInstitutionSubscription();

// Fetch followed institutions when modal opens
const { data, isFetching, execute } = useApi<FollowedInstitution[]>(
  route('api.v1.admin.institutions.followed'),
  { immediate: false },
);

// Refresh data when modal opens
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    execute();
  }
});

const institutions = computed(() => data.value ?? []);

// Additionally followed = followed but not duty-based
const additionallyFollowed = computed(() => {
  return institutions.value.filter(inst =>
    inst.subscription.is_followed && !inst.subscription.is_duty_based,
  );
});

// Muted institutions
const mutedInstitutions = computed(() => {
  return institutions.value.filter(inst => inst.subscription.is_muted);
});

async function handleToggleFollow(institution: FollowedInstitution) {
  await subscriptions.toggleFollow(institution.id, institution.subscription, []);
  // Refresh the list
  execute();
}

async function handleToggleMute(institution: FollowedInstitution) {
  await subscriptions.toggleMute(institution.id, institution.subscription, []);
  // Refresh the list
  execute();
}
</script>
