<template>
  <div
    class="agenda-item-card group rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 transition-all duration-200"
    :class="{ 'ring-2 ring-primary/20 border-primary/40': isExpanded }"
  >
    <!-- Header Row (always visible) -->
    <div class="flex items-center gap-3 px-3 py-3 sm:px-4">
      <!-- Drag Handle -->
      <div
        class="drag-handle shrink-0 cursor-grab active:cursor-grabbing p-1 -ml-2 text-zinc-300 hover:text-zinc-500 dark:text-zinc-600 dark:hover:text-zinc-400 transition-all duration-150 opacity-0 group-hover:opacity-100"
      >
        <GripVertical class="h-4 w-4" />
      </div>

      <!-- Item Number -->
      <div
        class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-xs font-semibold tabular-nums"
        :class="numberBadgeClass"
      >
        {{ order }}
      </div>

      <!-- Title & Click Area -->
      <button
        type="button"
        class="flex-1 min-w-0 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 rounded-lg -my-1 py-1"
        @click="toggleExpanded"
      >
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 truncate">
            {{ item.title }}
          </h3>
          <Badge v-if="item.brought_by_students" variant="default" class="bg-vusa-red hover:bg-vusa-red/90 shrink-0 text-[10px] px-1.5 py-0">
            {{ $t('Studentų') }}
          </Badge>
        </div>
        <!-- Status Summary (collapsed state) -->
        <div v-if="!isExpanded" class="flex items-center gap-2 mt-1">
          <span class="text-xs" :class="statusTextClass">{{ statusText }}</span>
        </div>
      </button>

      <!-- Right side: Vote Badges (collapsed) + Expand Icon -->
      <div class="flex items-center gap-2 shrink-0">
        <!-- Vote badges in collapsed state (show if votes exist, regardless of item type) -->
        <div v-if="!isExpanded && item.votes && item.votes.length > 0" class="hidden sm:flex items-center gap-1">
          <VoteSelectionBadge
            v-for="(vote, index) in displayVotes"
            :key="vote.id"
            :vote
            :index
            :is-main="vote.is_main"
          />
          <span v-if="additionalVotesCount > 0" class="text-xs text-zinc-400">
            +{{ additionalVotesCount }}
          </span>
        </div>

        <!-- More menu (visible on hover) -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="ghost"
              size="icon"
              class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <MoreVertical class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem class="text-destructive focus:text-destructive" @click="$emit('delete', item)">
              <Trash2 class="h-4 w-4 mr-2" />
              {{ $t('Šalinti') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- Expand/Collapse -->
        <button
          type="button"
          class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
          @click="toggleExpanded"
        >
          <ChevronDown
            class="h-4 w-4 transition-transform duration-200"
            :class="{ 'rotate-180': isExpanded }"
          />
        </button>
      </div>
    </div>

    <!-- Expanded Content -->
    <Collapsible :open="isExpanded">
      <CollapsibleContent>
        <div class="border-t border-zinc-100 dark:border-zinc-800 px-4 py-4 space-y-4">
          <!-- Type Toggle -->
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-zinc-600 dark:text-zinc-400 mr-1">{{ $t('Klausimo tipas') }}</span>
            <Button
              type="button"
              :variant="localType === 'voting' ? 'default' : 'outline'"
              size="sm"
              class="h-8 px-3"
              @click="setType('voting')"
            >
              {{ $t('Balsavimas') }}
            </Button>
            <Button
              type="button"
              :variant="localType === 'deferred' ? 'default' : 'outline'"
              size="sm"
              class="h-8 px-3"
              @click="setType('deferred')"
            >
              {{ $t('Atidėtas') }}
            </Button>
            <Button
              type="button"
              :variant="localType === 'informational' ? 'default' : 'outline'"
              size="sm"
              class="h-8 px-3"
              @click="setType('informational')"
            >
              {{ $t('Informacinis') }}
            </Button>
            <!-- Clear type button -->
            <Button
              v-if="localType"
              type="button"
              variant="ghost"
              size="sm"
              class="h-8 w-8 p-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
              @click="clearType"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>

          <!-- Votes Section (available for all types, but main vote only for voting type) -->
          <div class="space-y-3">
            <!-- Vote Items -->
            <div
              v-for="(vote, voteIndex) in localVotes"
              :key="vote.id || `vote-${voteIndex}`"
              class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 space-y-3"
              :class="[
                vote.is_main ? 'border-primary/30 bg-primary/5' : '',
                vote.is_consensus ? 'border-teal-300 dark:border-teal-700 bg-teal-50 dark:bg-teal-900/20' : '',
              ]"
            >
              <!-- Vote Header -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div
                    class="h-2.5 w-2.5 rounded-full"
                    :class="getVoteStatusColor(vote)"
                  />
                  <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ $t('Balsavimas') }}
                  </span>
                  <Badge v-if="vote.is_main && localType === 'voting'" variant="secondary" class="text-[10px] px-1.5 py-0">
                    {{ $t('Pagrindinis') }}
                  </Badge>
                  <Badge
                    v-if="vote.is_consensus"
                    class="text-[10px] px-1.5 py-0 bg-teal-100 text-teal-700
                      dark:bg-teal-900/50 dark:text-teal-300 border-teal-200 dark:border-teal-700"
                  >
                    <Handshake class="h-3 w-3 mr-1" />
                    {{ $t('Bendras sutarimas') }}
                  </Badge>
                  <button
                    type="button"
                    class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    @click="showVoteTitleInput(voteIndex)"
                  >
                    {{ vote.title || $t('+ Pridėti pavadinimą') }}
                  </button>
                </div>
                <!-- Delete button: for voting type, main vote can only be deleted if it's the only vote -->
                <!-- For deferred type, any vote can be deleted -->
                <Button
                  v-if="canDeleteVote(vote)"
                  variant="ghost"
                  size="icon"
                  class="h-6 w-6 text-zinc-400 hover:text-destructive"
                  @click="removeVote(voteIndex)"
                >
                  <X class="h-3.5 w-3.5" />
                </Button>
              </div>
              
              <!-- Quick Consensus Toggle -->
              <div class="flex items-center gap-2 pb-1">
                <TooltipProvider :delay-duration="200">
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button
                        type="button"
                        :variant="vote.is_consensus ? 'default' : 'outline'"
                        size="sm"
                        class="h-8 gap-1.5"
                        :class="vote.is_consensus ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'hover:border-teal-400 hover:text-teal-600'"
                        @click="toggleConsensus(voteIndex)"
                      >
                        <Handshake class="h-3.5 w-3.5" />
                        {{ vote.is_consensus ? $t('Bendras sutarimas') : $t('Pažymėti kaip sutarimą') }}
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">
                      <p v-if="!vote.is_consensus">
                        {{ $t('Klausimas priimtas bendru sutarimu – automatiškai užpildys visus laukus') }}
                      </p>
                      <p v-else>
                        {{ $t('Pašalinti bendro sutarimo žymėjimą') }}
                      </p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              </div>

              <!-- Vote Title Input (conditional) -->
              <Input
                v-if="editingVoteTitle === voteIndex"
                v-model="vote.title"
                :placeholder="$t('Balsavimo pavadinimas')"
                class="h-8 text-sm"
                @blur="finishEditingVoteTitle"
                @keydown.enter="finishEditingVoteTitle"
              />

              <!-- Decision Row -->
              <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                <span class="text-xs text-zinc-600 dark:text-zinc-400 sm:w-44 shrink-0">{{ $t('Sprendimas') }}</span>
                <div class="flex flex-wrap items-center gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="['h-8 px-3', vote.decision === 'positive' && 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-400']"
                    @click="setVoteField(voteIndex, 'decision', vote.decision === 'positive' ? null : 'positive')"
                  >
                    <Check class="h-3 w-3 mr-1" />
                    {{ $t('Priimtas') }}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="['h-8 px-3', vote.decision === 'negative' && 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-400']"
                    @click="setVoteField(voteIndex, 'decision', vote.decision === 'negative' ? null : 'negative')"
                  >
                    <X class="h-3 w-3 mr-1" />
                    {{ $t('Nepriimtas') }}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="['h-8 px-3', vote.decision === 'neutral' && 'bg-zinc-100 border-zinc-300 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600']"
                    @click="setVoteField(voteIndex, 'decision', vote.decision === 'neutral' ? null : 'neutral')"
                  >
                    <Minus class="h-3 w-3 mr-1" />
                    {{ $t('Susilaikyta') }}
                  </Button>
                  <Button
                    v-if="vote.decision"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-zinc-400"
                    @click="setVoteField(voteIndex, 'decision', null)"
                  >
                    <X class="h-3 w-3" />
                  </Button>
                </div>
              </div>

              <!-- Student Vote Row -->
              <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                <span class="text-xs text-zinc-600 dark:text-zinc-400 sm:w-44 shrink-0">{{ $t('Kaip balsavo studentai') }}</span>
                <div class="flex flex-wrap items-center gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="['h-8 px-3', vote.student_vote === 'positive' && 'bg-green-100 border-green-300 text-green-700 dark:bg-green-900/40 dark:border-green-700 dark:text-green-400']"
                    @click="setVoteField(voteIndex, 'student_vote', vote.student_vote === 'positive' ? null : 'positive')"
                  >
                    <Check class="h-3 w-3 mr-1" />
                    {{ $t('Pritarė') }}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="['h-8 px-3', vote.student_vote === 'negative' && 'bg-red-100 border-red-300 text-red-700 dark:bg-red-900/40 dark:border-red-700 dark:text-red-400']"
                    @click="setVoteField(voteIndex, 'student_vote', vote.student_vote === 'negative' ? null : 'negative')"
                  >
                    <X class="h-3 w-3 mr-1" />
                    {{ $t('Nepritarė') }}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="['h-8 px-3', vote.student_vote === 'neutral' && 'bg-zinc-100 border-zinc-300 text-zinc-700 dark:bg-zinc-800 dark:border-zinc-600']"
                    @click="setVoteField(voteIndex, 'student_vote', vote.student_vote === 'neutral' ? null : 'neutral')"
                  >
                    <Minus class="h-3 w-3 mr-1" />
                    {{ $t('Susilaikė') }}
                  </Button>
                  <Button
                    v-if="vote.student_vote"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-zinc-400"
                    @click="setVoteField(voteIndex, 'student_vote', null)"
                  >
                    <X class="h-3 w-3" />
                  </Button>
                </div>
              </div>

              <!-- Student Benefit Row -->
              <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                <div class="flex items-center gap-1.5 sm:w-44 shrink-0">
                  <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $t('Palankumas studentams') }}</span>
                  <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-75"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-75"
                  >
                    <span
                      v-if="recentlyAutoUpdatedVotes.has(vote.id)"
                      class="inline-flex items-center gap-0.5 text-[10px] text-amber-600 dark:text-amber-400"
                    >
                      <Sparkles class="h-3 w-3" />
                    </span>
                  </Transition>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <TooltipProvider :delay-duration="200">
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <button
                          type="button"
                          class="inline-flex items-center gap-1 text-sm transition-colors"
                          :class="[
                            vote.student_benefit === 'positive'
                              ? 'text-green-600 dark:text-green-400 font-medium'
                              : 'text-zinc-400 hover:text-green-600 dark:hover:text-green-400',
                          ]"
                          @click="setVoteField(voteIndex, 'student_benefit', vote.student_benefit === 'positive' ? null : 'positive')"
                        >
                          <ThumbsUp class="h-3.5 w-3.5" />
                          <span>{{ $t('Palanku') }}</span>
                        </button>
                      </TooltipTrigger>
                      <TooltipContent side="bottom">
                        <p>{{ $t('Sprendimas yra palankus studentams') }}</p>
                      </TooltipContent>
                    </Tooltip>
                    <span class="text-zinc-300 dark:text-zinc-600">|</span>
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <button
                          type="button"
                          class="inline-flex items-center gap-1 text-sm transition-colors"
                          :class="[
                            vote.student_benefit === 'negative'
                              ? 'text-red-600 dark:text-red-400 font-medium'
                              : 'text-zinc-400 hover:text-red-600 dark:hover:text-red-400',
                          ]"
                          @click="setVoteField(voteIndex, 'student_benefit', vote.student_benefit === 'negative' ? null : 'negative')"
                        >
                          <ThumbsDown class="h-3.5 w-3.5" />
                          <span>{{ $t('Nepalanku') }}</span>
                        </button>
                      </TooltipTrigger>
                      <TooltipContent side="bottom">
                        <p>{{ $t('Sprendimas nėra palankus studentams') }}</p>
                      </TooltipContent>
                    </Tooltip>
                    <span class="text-zinc-300 dark:text-zinc-600">|</span>
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <button
                          type="button"
                          class="inline-flex items-center gap-1 text-sm transition-colors"
                          :class="[
                            vote.student_benefit === 'neutral'
                              ? 'text-zinc-700 dark:text-zinc-300 font-medium'
                              : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-400',
                          ]"
                          @click="setVoteField(voteIndex, 'student_benefit', vote.student_benefit === 'neutral' ? null : 'neutral')"
                        >
                          <Minus class="h-3.5 w-3.5" />
                          <span>{{ $t('Neutralu') }}</span>
                        </button>
                      </TooltipTrigger>
                      <TooltipContent side="bottom">
                        <p>{{ $t('Sprendimas neturi aiškaus poveikio studentams') }}</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
              </div>
            </div>

            <!-- Add Vote Button -->
            <button
              type="button"
              class="w-full py-2 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 border border-dashed border-zinc-300 dark:border-zinc-700 rounded-lg hover:border-zinc-400 dark:hover:border-zinc-600 transition-colors flex items-center justify-center gap-1.5"
              @click="addVote"
            >
              <Plus class="h-3.5 w-3.5" />
              {{ $t('Pridėti balsavimą') }}
            </button>
          </div>

          <!-- Brought by Students Toggle -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            <span class="text-xs text-zinc-600 dark:text-zinc-400 sm:w-48 shrink-0">{{ $t('Studentų klausimas') }}</span>
            <Switch
              :model-value="localBroughtByStudents"
              @update:model-value="(val: boolean) => { localBroughtByStudents = val; saveChanges(); }"
            />
          </div>

          <!-- Student Position Row (above description) -->
          <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-3">
            <span class="text-xs text-zinc-600 dark:text-zinc-400 sm:w-48 shrink-0 pt-2">
              <span class="font-semibold">{{ $t('Išsakyta') }}</span> {{ $t('studentų pozicija') }}
            </span>
            <div class="flex-1">
              <Textarea
                v-if="editingStudentPosition"
                v-model="localStudentPosition"
                :placeholder="$t('Įveskite studentų poziciją...')"
                class="min-h-20 text-sm"
                @blur="finishEditingStudentPosition"
              />
              <button
                v-else
                type="button"
                class="w-full text-left text-sm px-3 py-2 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 transition-colors"
                :class="localStudentPosition ? 'text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/50' : 'text-zinc-400'"
                @click="editingStudentPosition = true"
              >
                <span v-if="localStudentPosition">{{ localStudentPosition }}</span>
                <span v-else class="flex items-center gap-1">
                  <Plus class="h-3 w-3" />
                  {{ $t('Pridėti studentų poziciją') }}
                </span>
              </button>
            </div>
          </div>

          <!-- Description Row -->
          <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-3">
            <span class="text-xs text-zinc-600 dark:text-zinc-400 sm:w-48 shrink-0 pt-2">{{ $t('Aprašymas') }}</span>
            <div class="flex-1">
              <Textarea
                v-if="editingDescription"
                v-model="localDescription"
                :placeholder="$t('Įveskite aprašymą...')"
                class="min-h-20 text-sm"
                @blur="finishEditingDescription"
              />
              <button
                v-else
                type="button"
                class="w-full text-left text-sm px-3 py-2 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 transition-colors"
                :class="localDescription ? 'text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/50' : 'text-zinc-400'"
                @click="editingDescription = true"
              >
                <span v-if="localDescription">{{ localDescription }}</span>
                <span v-else class="flex items-center gap-1">
                  <Plus class="h-3 w-3" />
                  {{ $t('Pridėti aprašymą') }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </CollapsibleContent>
    </Collapsible>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';
import {
  GripVertical,
  ChevronDown,
  MoreVertical,
  Trash2,
  Plus,
  Check,
  X,
  Minus,
  ThumbsUp,
  ThumbsDown,
  Sparkles,
  Handshake,
} from 'lucide-vue-next';

import VoteSelectionBadge from './VoteSelectionBadge.vue';

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { useAgendaItemStyling } from '@/Composables/useAgendaItemStyling';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Collapsible, CollapsibleContent } from '@/Components/ui/collapsible';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip';

// Simple ID generator for temporary votes
const generateTempId = () => `temp_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;

interface AgendaItem {
  id: string;
  title: string;
  description?: string | null;
  order: number;
  brought_by_students?: boolean;
  type?: 'voting' | 'informational' | 'deferred' | null;
  student_position?: string | null;
  votes?: (App.Entities.Vote & { is_consensus?: boolean })[];
}

interface Props {
  item: AgendaItem;
  order: number;
  expanded?: boolean;
}

interface Emits {
  (e: 'delete', item: AgendaItem): void;
  (e: 'update', item: AgendaItem): void;
  (e: 'toggle-expand', id: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  expanded: false,
});

const emit = defineEmits<Emits>();

// Local state
const isExpanded = ref(props.expanded);
const editingDescription = ref(false);
const editingStudentPosition = ref(false);
const editingVoteTitle = ref<number | null>(null);
const recentlyAutoUpdatedVotes = ref<Set<string>>(new Set());

// Local editable copies - keep null type as null (requires user selection)
const localType = ref<'voting' | 'informational' | 'deferred' | null>(props.item.type ?? null);
const localDescription = ref(props.item.description || '');
const localStudentPosition = ref(props.item.student_position || '');
const localBroughtByStudents = ref(props.item.brought_by_students || false);
const localVotes = ref<App.Entities.Vote[]>([...(props.item.votes || [])]);

// Watch for prop changes
watch(() => props.item, (newItem) => {
  localType.value = newItem.type ?? null;
  localDescription.value = newItem.description || '';
  localStudentPosition.value = newItem.student_position || '';
  localBroughtByStudents.value = newItem.brought_by_students || false;
  localVotes.value = [...(newItem.votes || [])];
}, { deep: true });

watch(() => props.expanded, (newVal) => {
  isExpanded.value = newVal;
});

// Use composable for styling
const styling = useAgendaItemStyling();

// Computed
const displayVotes = computed(() => {
  // Show main vote first, then up to 2 more
  const mainVote = localVotes.value.find(v => v.is_main);
  const others = localVotes.value.filter(v => !v.is_main).slice(0, 2);
  return mainVote ? [mainVote, ...others] : others;
});

const additionalVotesCount = computed(() => {
  const shown = displayVotes.value.length;
  return Math.max(0, localVotes.value.length - shown);
});

// Create a reactive "fake" item for composable functions
const itemForStyling = computed(() => ({
  ...props.item,
  type: localType.value,
  votes: localVotes.value,
}));

const numberBadgeClass = computed(() => styling.getNumberBadgeClass(itemForStyling.value as App.Entities.AgendaItem));
const statusText = computed(() => styling.getStatusText(itemForStyling.value as App.Entities.AgendaItem));
const statusTextClass = computed(() => styling.getStatusTextClass(itemForStyling.value as App.Entities.AgendaItem));

// Methods
const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value;
  emit('toggle-expand', props.item.id);
};

/**
 * Clear the type selection.
 * Removes is_main from all votes but keeps the votes themselves.
 */
const clearType = () => {
  localType.value = null;
  // Remove is_main from all votes when clearing type
  localVotes.value.forEach((v) => {
    v.is_main = false;
  });
  saveChanges();
};

const setType = (type: 'voting' | 'informational' | 'deferred') => {
  localType.value = type;

  // When switching to voting type, ensure there's a main vote
  if (type === 'voting') {
    const hasMainVote = localVotes.value.some(v => v.is_main);
    if (!hasMainVote) {
      // If there are existing votes, promote the first one to main
      if (localVotes.value.length > 0 && localVotes.value[0]) {
        localVotes.value[0].is_main = true;
      }
      else {
        // Create an empty main vote
        const newVote: App.Entities.Vote = {
          id: generateTempId(),
          agenda_item_id: props.item.id,
          is_main: true,
          title: null,
          student_vote: null,
          decision: null,
          student_benefit: null,
          note: null,
          order: 0,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        };
        localVotes.value.unshift(newVote);
      }
    }
  }
  // When switching away from voting type, remove main vote status
  else {
    localVotes.value.forEach((v) => {
      v.is_main = false;
    });
  }

  saveChanges();
};

const showVoteTitleInput = (index: number) => {
  editingVoteTitle.value = index;
};

/**
 * Infer student_benefit based on student_vote and decision.
 * Logic: If the decision matches what students voted for, it's positive for students.
 * If the decision opposes what students voted for, it's negative.
 * If either is neutral, the benefit is neutral.
 */
const inferStudentBenefit = (
  studentVote: string | null,
  decision: string | null,
): 'positive' | 'negative' | 'neutral' | null => {
  if (!studentVote || !decision) return null;

  // If either is neutral, the benefit is neutral
  if (studentVote === 'neutral' || decision === 'neutral') {
    return 'neutral';
  }

  // If students voted positive and decision is positive, it's good for students
  // If students voted negative and decision is negative, it's also good (their opposition succeeded)
  if (studentVote === decision) {
    return 'positive';
  }

  // If they don't match, decision went against student wishes
  return 'negative';
};

const setVoteField = (
  voteIndex: number,
  field: 'decision' | 'student_vote' | 'student_benefit',
  value: 'positive' | 'negative' | 'neutral' | null,
) => {
  const vote = localVotes.value[voteIndex];
  if (!vote) return;

  vote[field] = value;

  // If user manually changes decision or student_vote, clear consensus status
  // (consensus means all values were auto-set, manual override breaks that)
  if ((field === 'decision' || field === 'student_vote') && vote.is_consensus) {
    vote.is_consensus = false;
  }

  // Auto-update student_benefit when both student_vote and decision are set
  // Only auto-update if we're changing student_vote or decision (not student_benefit itself)
  if (field !== 'student_benefit') {
    const newStudentVote = field === 'student_vote' ? value : vote.student_vote;
    const newDecision = field === 'decision' ? value : vote.decision;

    if (newStudentVote && newDecision) {
      const inferredBenefit = inferStudentBenefit(newStudentVote, newDecision);

      // Only update if the inferred value is different from current
      if (inferredBenefit && inferredBenefit !== vote.student_benefit) {
        vote.student_benefit = inferredBenefit;

        // Show visual indicator
        const newSet = new Set(recentlyAutoUpdatedVotes.value);
        newSet.add(vote.id);
        recentlyAutoUpdatedVotes.value = newSet;

        // Show toast notification
        toast.info($t('Palankumas studentams atnaujintas automatiškai'), {
          duration: 2000,
        });

        // Remove indicator after animation
        setTimeout(() => {
          const updatedSet = new Set(recentlyAutoUpdatedVotes.value);
          updatedSet.delete(vote.id);
          recentlyAutoUpdatedVotes.value = updatedSet;
        }, 2500);
      }
    }
  }

  saveChanges();
};

const addVote = () => {
  // Only for voting type and when no votes exist, the first vote becomes main
  // For deferred/informational types, votes are never main
  const isMain = localType.value === 'voting' && localVotes.value.length === 0;
  const newVote: App.Entities.Vote & { is_consensus?: boolean } = {
    id: generateTempId(),
    agenda_item_id: props.item.id,
    is_main: isMain,
    is_consensus: false,
    title: null,
    student_vote: null,
    decision: null,
    student_benefit: null,
    note: null,
    order: localVotes.value.length,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  };
  localVotes.value.push(newVote);
  saveChanges();
};

/**
 * Toggle consensus status for a vote.
 * When enabling consensus, auto-fill all vote fields with positive values.
 * When disabling, clear the fields to allow manual entry.
 */
const toggleConsensus = (voteIndex: number) => {
  const vote = localVotes.value[voteIndex];
  if (!vote) return;

  if (vote.is_consensus) {
    // Disable consensus - clear the fields
    vote.is_consensus = false;
    vote.decision = null;
    vote.student_vote = null;
    vote.student_benefit = null;
  }
  else {
    // Enable consensus - auto-fill with positive values
    vote.is_consensus = true;
    vote.decision = 'positive';
    vote.student_vote = 'positive';
    vote.student_benefit = 'positive';

    toast.success($t('Klausimas pažymėtas kaip priimtas bendru sutarimu'), {
      duration: 2000,
    });
  }

  saveChanges();
};

const removeVote = (index: number) => {
  const wasMain = localVotes.value[index]?.is_main;
  localVotes.value.splice(index, 1);
  // If we removed the main vote (voting type only), make first remaining vote main
  // For deferred/informational, there's no main vote concept
  if (wasMain && localType.value === 'voting' && localVotes.value.length > 0 && localVotes.value[0]) {
    localVotes.value[0].is_main = true;
  }
  saveChanges();
};

/**
 * Determine if a vote can be deleted.
 * - For deferred/informational type: any vote can be deleted (no main vote concept)
 * - For voting type: main vote can only be deleted if it's the only vote
 *   (additional votes can always be deleted)
 */
const canDeleteVote = (vote: App.Entities.Vote): boolean => {
  // For deferred/informational type, any vote can be deleted
  if (localType.value === 'deferred' || localType.value === 'informational') {
    return true;
  }

  // For voting type:
  // - If this is NOT the main vote, it can be deleted
  // - If this IS the main vote, it can only be deleted if it's the only vote
  if (vote.is_main) {
    return localVotes.value.length === 1;
  }

  return true;
};

const finishEditingDescription = () => {
  editingDescription.value = false;
  saveChanges();
};

const finishEditingVoteTitle = () => {
  editingVoteTitle.value = null;
  saveChanges();
};

const finishEditingStudentPosition = () => {
  editingStudentPosition.value = false;
  saveChanges();
};

// Use composable for vote status color instead of duplicating logic
const getVoteStatusColor = (vote: App.Entities.Vote) => {
  return styling.getVoteStatusDotClass(vote);
};

// Save changes to backend
const saveChanges = () => {
  const payload = {
    type: localType.value,
    description: localDescription.value || null,
    student_position: localStudentPosition.value || null,
    brought_by_students: localBroughtByStudents.value,
    votes: localVotes.value.map((vote, index) => ({
      id: vote.id?.startsWith('temp_') ? null : vote.id,
      is_main: vote.is_main,
      is_consensus: vote.is_consensus || false,
      title: vote.title,
      student_vote: vote.student_vote,
      decision: vote.decision,
      student_benefit: vote.student_benefit,
      note: vote.note,
      order: index,
    })),
  };

  router.patch(route('agendaItems.update', props.item.id), payload, {
    preserveScroll: true,
    only: ['meeting'],
  });
};
</script>

<style scoped>
.sortable-ghost {
  opacity: 0.4;
}

.sortable-chosen {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
