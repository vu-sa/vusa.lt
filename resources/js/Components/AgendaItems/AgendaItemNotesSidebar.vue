<template>
  <aside class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/40">
    <div class="flex items-center justify-between gap-2 border-b border-zinc-200 dark:border-zinc-800 px-4 py-3">
      <div class="flex items-center gap-2">
        <Users class="h-4 w-4 text-zinc-500 dark:text-zinc-400" />
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
          {{ $t('Atstovų pastabos') }}
        </h3>
      </div>
      <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400 ring-1 ring-amber-200/70 dark:ring-amber-900/50">
        <Lock class="h-3 w-3" />
        {{ $t('PRIVATU') }}
      </span>
    </div>

    <div class="px-4 py-6 text-center">
      <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
        <MessageSquare class="h-5 w-5 text-zinc-400 dark:text-zinc-500" />
      </div>
      <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">
        {{ $t('Greitai') }}
      </p>
      <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
        {{ $t('Bendros studentų atstovų pastabos — matomos visai komandai, niekada viešai.') }}
      </p>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Lock, MessageSquare, Users } from 'lucide-vue-next';
</script>
