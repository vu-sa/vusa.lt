<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>{{ $t('Apie posėdžių skaidrumą') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Kodėl ir kaip rodomos balsavimo detalės') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6">
        <!-- Why we publish -->
        <section>
          <h3 class="font-semibold mb-2 text-base">{{ $t('Kodėl skelbiame?') }}</h3>
          <p class="text-sm text-muted-foreground">
            {{ $t('VU SA užtikrina skaidrumą viešindama, kaip studentų atstovai balsuoja...') }}
          </p>
        </section>

        <!-- What each field means -->
        <section>
          <h3 class="font-semibold mb-3 text-base">{{ $t('Ką reiškia kiekvienas laukas?') }}</h3>
          <dl class="space-y-4">
            <!-- Student vote -->
            <div class="flex flex-col sm:flex-row sm:gap-4">
              <dt class="font-medium mb-1 sm:mb-0 sm:min-w-[140px] flex items-center gap-2">
                <VoteIndicator vote="positive" type="vote" />
                <span>{{ $t('Studentų balsas') }}</span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('Kaip balsavo studentų atstovas ar atstovai') }}
              </dd>
            </div>

            <!-- Decision -->
            <div class="flex flex-col sm:flex-row sm:gap-4">
              <dt class="font-medium mb-1 sm:mb-0 sm:min-w-[140px] flex items-center gap-2">
                <VoteIndicator vote="positive" type="vote" />
                <span>{{ $t('Sprendimas') }}</span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('Galutinis viso organo sprendimas') }}
              </dd>
            </div>

            <!-- Student benefit -->
            <div class="flex flex-col sm:flex-row sm:gap-4">
              <dt class="font-medium mb-1 sm:mb-0 sm:min-w-[140px] flex items-center gap-2">
                <VoteIndicator vote="positive" type="benefit" />
                <span>{{ $t('Nauda studentams') }}</span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('VU SA įvertinimas, ar sprendimas naudingas studentams') }}
              </dd>
            </div>

            <!-- Brought by students -->
            <div class="flex flex-col sm:flex-row sm:gap-4">
              <dt class="font-medium mb-1 sm:mb-0 sm:min-w-[140px] flex items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 ring-1 ring-zinc-200 dark:ring-zinc-700">
                  <UsersIcon class="h-3 w-3" />
                </span>
                <span>{{ $t('Įtraukta studentų') }}</span>
              </dt>
              <dd class="text-sm text-muted-foreground">
                {{ $t('Klausimas, kurį į posėdžio darbotvarkę įtraukė studentų atstovai') }}
              </dd>
            </div>
          </dl>
        </section>

        <!-- Symbol meanings -->
        <section>
          <h3 class="font-semibold mb-3 text-base">{{ $t('Simbolių reikšmės') }}</h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="flex items-center gap-2">
              <VoteIndicator vote="positive" type="vote" />
            </div>
            <div class="flex items-center gap-2">
              <VoteIndicator vote="negative" type="vote" />
            </div>
            <div class="flex items-center gap-2">
              <VoteIndicator vote="neutral" type="vote" />
            </div>
          </div>

          <!-- Benefit icons -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3 pt-3 border-t">
            <div class="flex items-center gap-2">
              <VoteIndicator vote="positive" type="benefit" />
              <span class="text-xs text-muted-foreground">({{ $t('nauda') }})</span>
            </div>
            <div class="flex items-center gap-2">
              <VoteIndicator vote="negative" type="benefit" />
              <span class="text-xs text-muted-foreground">({{ $t('nauda') }})</span>
            </div>
            <div class="flex items-center gap-2">
              <VoteIndicator vote="neutral" type="benefit" />
              <span class="text-xs text-muted-foreground">({{ $t('nauda') }})</span>
            </div>
          </div>
        </section>
      </div>

      <DialogFooter>
        <Button @click="$emit('update:open', false)">
          {{ $t('Supratau') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Users as UsersIcon } from 'lucide-vue-next';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import VoteIndicator from './VoteIndicator.vue';

defineProps<{
  open: boolean;
}>();

defineEmits<{
  'update:open': [value: boolean];
}>();
</script>
