<template>
  <OverviewPage :eyebrow="$t('shell.workspaces.sistema.title')" :title="$t('Sistemos būsena')" :lead="$t('Pagrindinių paslaugų ir integracijų būsena.')">
    <template #actions>
      <Button variant="outline" class="u-touch" @click="router.reload({ only: ['status', 'deviceMetrics', 'lastUpdated'] })">
        <RefreshCw class="size-4" />
        {{ $t('Atnaujinti') }}
      </Button>
    </template>

    <Deferred data="systemStatus">
      <template #fallback>
        <div class="grid gap-px border border-border bg-border sm:grid-cols-2 xl:grid-cols-3">
          <Skeleton v-for="index in 6" :key="index" class="h-32 bg-card" />
        </div>
      </template>

      <template v-if="status">
        <section class="grid gap-px border border-border bg-border sm:grid-cols-2 xl:grid-cols-3">
          <article v-for="check in checks" :key="check.key" class="bg-card p-5">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h2 class="font-semibold text-foreground">{{ check.label }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ check.summary }}</p>
              </div>
              <StatusBadge :status="check.presentation" />
            </div>
            <dl v-if="check.details.length" class="mt-5 divide-y divide-border border-y border-border text-sm">
              <div v-for="detail in check.details" :key="detail.key" class="flex justify-between gap-4 py-2">
                <dt class="text-muted-foreground">{{ detail.key }}</dt>
                <dd class="max-w-48 truncate text-right font-medium text-foreground">{{ detail.value }}</dd>
              </div>
            </dl>
          </article>
        </section>
        <p class="text-xs text-muted-foreground">{{ $t('Paskutinis atnaujinimas') }}: {{ formattedUpdatedAt }}</p>
      </template>
    </Deferred>
  </OverviewPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Deferred, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleCheck, CircleDashed, CircleX, RefreshCw, TriangleAlert } from 'lucide-vue-next';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import type { StatusPresentation } from '@/Constants/statuses';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { formatNearDate } from '@/Utils/dateTime';

type ServiceStatus = Record<string, unknown> & { status?: string; error?: string; message?: string };
const props = defineProps<{ status?: Record<string, ServiceStatus>; lastUpdated: string; deviceMetrics?: unknown }>();
const labels: Record<string, string> = { redis: 'Redis', database: 'Duomenų bazė', cache: 'Talpykla', typesense: 'Typesense', scheduler: 'Planuoklė', digest: 'Laiškų eilė', mail: 'El. paštas', system: 'Sistema', integrations: 'Integracijos' };
const present = (status?: string): StatusPresentation => {
  if (['healthy', 'connected', 'working', 'configured'].includes(status ?? '')) return { label: $t('Veikia'), role: 'success', icon: CircleCheck };
  if (['warning', 'degraded'].includes(status ?? '')) return { label: $t('Reikia dėmesio'), role: 'attention', icon: TriangleAlert };
  if (['error', 'failed', 'disconnected'].includes(status ?? '')) return { label: $t('Neveikia'), role: 'danger', icon: CircleX };
  return { label: $t('Nežinoma'), role: 'neutral', icon: CircleDashed };
};
const scalarDetails = (check: ServiceStatus) => Object.entries(check)
  .filter(([key, value]) => !['status', 'error', 'message'].includes(key) && ['string', 'number', 'boolean'].includes(typeof value))
  .slice(0, 4)
  .map(([key, value]) => ({ key: key.replaceAll('_', ' '), value: String(value) }));
const checks = computed(() => Object.entries(props.status ?? {}).map(([key, value]) => ({
  key,
  label: labels[key] ?? key.replaceAll('_', ' '),
  summary: value.error ?? value.message ?? value.status ?? $t('Duomenų nėra'),
  presentation: present(value.status),
  details: scalarDetails(value),
})));
const formattedUpdatedAt = computed(() => formatNearDate(props.lastUpdated));
usePageBreadcrumbs(BreadcrumbHelpers.adminForm($t('Sistema'), 'systemStatus', $t('Sistemos būsena')));
</script>
