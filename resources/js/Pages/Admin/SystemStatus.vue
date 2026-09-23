<template>
  <OverviewPage :eyebrow="$t('shell.workspaces.sistema.title')" :title="$t('Sistemos būsena')" :lead="$t('Pagrindinių paslaugų ir integracijų būsena.')">
    <template #actions>
      <Button variant="outline" class="u-touch" @click="router.reload({ only: ['status', 'deviceMetrics', 'lastUpdated'] })">
        <RefreshCw class="size-4" />
        {{ $t('Atnaujinti') }}
      </Button>
    </template>

    <template v-if="status">
      <section class="grid gap-px border border-border bg-border sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="check in checks" :key="check.key" class="bg-card p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="font-semibold text-foreground">
                {{ check.label }}
              </h2>
              <p class="mt-1 text-sm text-muted-foreground">
                {{ check.summary }}
              </p>
            </div>
            <StatusBadge :status="check.presentation" />
          </div>
          <dl v-if="check.details.length" class="mt-5 divide-y divide-border border-y border-border text-sm">
            <div v-for="detail in check.details" :key="detail.key" class="flex justify-between gap-4 py-2">
              <dt class="text-muted-foreground">
                {{ detail.key }}
              </dt>
              <dd class="max-w-48 truncate text-right font-medium text-foreground">
                {{ detail.value }}
              </dd>
            </div>
          </dl>
        </article>
      </section>
      <p class="text-xs text-muted-foreground">
        {{ $t('Paskutinis atnaujinimas') }}: {{ formattedUpdatedAt }}
      </p>
    </template>

    <section v-if="deviceMetrics" data-testid="device-metrics" class="space-y-4">
      <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-lg font-semibold text-foreground">
          {{ $t('Prisijungimai pagal įrenginį (30 d.)') }}
        </h2>
        <p class="text-sm text-muted-foreground">
          {{ $t('Iš viso') }}: <span class="tabular-nums">{{ deviceMetrics.summary.total_logins }}</span>
        </p>
      </div>
      <dl class="grid grid-cols-2 gap-px border border-border bg-border lg:grid-cols-4">
        <div v-for="stat in deviceStats" :key="stat.label" class="bg-card p-4">
          <dt class="text-sm text-muted-foreground">
            {{ stat.label }}
          </dt>
          <dd class="mt-1 text-2xl font-semibold tabular-nums text-foreground">
            {{ stat.value }}
            <span v-if="stat.percentage !== undefined" class="text-sm font-normal text-muted-foreground">{{ stat.percentage }}%</span>
          </dd>
        </div>
      </dl>
      <Table v-if="deviceMetrics.records.length">
        <TableHeader>
          <TableRow>
            <TableHead>{{ $t('Data') }}</TableHead>
            <TableHead class="text-right">
              {{ $t('Kompiuteriai') }}
            </TableHead>
            <TableHead class="text-right">
              {{ $t('Telefonai') }}
            </TableHead>
            <TableHead class="text-right">
              {{ $t('Planšetės') }}
            </TableHead>
            <TableHead class="text-right">
              {{ $t('PWA') }}
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="row in deviceMetrics.records" :key="row.date">
            <TableCell class="tabular-nums">
              {{ row.date }}
            </TableCell>
            <TableCell class="text-right tabular-nums">
              {{ row.desktop_logins }}
            </TableCell>
            <TableCell class="text-right tabular-nums">
              {{ row.phone_logins }}
            </TableCell>
            <TableCell class="text-right tabular-nums">
              {{ row.tablet_logins }}
            </TableCell>
            <TableCell class="text-right tabular-nums">
              {{ row.pwa_launches }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </section>
  </OverviewPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleCheck, CircleDashed, CircleX, RefreshCw, TriangleAlert } from 'lucide-vue-next';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import type { StatusPresentation } from '@/Constants/statuses';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { formatNearDate } from '@/Utils/dateTime';

type ServiceStatus = Record<string, unknown> & { status?: string; error?: string; message?: string };
interface DeviceMetricRow { date: string; phone_logins: number; tablet_logins: number; desktop_logins: number; pwa_launches: number }
interface DeviceMetrics {
  records: DeviceMetricRow[];
  summary: {
    total_logins: number; total_phone: number; total_tablet: number; total_desktop: number; total_pwa_launches: number;
    phone_percentage: number; tablet_percentage: number; desktop_percentage: number;
  };
}
const props = defineProps<{ status?: Record<string, ServiceStatus>; lastUpdated: string; deviceMetrics?: DeviceMetrics }>();
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
const deviceStats = computed(() => {
  const summary = props.deviceMetrics?.summary;
  if (!summary) return [];
  return [
    { label: $t('Kompiuteriai'), value: summary.total_desktop, percentage: summary.desktop_percentage },
    { label: $t('Telefonai'), value: summary.total_phone, percentage: summary.phone_percentage },
    { label: $t('Planšetės'), value: summary.total_tablet, percentage: summary.tablet_percentage },
    { label: $t('PWA paleidimai'), value: summary.total_pwa_launches },
  ];
});
usePageBreadcrumbs(BreadcrumbHelpers.adminForm($t('Sistema'), 'systemStatus', $t('Sistemos būsena')));
</script>
