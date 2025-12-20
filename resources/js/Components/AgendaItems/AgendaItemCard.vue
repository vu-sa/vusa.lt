<template>
  <Card class="agenda-item-card group transition-all duration-200 hover:shadow-md hover:ring-1 hover:ring-zinc-200 dark:hover:ring-zinc-700">
    <CardContent class="px-3 py-3 sm:px-4 sm:py-3.5">
      <div class="flex items-center gap-3">
        <!-- Drag Handle (visible on hover) -->
        <div
          class="drag-handle shrink-0 cursor-grab active:cursor-grabbing p-1 -ml-2 text-zinc-300 hover:text-zinc-500 dark:text-zinc-600 dark:hover:text-zinc-400 transition-all duration-150 opacity-0 group-hover:opacity-100">
          <GripVertical class="h-4 w-4" />
        </div>

        <!-- Item Number -->
        <div
          class="flex-shrink-0 w-7 h-7 rounded-lg bg-zinc-100 dark:bg-zinc-800/80 flex items-center justify-center text-xs font-semibold text-zinc-600 dark:text-zinc-300 tabular-nums">
          {{ order }}
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <!-- Title Row with Compact Indicators -->
          <div class="flex items-center justify-between gap-2">
            <button type="button" class="text-left flex-1 min-w-0 flex items-center gap-2" @click="showDetailed = !showDetailed">
              <h3
                class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors leading-snug">
                {{ item.title }}
              </h3>
              <Badge v-if="item.brought_by_students" variant="default" class="bg-vusa-red hover:bg-vusa-red/90 shrink-0 text-[10px] px-1.5 py-0">
                {{ $t('Studentų') }}
              </Badge>
            </button>

            <!-- Right side: Compact indicators + Menu -->
            <div class="flex items-center gap-1 shrink-0">
              <!-- Compact Vote Status (always visible) -->
              <CompactVoteIndicator :decision="item.decision" :student-vote="item.student_vote"
                :student-benefit="item.student_benefit" @click="showDetailed = !showDetailed" />

              <!-- More menu -->
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon"
                    class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity">
                    <MoreVertical class="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem @click="$emit('edit', item)">
                    <Edit class="h-4 w-4 mr-2" />
                    {{ $t('Redaguoti') }}
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem class="text-destructive focus:text-destructive" @click="$emit('delete', item)">
                    <Trash2 class="h-4 w-4 mr-2" />
                    {{ $t('Šalinti') }}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>

          <!-- Description Preview (collapsed state) -->
          <p v-if="item.description && !showDetailed" class="text-[13px] text-zinc-500 dark:text-zinc-400 line-clamp-1 mt-1.5 leading-relaxed">
            {{ item.description }}
          </p>

          <!-- Expandable Voting Section -->
          <Collapsible v-model:open="showDetailed">
            <CollapsibleContent>
              <div class="pt-3 space-y-2.5 border-t border-zinc-100 dark:border-zinc-800 mt-3 -mx-1 px-1">
                <!-- Voting Controls - Compact Layout -->
                <!-- Decision -->
                <InlineVoteControl :label="$t('voting_field_decision_label')"
                  :tooltip="$t('voting_field_decision_tooltip')" :value="item.decision" :options="decisionOptions"
                  @update="(val) => updateField('decision', val)" />

                <!-- Student Vote -->
                <InlineVoteControl :label="$t('voting_field_student_vote_label')"
                  :tooltip="$t('voting_field_student_vote_tooltip')" :value="item.student_vote" :options="voteOptions"
                  @update="(val) => updateField('student_vote', val)" />

                <!-- Student Benefit -->
                <InlineVoteControl :label="$t('voting_field_student_benefit_label')"
                  :tooltip="$t('voting_field_student_benefit_tooltip')" :value="item.student_benefit"
                  :options="benefitOptions" @update="(val) => updateField('student_benefit', val)" />

                <!-- Description row - unified display and edit -->
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-1 w-48 sm:w-56 shrink-0 pt-0.5">
                    <span class="text-xs text-zinc-600 dark:text-zinc-400 truncate">{{ $t('Aprašymas') }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <button type="button" class="w-full text-left group/desc" @click="$emit('edit', item)">
                      <div v-if="item.description"
                        class="text-sm text-zinc-600 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900/50 rounded-md p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <span>{{ item.description }}</span>
                        <Edit
                          class="inline-block h-3 w-3 ml-1.5 text-zinc-400 opacity-0 group-hover/desc:opacity-100 transition-opacity" />
                      </div>
                      <div v-else
                        class="text-xs text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-400 transition-colors flex items-center gap-1 py-1">
                        <Plus class="h-3 w-3" />
                        {{ $t('Pridėti aprašymą') }}
                      </div>
                    </button>
                  </div>
                </div>
              </div>
            </CollapsibleContent>
          </Collapsible>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

// Icons
import {
  GripVertical,
  MoreVertical,
  Edit,
  Trash2,
  Plus,
  Check,
  X,
  Minus,
  ThumbsUp,
  ThumbsDown
} from 'lucide-vue-next'

// UI Components
import CompactVoteIndicator from './CompactVoteIndicator.vue'
import InlineVoteControl from './InlineVoteControl.vue'

import { Card, CardContent } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Collapsible, CollapsibleContent } from '@/Components/ui/collapsible'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/Components/ui/dropdown-menu'

// Custom Components

interface AgendaItem {
  id: string
  title: string
  description?: string | null
  order: number
  brought_by_students?: boolean
  decision?: string | null
  student_vote?: string | null
  student_benefit?: string | null
}

interface Props {
  item: AgendaItem
  order: number
  showVoteOptions?: boolean
}

interface Emits {
  (e: 'edit', item: AgendaItem): void
  (e: 'delete', item: AgendaItem): void
  (e: 'update', item: AgendaItem, field: string, value: any): void
}

const props = withDefaults(defineProps<Props>(), {
  showVoteOptions: false
})

const emit = defineEmits<Emits>()

const showDetailed = ref(false)

// Check if any votes have been recorded
const hasAnyVotes = computed(() => {
  return props.item.decision || props.item.student_vote || props.item.student_benefit
})

// Auto-expand/collapse when showVoteOptions changes
watch(() => props.showVoteOptions, (newValue) => {
  showDetailed.value = newValue
}, { immediate: true })

// Vote options with icons and colors
const decisionOptions = computed(() => [
  { value: 'positive', icon: Check, label: $t('Priimtas'), color: 'green' as const },
  { value: 'negative', icon: X, label: $t('Nepriimtas'), color: 'red' as const },
  { value: 'neutral', icon: Minus, label: $t('Susilaikyta'), color: 'zinc' as const },
])

const voteOptions = computed(() => [
  { value: 'positive', icon: Check, label: $t('Pritarė'), color: 'green' as const },
  { value: 'negative', icon: X, label: $t('Nepritarė'), color: 'red' as const },
  { value: 'neutral', icon: Minus, label: $t('Susilaikė'), color: 'zinc' as const },
])

const benefitOptions = computed(() => [
  { value: 'positive', icon: ThumbsUp, label: $t('Palanku'), color: 'green' as const },
  { value: 'negative', icon: ThumbsDown, label: $t('Nepalanku'), color: 'red' as const },
  { value: 'neutral', icon: Minus, label: $t('Neutralu'), color: 'zinc' as const },
])

const updateField = (field: string, value: any) => {
  emit('update', props.item, field, value)
}
</script>
