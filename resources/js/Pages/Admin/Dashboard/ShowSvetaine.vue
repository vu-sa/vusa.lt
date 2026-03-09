<template>
  <AdminContentPage :title="$t('Svetainė')">
    <section v-if="tenants.length > 0" class="mt-8">
      <div class="mb-8 inline-flex items-center gap-6">
        <h3 class="mb-0">
          Pasirinkti padalinį
        </h3>
        <div>
          <Select :model-value="selectedTenantId" @update:model-value="handleTenantUpdateValue">
            <SelectTrigger class="w-[200px]">
              <SelectValue placeholder="Pasirinkite padalinį" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle>
              <div class="inline-flex items-center gap-2">
                <component :is="Icons.PAGE" />
                Puslapiai
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 gap-2">
              <p>Visi</p>
              <p>Išversti</p>
              <span class="inline-block text-4xl font-bold">
                {{ providedTenant?.pages?.length }}
              </span>
              <span class="inline-block text-4xl font-bold">
                {{ providedTenant?.pages?.filter(page => page.other_lang_id).length }}
              </span>
            </div>
          </CardContent>
          <CardFooter>
            <Link :href="route('pages.index')">
            <Button size="sm" variant="secondary">
              <Icons.PAGE />
              {{ $t('Rodyti visus') }}
            </Button>
            </Link>
          </CardFooter>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>
              <div class="inline-flex items-center gap-2">
                <component :is="Icons.NEWS" />
                Naujienos
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 gap-2">
              <p>Visos</p>
              <p>Išverstos</p>

              <span class="inline-block text-4xl font-bold">
                {{ providedTenant?.news?.length }}
              </span>

              <span class="inline-block text-4xl font-bold">
                {{ providedTenant?.news?.filter(news => news.other_lang_id).length }}
              </span>
            </div>
          </CardContent>
          <CardFooter>
            <Link :href="route('news.index')">
            <Button size="sm" variant="secondary">
              <Icons.NEWS />
              {{ $t('Rodyti visus') }}
            </Button>
            </Link>
          </CardFooter>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Visų naujienų statistika</CardTitle>
          </CardHeader>
          <CardContent>
            <div ref="wrapper" class="mx-auto w-fit" />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>
              <div class="inline-flex items-center gap-2">
                <component :is="Icons.QUICK_LINK" />
                Greitieji mygtukai
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <span class="inline-block text-4xl font-bold">
              {{ providedTenant?.quick_links?.length }}
            </span>
          </CardContent>
          <CardFooter>
            <Link :href="route('quickLinks.index')">
            <Button size="sm" variant="secondary">
              <Icons.QUICK_LINK />
              {{ $t('Rodyti visus') }}
            </Button>
            </Link>
          </CardFooter>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>
              <div class="inline-flex items-center gap-2">
                <component :is="Icons.CALENDAR" />
                Kalendoriaus įrašai
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="mb-2">
              Paskutinius 12 mėnesių:
            </p>
            <span class="inline-block text-4xl font-bold">
              {{ providedTenant?.calendar?.length }}
            </span>
          </CardContent>
          <CardFooter>
            <Link :href="route('calendar.index')">
            <Button size="sm" variant="secondary">
              <Icons.CALENDAR />
              {{ $t('Rodyti visus') }}
            </Button>
            </Link>
          </CardFooter>
        </Card>
      </div>
    </section>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import Icons from "@/Types/Icons/filled";
import { binX, plot, rectY } from '@observablehq/plot';
import { onMounted, ref, watch } from 'vue';
import { computed } from 'vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { trans as $t } from "laravel-vue-i18n";

const { tenants, providedTenant } = defineProps<{
  tenants: App.Entities.Tenant[];
  providedTenant: App.Entities.Tenant | null;
}>();

const selectedTenantId = computed(() => providedTenant?.id ? String(providedTenant.id) : undefined);

const handleTenantUpdateValue = (value: string) => {
  router.reload({ data: { tenant_id: Number(value) } });
}

const wrapper = ref(null);

const tenantNews = computed(() => providedTenant?.news?.map(news => ({
  ...news,
  publish_time: new Date(news.publish_time),
})))

const generatePlot = () => plot({
  x: { type: "time", label: "Paskelbimo laikas" },
  // don't show decimal
  y: { grid: true, label: "Naujienų skaičius", round: true, nice: true, ticks: 3 },
  marks: [
    rectY(tenantNews.value, binX({ y: "count" }, {
      x: "publish_time", fill: '#aa2430ee'
    })),
  ],
  marginTop: 30,
  marginBottom: 45,
  width: 350,
  height: 170,
});

// Setup breadcrumbs for the Svetaine page
usePageBreadcrumbs([
  { label: $t('Svetainė'), icon: Icons.PAGE }
]);

watch(() => providedTenant, () => {
  if (wrapper.value) {
    wrapper.value.innerHTML = ''
    wrapper.value.appendChild(generatePlot())
  }
});

onMounted(() => {
  if (wrapper.value) {
    wrapper.value?.appendChild(generatePlot());
  }
});
</script>
