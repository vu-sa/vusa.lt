<template>
  <div>
    <Button
      variant="ghost"
      size="sm"
      voice="sentence"
      class="text-muted-foreground pointer-coarse:h-11"
      @click="showModal = true"
    >
      <HelpCircle class="size-4" />
      {{ $t('Pagalba') }}
    </Button>

    <Dialog v-model:open="showModal">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{{ $t('voting.help_title') }}</DialogTitle>
          <DialogDescription>
            {{ $t('voting.help_description') }}
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-8">
          <section class="space-y-2">
            <h3 :class="HEADING_CLASS">
              {{ $t('voting.help_why_important') }}
            </h3>
            <p class="text-sm text-muted-foreground">
              {{ $t('voting.help_why_important_text') }}
            </p>
          </section>

          <section class="space-y-3">
            <h3 :class="HEADING_CLASS">
              {{ $t('Ką reiškia kiekvienas laukas?') }}
            </h3>
            <dl class="divide-y divide-border border-y border-border">
              <div v-for="item in voteFieldExplanations" :key="item.field" class="grid gap-1 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4">
                <dt class="text-sm font-medium text-foreground">
                  {{ $t(item.label) }}
                </dt>
                <dd class="text-sm text-muted-foreground">
                  {{ $t(item.admin) }}
                </dd>
              </div>
              <div class="grid gap-1 py-3 sm:grid-cols-[10rem_1fr] sm:gap-4">
                <dt class="text-sm font-medium text-foreground">
                  {{ $t('Aprašymas') }}
                </dt>
                <dd class="text-sm text-muted-foreground">
                  {{ $t('voting.field_description_tooltip') }}
                </dd>
              </div>
            </dl>
          </section>

          <section class="space-y-3">
            <h3 :class="HEADING_CLASS">
              {{ $t('voting.help_tips_title') }}
            </h3>
            <ul class="space-y-2">
              <li v-for="tip in TIPS" :key="tip" class="flex items-start gap-2 text-sm text-muted-foreground">
                <Check class="mt-0.5 size-4 shrink-0 text-status-success" />
                {{ $t(tip) }}
              </li>
            </ul>
          </section>

          <section class="space-y-3">
            <h3 :class="HEADING_CLASS">
              {{ $t('Darbotvarkės klausimų būsenos') }}
            </h3>
            <p class="text-sm text-muted-foreground">
              {{ $t('voting.help_agenda_status_description') }}
            </p>
            <dl class="divide-y divide-border border-y border-border">
              <div v-for="item in agendaStatusExplanations" :key="item.status" class="grid gap-2 py-3 sm:grid-cols-[14rem_1fr] sm:gap-4">
                <dt>
                  <StatusBadge :status="agendaItemStatuses[item.status]" />
                </dt>
                <dd class="text-sm text-muted-foreground">
                  {{ $t(item.admin) }}
                </dd>
              </div>
            </dl>
          </section>

          <p class="flex items-center gap-2 border-y border-border py-3 text-sm text-muted-foreground">
            <MessageCircle class="size-4 shrink-0" />
            {{ $t('voting.help_contact') }}
          </p>
        </div>

        <DialogFooter>
          <Button variant="brand" @click="showModal = false">
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
import { Check, HelpCircle, MessageCircle } from 'lucide-vue-next';

import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { agendaItemStatuses } from '@/Constants/statuses';
import { agendaStatusExplanations, voteFieldExplanations } from '@/Constants/votingExplainer';

const HEADING_CLASS = 'text-xs font-bold uppercase tracking-[0.18em] text-foreground';
const TIPS = ['voting.help_tip_1', 'voting.help_tip_2', 'voting.help_tip_3'];

const showModal = ref(false);
</script>
