<template>
  <Card class="agenda-item-card group hover:shadow-md transition-all duration-200"
    :class="{ 'cursor-pointer': !showVoteOptions }">
    <CardContent class="p-4">
      <div class="flex items-start gap-3">
        <!-- Drag Handle -->
        <div
          class="drag-handle shrink-0 cursor-move p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
          <GripVertical class="h-4 w-4" />
        </div>

        <!-- Item Number -->
        <div
          class="flex-shrink-0 w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-sm font-medium text-zinc-600 dark:text-zinc-400">
          {{ order }}
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0 space-y-3" :class="{ 'cursor-pointer': !showVoteOptions }" @click="handleCardClick">
          <!-- Title and Actions Row -->
          <div class="flex items-start justify-between gap-2">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 leading-tight">
              {{ item.title }}
            </h3>
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="h-8 w-8 shrink-0">
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

          <!-- Status Badges Row -->
          <div class="flex flex-wrap items-center gap-2" :class="{ 'mb-0': !showDetailed }">
            <StatusBadge :state="item.decision" type="decision" :show-tooltip="true" />

            <StatusBadge v-if="item.student_vote || showVoteOptions" :state="item.student_vote" type="student_vote"
              show-tooltip />

            <StatusBadge v-if="item.student_benefit || showVoteOptions" :state="item.student_benefit"
              type="student_benefit" :show-tooltip="true" />

            <!-- Description Indicator -->
            <Button v-if="item.description || !item.description" variant="ghost" size="sm" class="h-6 px-2 text-xs"
              @click.stop="$emit('edit', item)">
              <component :is="item.description ? FileText : FilePlus" class="h-3 w-3 mr-1" />
              {{ item.description ? $t('Aprašymas') : $t('Pridėti aprašymą') }}
            </Button>

            <!-- Manual Collapse/Expand Toggle -->
            <Button v-if="showVoteOptions" variant="ghost" size="sm" class="h-6 px-2 text-xs"
              @click.stop="showDetailed = !showDetailed">
              <component :is="showDetailed ? ChevronUp : ChevronDown" class="h-3 w-3 mr-1" />
              {{ showDetailed ? $t('Suskleisti') : $t('Išskleisti') }}
            </Button>
          </div>

          <!-- Description Preview -->
          <div v-if="item.description" class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2">
            {{ item.description }}
          </div>

          <!-- Detailed Vote Controls (Expandable) -->
          <Collapsible v-model:open="showDetailed">
            <CollapsibleContent class="space-y-3">
              <Separator />

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Decision Control -->
                <div class="space-y-2">
                  <Label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    {{ $t('Sprendimas') }}
                  </Label>
                  <div class="flex gap-1">
                    <Button variant="outline" size="sm"
                      :class="item.decision === 'positive' ? 'bg-green-50 border-green-200 text-green-700' : ''"
                      @click="updateField('decision', 'positive')">
                      <Check class="h-3 w-3" />
                    </Button>
                    <Button variant="outline" size="sm"
                      :class="item.decision === 'negative' ? 'bg-red-50 border-red-200 text-red-700' : ''"
                      @click="updateField('decision', 'negative')">
                      <X class="h-3 w-3" />
                    </Button>
                    <Button variant="outline" size="sm"
                      :class="item.decision === 'neutral' ? 'bg-zinc-50 border-zinc-200 text-zinc-700' : ''"
                      @click="updateField('decision', 'neutral')">
                      <Minus class="h-3 w-3" />
                    </Button>
                  </div>
                </div>

                <!-- Student Vote Control -->
                <div class="space-y-2">
                  <Label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    {{ $t('Kaip balsavo studentai') }}
                  </Label>
                  <div class="flex gap-1">
                    <Button variant="outline" size="sm"
                      :class="item.student_vote === 'positive' ? 'bg-green-50 border-green-200 text-green-700' : ''"
                      @click="updateField('student_vote', 'positive')">
                      <Check class="h-3 w-3" />
                    </Button>
                    <Button variant="outline" size="sm"
                      :class="item.student_vote === 'negative' ? 'bg-red-50 border-red-200 text-red-700' : ''"
                      @click="updateField('student_vote', 'negative')">
                      <X class="h-3 w-3" />
                    </Button>
                    <Button variant="outline" size="sm"
                      :class="item.student_vote === 'neutral' ? 'bg-zinc-50 border-zinc-200 text-zinc-700' : ''"
                      @click="updateField('student_vote', 'neutral')">
                      <Minus class="h-3 w-3" />
                    </Button>
                  </div>
                </div>

                <!-- Student Benefit Control -->
                <div class="space-y-2">
                  <Label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    {{ $t('Ar sprendimas palankus studentams') }}?
                  </Label>
                  <div class="flex gap-1">
                    <Button variant="outline" size="sm"
                      :class="item.student_benefit === 'positive' ? 'bg-green-50 border-green-200 text-green-700' : ''"
                      @click="updateField('student_benefit', 'positive')">
                      <ThumbsUp class="h-3 w-3" />
                    </Button>
                    <Button variant="outline" size="sm"
                      :class="item.student_benefit === 'negative' ? 'bg-red-50 border-red-200 text-red-700' : ''"
                      @click="updateField('student_benefit', 'negative')">
                      <ThumbsDown class="h-3 w-3" />
                    </Button>
                    <Button variant="outline" size="sm"
                      :class="item.student_benefit === 'neutral' ? 'bg-zinc-50 border-zinc-200 text-zinc-700' : ''"
                      @click="updateField('student_benefit', 'neutral')">
                      <Minus class="h-3 w-3" />
                    </Button>
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
import { ref, watch } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

// UI Components
import {
  GripVertical,
  MoreVertical,
  Edit,
  Trash2,
  FileText,
  FilePlus,
  Check,
  X,
  Minus,
  ThumbsUp,
  ThumbsDown,
  ChevronUp,
  ChevronDown
} from 'lucide-vue-next'

import StatusBadge from './StatusBadge.vue'

import { Card, CardContent } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { Separator } from '@/Components/ui/separator'
import { Collapsible, CollapsibleContent } from '@/Components/ui/collapsible'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/Components/ui/dropdown-menu'

// Custom Components

// Icons

interface AgendaItem {
  id: string
  title: string
  description?: string | null
  order: number
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

// Auto-expand when showVoteOptions is enabled
watch(() => props.showVoteOptions, (newValue) => {
  if (newValue) {
    showDetailed.value = true
  }
}, { immediate: true })

const updateField = (field: string, value: any) => {
  emit('update', props.item, field, value)
}

const handleCardClick = (event: MouseEvent) => {
  // Only toggle when showVoteOptions is false
  if (props.showVoteOptions) return

  // Don't toggle if clicking on interactive elements
  const target = event.target as HTMLElement
  const isInteractive = target.closest('button, a, [role="button"], [role="menuitem"]')

  if (!isInteractive) {
    showDetailed.value = !showDetailed.value
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
