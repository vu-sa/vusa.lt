<template>
  <Card
    class="group hover:shadow-md transition-all duration-200 cursor-pointer"
    @click="navigateToShow"
  >
    <CardContent class="p-4">
      <div class="flex items-start gap-3">
        <!-- Icon -->
        <div
          class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 group-hover:bg-blue-500/15 transition-colors"
        >
          <MeetingIcon class="size-5" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <!-- Title and Status -->
          <div class="flex items-start justify-between gap-2">
            <h3 class="font-medium text-foreground truncate group-hover:text-primary transition-colors">
              {{ meeting.title || $t('Be pavadinimo') }}
            </h3>
            <Badge
              v-if="meeting.completion_status"
              :variant="meeting.completion_status === 'complete' ? 'default' : 'secondary'"
              :class="[
                meeting.completion_status === 'complete'
                  ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20'
                  : 'bg-amber-500/10 text-amber-600 hover:bg-amber-500/20'
              ]"
            >
              {{ meeting.completion_status === 'complete' ? $t('Baigtas') : $t('Nebaigtas') }}
            </Badge>
          </div>

          <!-- Description -->
          <p
            v-if="meeting.description"
            class="text-sm text-muted-foreground line-clamp-2 mt-1"
          >
            {{ meeting.description }}
          </p>

          <!-- Meta info -->
          <div class="flex flex-wrap items-center gap-2 mt-2">
            <Badge v-if="meeting.tenant_shortname" variant="outline" class="text-xs">
              <Building2 class="size-3 mr-1" />
              {{ meeting.tenant_shortname }}
            </Badge>
            <Badge v-if="meeting.institution_name_lt" variant="secondary" class="text-xs">
              {{ meeting.institution_name_lt }}
            </Badge>
            <Badge v-if="formattedDate" variant="secondary" class="text-xs">
              <CalendarIcon class="size-3 mr-1" />
              {{ formattedDate }}
            </Badge>
            <Badge v-if="meeting.agenda_items_count" variant="outline" class="text-xs">
              <ListChecks class="size-3 mr-1" />
              {{ meeting.agenda_items_count }} {{ $t('punktai') }}
            </Badge>
          </div>

          <!-- Vote Alignment Status -->
          <div v-if="meeting.vote_alignment_status" class="mt-2">
            <Badge
              :variant="getVoteAlignmentVariant(meeting.vote_alignment_status)"
              class="text-xs"
            >
              <Vote class="size-3 mr-1" />
              {{ getVoteAlignmentLabel(meeting.vote_alignment_status) }}
            </Badge>
          </div>
        </div>
      </div>
    </CardContent>

    <!-- Actions Footer -->
    <CardFooter class="px-4 py-3 bg-muted/30 border-t justify-end gap-2">
      <Button
        variant="ghost"
        size="sm"
        @click.stop="navigateToShow"
      >
        <Eye class="size-4 mr-1" />
        {{ $t('Peržiūrėti') }}
      </Button>
      <Button
        variant="ghost"
        size="sm"
        @click.stop="navigateToEdit"
      >
        <Pencil class="size-4 mr-1" />
        {{ $t('Redaguoti') }}
      </Button>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  CalendarIcon,
  Building2,
  ListChecks,
  Vote,
  Eye,
  Pencil,
} from 'lucide-vue-next';

import { Card, CardContent, CardFooter } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { MeetingIcon } from '@/Components/icons';
import type { MeetingSearchResult } from '@/Composables/useAdminSearch';

interface Props {
  meeting: MeetingSearchResult;
}

const props = defineProps<Props>();

// Format the start time
const formattedDate = computed(() => {
  if (!props.meeting.start_time) return null;
  const date = new Date(props.meeting.start_time * 1000);
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
});

// Navigate to show page
const navigateToShow = () => {
  router.visit(route('meetings.show', props.meeting.id));
};

// Navigate to edit page
const navigateToEdit = () => {
  router.visit(route('meetings.edit', props.meeting.id));
};

// Get vote alignment badge variant
const getVoteAlignmentVariant = (status: string) => {
  switch (status) {
    case 'aligned':
      return 'default';
    case 'misaligned':
      return 'destructive';
    default:
      return 'secondary';
  }
};

// Get vote alignment label (consistent with public-facing labels)
const getVoteAlignmentLabel = (status: string) => {
  switch (status) {
    case 'aligned':
      return $t('Pozicija priimta');
    case 'misaligned':
      return $t('Pozicija nepriimta');
    case 'incomplete':
      return $t('Nepilna informacija');
    default:
      return status;
  }
};
</script>
