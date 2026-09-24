<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ $t('Apie posėdžių skaidrumą') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Kodėl ir kaip rodomos balsavimo detalės') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-8">
        <section class="space-y-2">
          <h3 :class="HEADING_CLASS">
            {{ $t('Kodėl skelbiame?') }}
          </h3>
          <p class="text-sm text-muted-foreground">
            {{ $t('VU SA užtikrina skaidrumą viešindama, kaip studentų atstovai balsuoja...') }}
          </p>
        </section>

        <section class="space-y-3">
          <h3 :class="HEADING_CLASS">
            {{ $t('Ką reiškia kiekvienas laukas?') }}
          </h3>
          <dl class="divide-y divide-border border-y border-border">
            <div v-for="item in voteFieldExplanations" :key="item.field" class="grid gap-1 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4">
              <dt class="flex items-center gap-2 text-sm font-medium text-foreground">
                <VoteStatusIndicator vote="positive" :type="item.field === 'student_benefit' ? 'benefit' : 'vote'" compact />
                {{ $t(item.label) }}
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t(item.public) }}
              </dd>
            </div>
            <div class="grid gap-1 py-3 sm:grid-cols-[12rem_1fr] sm:gap-4">
              <dt class="flex items-center gap-2 text-sm font-medium text-foreground">
                <IFluentPeople24Regular class="size-3.5 text-muted-foreground" aria-hidden="true" />
                {{ $t('Įtraukta studentų') }}
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('Klausimas, kurį į posėdžio darbotvarkę įtraukė studentų atstovai') }}
              </dd>
            </div>
          </dl>
        </section>

        <section class="space-y-3">
          <h3 :class="HEADING_CLASS">
            {{ $t('Simbolių reikšmės') }}
          </h3>
          <div class="grid gap-3 sm:grid-cols-3">
            <VoteStatusIndicator v-for="value in VALUES" :key="value" :vote="value" type="vote" />
          </div>
          <div class="grid gap-3 border-t border-border pt-3 sm:grid-cols-3">
            <span v-for="value in VALUES" :key="value" class="flex items-center gap-2">
              <VoteStatusIndicator :vote="value" type="benefit" />
              <span class="text-xs text-muted-foreground">({{ $t('nauda') }})</span>
            </span>
          </div>
        </section>

        <section class="space-y-3">
          <h3 :class="HEADING_CLASS">
            {{ $t('Darbotvarkės klausimų būsenos') }}
          </h3>
          <p class="text-sm text-muted-foreground">
            {{ $t('Kiekvienas klausimas rodomas pagal jo būseną ir balsavimo rezultatą') }}
          </p>
          <dl class="divide-y divide-border border-y border-border">
            <div v-for="item in agendaStatusExplanations" :key="item.status" class="grid gap-2 py-3 sm:grid-cols-[16rem_1fr] sm:gap-4">
              <dt>
                <span
                  :class="['inline-flex items-center gap-1.5 border px-2 py-1 text-xs font-bold', statusRoleClasses[agendaItemStatuses[item.status].role]]"
                >
                  {{ $t(agendaItemStatuses[item.status].label) }}
                </span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t(item.public) }}
              </dd>
            </div>
          </dl>
        </section>
      </div>

      <DialogFooter>
        <Button variant="brand" @click="$emit('update:open', false)">
          {{ $t('Supratau') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import VoteStatusIndicator from './VoteStatusIndicator.vue';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { agendaItemStatuses, statusRoleClasses } from '@/Constants/statuses';
import { agendaStatusExplanations, voteFieldExplanations } from '@/Constants/votingExplainer';
import IFluentPeople24Regular from '~icons/fluent/people-24-regular';

const HEADING_CLASS = 'text-xs font-bold uppercase tracking-[0.18em] text-foreground';
const VALUES = ['positive', 'negative', 'neutral'] as const;

defineProps<{
  open: boolean;
}>();

defineEmits<{
  'update:open': [value: boolean];
}>();
</script>
