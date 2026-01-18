<template>
  <Card
    class="group hover:shadow-md transition-all duration-200 cursor-pointer"
    @click="navigateToMeeting"
  >
    <CardContent class="p-4">
      <div class="flex items-start gap-3">
        <!-- Icon -->
        <div
          class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:bg-violet-500/20 dark:text-violet-400 group-hover:bg-violet-500/15 transition-colors"
        >
          <AgendaItemIcon class="size-5" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <!-- Title and Vote Result -->
          <div class="flex items-start justify-between gap-2">
            <h3 class="font-medium text-foreground truncate group-hover:text-primary transition-colors">
              {{ item.title || $t('Be pavadinimo') }}
            </h3>
            <Badge
              v-if="item.student_vote"
              :class="getVoteBadgeClasses(item.student_vote)"
            >
              {{ getVoteLabel(item.student_vote) }}
            </Badge>
          </div>

          <!-- Description / Student Benefit -->
          <p
            v-if="item.student_benefit || item.description"
            class="text-sm text-muted-foreground line-clamp-2 mt-1"
          >
            {{ item.student_benefit || item.description }}
          </p>

          <!-- Meeting Context -->
          <div v-if="item.meeting_title" class="mt-2 flex items-center gap-2 text-sm">
            <MeetingIcon class="size-4 text-muted-foreground" />
            <span class="text-muted-foreground truncate">{{ item.meeting_title }}</span>
            <span v-if="formattedDate" class="text-muted-foreground/50">•</span>
            <span v-if="formattedDate" class="text-muted-foreground shrink-0">{{ formattedDate }}</span>
          </div>

          <!-- Meta info badges -->
          <div class="flex flex-wrap items-center gap-2 mt-2">
            <Badge v-if="item.decision" variant="outline" class="text-xs">
              <Gavel class="size-3 mr-1" />
              {{ getDecisionLabel(item.decision) }}
            </Badge>
            <Badge
              v-if="item.is_complete !== undefined"
              :variant="item.is_complete ? 'default' : 'secondary'"
              :class="item.is_complete ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600'"
              class="text-xs"
            >
              <CheckCircle class="size-3 mr-1" />
              {{ item.is_complete ? $t('Užpildyta') : $t('Neužpildyta') }}
            </Badge>
            <Badge
              v-if="item.brought_by_students"
              variant="outline"
              class="text-xs bg-primary/10 text-primary"
            >
              <UserCheck class="size-3 mr-1" />
              {{ $t('Studentų') }}
            </Badge>
          </div>

          <!-- Vote Alignment -->
          <div v-if="item.vote_alignment_status && item.vote_alignment_status !== 'unknown'" class="mt-2">
            <Badge
              :variant="getVoteAlignmentVariant(item.vote_alignment_status)"
              class="text-xs"
            >
              <Vote class="size-3 mr-1" />
              {{ getVoteAlignmentLabel(item.vote_alignment_status) }}
            </Badge>
          </div>
        </div>
      </div>
    </CardContent>

    <!-- Actions Footer -->
    <CardFooter class="px-4 py-3 bg-muted/30 border-t justify-end gap-2">
      <Button
        v-if="item.meeting_id"
        variant="ghost"
        size="sm"
        @click.stop="navigateToMeeting"
      >
        <MeetingIcon class="size-4 mr-1" />
        {{ $t('Posėdis') }}
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
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import {
  CheckCircle,
  Gavel,
  UserCheck,
  Vote,
  Pencil,
} from 'lucide-vue-next'

import { Card, CardContent, CardFooter } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { AgendaItemIcon, MeetingIcon } from '@/Components/icons'

import type { AgendaItemSearchResult } from '@/Composables/useAdminSearch'

interface Props {
  item: AgendaItemSearchResult
}

const props = defineProps<Props>()

// Format the meeting start time
const formattedDate = computed(() => {
  if (!props.item.meeting_start_time) return null
  const date = new Date(props.item.meeting_start_time * 1000)
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
})

// Navigate to meeting show page
const navigateToMeeting = () => {
  if (props.item.meeting_id) {
    router.visit(route('meetings.show', props.item.meeting_id))
  }
}

// Navigate to agenda item edit page
const navigateToEdit = () => {
  router.visit(route('agendaItems.edit', props.item.id))
}

// Get vote badge classes
const getVoteBadgeClasses = (vote: string) => {
  const voteLower = vote.toLowerCase()
  if (voteLower === 'for' || voteLower === 'už') {
    return 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20'
  }
  if (voteLower === 'against' || voteLower === 'prieš') {
    return 'bg-red-500/10 text-red-600 hover:bg-red-500/20'
  }
  return 'bg-amber-500/10 text-amber-600 hover:bg-amber-500/20'
}

// Get vote label
const getVoteLabel = (vote: string) => {
  const voteMap: Record<string, string> = {
    for: $t('Už'),
    against: $t('Prieš'),
    abstain: $t('Susilaikė'),
  }
  return voteMap[vote.toLowerCase()] || vote
}

// Get decision label
const getDecisionLabel = (decision: string) => {
  const decisionMap: Record<string, string> = {
    approved: $t('Pritarta'),
    rejected: $t('Atmesta'),
    postponed: $t('Atidėta'),
    noted: $t('Susipažinta'),
  }
  return decisionMap[decision.toLowerCase()] || decision
}

// Get vote alignment badge variant
const getVoteAlignmentVariant = (status: string) => {
  switch (status) {
    case 'aligned':
      return 'default'
    case 'misaligned':
      return 'destructive'
    default:
      return 'secondary'
  }
}

// Get vote alignment label
const getVoteAlignmentLabel = (status: string) => {
  switch (status) {
    case 'aligned':
      return $t('Balsai atitinka')
    case 'misaligned':
      return $t('Balsai neatitinka')
    case 'incomplete':
      return $t('Nepilna informacija')
    default:
      return status
  }
}
</script>
