<template>
  <div>
    <!-- Desktop: Tooltip with modal trigger -->
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger as-child>
          <Button variant="ghost" size="sm"
            class="h-6 px-2 align-middle text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
            @click="showModal = true">
            <HelpCircle class="h-3.5 w-3.5 mr-1" />
            <span>{{ $t('Pagalba') }}</span>
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          {{ $t('Sužinokite daugiau apie balsavimo duomenis') }}
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>

    <!-- Help Modal -->
    <Dialog v-model:open="showModal">
      <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ $t('voting.help_title') }}</DialogTitle>
          <DialogDescription>
            {{ $t('voting.help_description') }}
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-6 mt-4">
          <!-- Why it's important -->
          <section>
            <h3 class="font-semibold mb-2 text-base flex items-center gap-2">
              <Info class="h-4 w-4 text-primary" />
              {{ $t('voting.help_why_important') }}
            </h3>
            <p class="text-sm text-muted-foreground">
              {{ $t('voting.help_why_important_text') }}
            </p>
          </section>

          <!-- Field explanations -->
          <section>
            <h3 class="font-semibold mb-3 text-base">
              {{ $t('Ką reiškia kiekvienas laukas?') }}
            </h3>
            <dl class="space-y-4">
              <!-- Decision -->
              <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                <dt class="font-medium mb-1 flex items-center gap-2 text-sm">
                  <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center">
                    <Check class="h-3 w-3 text-primary" />
                  </div>
                  {{ $t('voting.field_decision_label') }}
                </dt>
                <dd class="text-sm text-muted-foreground ml-8">
                  {{ $t('voting.field_decision_tooltip') }}
                </dd>
              </div>

              <!-- Student vote -->
              <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                <dt class="font-medium mb-1 flex items-center gap-2 text-sm">
                  <div class="w-6 h-6 rounded-full bg-blue-500/10 flex items-center justify-center">
                    <Users class="h-3 w-3 text-blue-600" />
                  </div>
                  {{ $t('voting.field_student_vote_label') }}
                </dt>
                <dd class="text-sm text-muted-foreground ml-8">
                  {{ $t('voting.field_student_vote_tooltip') }}
                </dd>
              </div>

              <!-- Student benefit -->
              <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                <dt class="font-medium mb-1 flex items-center gap-2 text-sm">
                  <div class="w-6 h-6 rounded-full bg-green-500/10 flex items-center justify-center">
                    <ThumbsUp class="h-3 w-3 text-green-600" />
                  </div>
                  {{ $t('voting.field_student_benefit_label') }}
                </dt>
                <dd class="text-sm text-muted-foreground ml-8">
                  {{ $t('voting.field_student_benefit_tooltip') }}
                </dd>
              </div>

              <!-- Description -->
              <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
                <dt class="font-medium mb-1 flex items-center gap-2 text-sm">
                  <div class="w-6 h-6 rounded-full bg-orange-500/10 flex items-center justify-center">
                    <FileText class="h-3 w-3 text-orange-600" />
                  </div>
                  {{ $t('Aprašymas') }}
                </dt>
                <dd class="text-sm text-muted-foreground ml-8">
                  {{ $t('voting.field_description_tooltip') }}
                </dd>
              </div>
            </dl>
          </section>

          <!-- Tips -->
          <section>
            <h3 class="font-semibold mb-3 text-base flex items-center gap-2">
              <Lightbulb class="h-4 w-4 text-yellow-500" />
              {{ $t('voting.help_tips_title') }}
            </h3>
            <ul class="space-y-2">
              <li class="flex items-start gap-2 text-sm text-muted-foreground">
                <Check class="h-4 w-4 text-green-500 mt-0.5 shrink-0" />
                {{ $t('voting.help_tip_1') }}
              </li>
              <li class="flex items-start gap-2 text-sm text-muted-foreground">
                <Check class="h-4 w-4 text-green-500 mt-0.5 shrink-0" />
                {{ $t('voting.help_tip_2') }}
              </li>
              <li class="flex items-start gap-2 text-sm text-muted-foreground">
                <Check class="h-4 w-4 text-green-500 mt-0.5 shrink-0" />
                {{ $t('voting.help_tip_3') }}
              </li>
            </ul>
          </section>

          <!-- Symbol meanings -->
          <section>
            <h3 class="font-semibold mb-3 text-base">
              {{ $t('Simbolių reikšmės') }}
            </h3>
            <div class="grid grid-cols-3 gap-3">
              <div class="flex flex-col items-center gap-2 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                  <Check class="h-4 w-4 text-green-600" />
                </div>
                <span class="text-xs text-center text-muted-foreground">{{ $t('Priimtas') }} / {{ $t('Pritarė') }} / {{
                  $t('Palanku') }}</span>
              </div>
              <div class="flex flex-col items-center gap-2 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                  <X class="h-4 w-4 text-red-600" />
                </div>
                <span class="text-xs text-center text-muted-foreground">{{ $t('Nepriimtas') }} / {{ $t('Nepritarė') }} /
                  {{ $t('Nepalanku') }}</span>
              </div>
              <div class="flex flex-col items-center gap-2 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                  <Minus class="h-4 w-4 text-zinc-600" />
                </div>
                <span class="text-xs text-center text-muted-foreground">{{ $t('Susilaikyta') }} / {{ $t('Susilaikė') }}
                  / {{ $t('Neutralu') }}</span>
              </div>
            </div>
          </section>

          <!-- Contact info -->
          <section class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <p class="text-sm text-blue-800 dark:text-blue-300 flex items-center gap-2">
              <MessageCircle class="h-4 w-4" />
              {{ $t('voting.help_contact') }}
            </p>
          </section>
        </div>

        <DialogFooter>
          <Button @click="showModal = false">
            {{ $t('Supratau') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  HelpCircle,
  Info,
  Check,
  X,
  Minus,
  ThumbsUp,
  FileText,
  Lightbulb,
  MessageCircle,
  Users
} from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/Components/ui/tooltip';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

const showModal = ref(false);
</script>
