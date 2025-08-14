<template>
  <AdminContentPage :title="$t('Svetainė')">
    <section v-if="tenants.length > 0" class="mt-8">
      <div class="mb-8 inline-flex items-center gap-6">
        <h3 class="mb-0">
          Pasirinkti padalinį
        </h3>
        <div>
          <ThemeProvider>
            <NSelect :value="providedTenant?.id" filterable
              :options="tenants.map(tenant => ({ label: tenant.shortname, value: tenant.id }))"
              @update:value="handleTenantUpdateValue" />
          </ThemeProvider>
        </div>
      </div>
      <ThemeProvider>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
          <NCard :segmented="{
            footer: 'soft',
          }">
          <template #header>
            <div class="inline-flex items-center gap-2">
              <component :is="Icons.PAGE" />
              Puslapiai
            </div>
          </template>
          <div class="grid grid-cols-2 gap-2">
            <p>Visi</p>
            <p>🌐 Išversti</p>
            <span class="inline-block text-4xl font-bold">
              <NNumberAnimation :from="0" :to="providedTenant?.pages?.length" />
            </span>
            <span class="inline-block text-4xl font-bold">
              <NNumberAnimation :from="0" :to="providedTenant?.pages?.filter(page => page.other_lang_id).length" />
            </span>
          </div>
          <template #footer>
            <Link :href="route('pages.index')">
            <NButton size="small" secondary>
              <template #icon>
                <Icons.PAGE />
              </template>
              {{ $t('Rodyti visus') }}
            </NButton>
            </Link>
          </template>
        </NCard>
        <NCard :segmented="{
          footer: 'soft',
        }">
          <template #header>
            <div class="inline-flex items-center gap-2">
              <component :is="Icons.NEWS" />
              Naujienos
            </div>
          </template>
          <div class="grid grid-cols-2 gap-2">
            <p>Visos</p>
            <p>🌐 Išverstos</p>

            <span class="inline-block text-4xl font-bold">
              <NNumberAnimation :from="0" :to="providedTenant?.news?.length" />
            </span>

            <span class="inline-block text-4xl font-bold">
              <NNumberAnimation :from="0" :to="providedTenant?.news?.filter(news => news.other_lang_id).length" />
            </span>
          </div>
          <template #footer>
            <Link :href="route('news.index')">
            <NButton size="small" secondary>
              <template #icon>
                <Icons.NEWS />
              </template>
              {{ $t('Rodyti visus') }}
            </NButton>
            </Link>
          </template>
        </NCard>
        <NCard title="Visų naujienų statistika">
          <div ref="wrapper" class="mx-auto w-fit" />
        </NCard>
        <NCard :segmented="{
          footer: 'soft',
        }">
          <template #header>
            <div class="inline-flex items-center gap-2">
              <component :is="Icons.QUICK_LINK" />
              Greitieji mygtukai
            </div>
          </template>
          <span class="inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="providedTenant?.quick_links?.length" />
          </span>
          <template #footer>
            <Link :href="route('quickLinks.index')">
            <NButton size="small" secondary>
              <template #icon>
                <Icons.QUICK_LINK />
              </template>
              {{ $t('Rodyti visus') }}
            </NButton>
            </Link>
          </template>
        </NCard>
        <NCard :segmented="{
          footer: 'soft',
        }">
          <template #header>
            <div class="inline-flex items-center gap-2">
              <component :is="Icons.CALENDAR" />
              Kalendoriaus įrašai
            </div>
          </template>
          <p class="mb-2">
            Paskutinius 12 mėnesių:
          </p>
          <span class="inline-block text-4xl font-bold">
            <NNumberAnimation :from="0" :to="providedTenant?.calendar?.length" />
          </span>
          <template #footer>
            <Link :href="route('calendar.index')">
            <NButton size="small" secondary>
              <template #icon>
                <Icons.CALENDAR />
              </template>
              {{ $t('Rodyti visus') }}
            </NButton>
            </Link>
          </template>
        </NCard>
        </div>
      </ThemeProvider>
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
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { trans as $t } from "laravel-vue-i18n";
import ThemeProvider from "@/Components/Providers/ThemeProvider.vue";

const { tenants, providedTenant } = defineProps<{
  tenants: App.Entities.Tenant[];
  providedTenant: App.Entities.Tenant | null;
}>();

const handleTenantUpdateValue = (value) => {
  router.reload({ data: { tenant_id: value } });
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
