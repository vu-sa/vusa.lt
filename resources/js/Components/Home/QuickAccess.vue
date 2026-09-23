<template>
  <section v-if="links.length > 0" class="border-t border-border pt-6" data-slot="home-quick-access">
    <SpotlightPopover
      :title="$t('home.quick_access.spotlight_title')"
      :description="$t('home.quick_access.spotlight_description')"
      :is-dismissed="spotlight.isDismissed.value"
      position="right"
      float
      @dismiss="spotlight.dismiss()"
    >
      <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-muted-foreground">
        {{ $t('home.quick_access.title') }}
      </h2>
    </SpotlightPopover>

    <div class="mt-3 grid gap-3 sm:flex sm:flex-wrap">
      <Button v-for="link in links" :key="link.key" as-child variant="outline" voice="sentence" size="lg" class="h-auto min-h-12 justify-start px-4">
        <Link :href="link.href" prefetch @click="spotlight.dismiss()">
          <component :is="link.icon" class="size-4 text-brand" aria-hidden="true" />
          {{ $t(link.label) }}
          <ChevronRight class="ml-auto size-4 text-muted-foreground sm:hidden" aria-hidden="true" />
        </Link>
      </Button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Boxes, ChevronRight, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Button } from '@/Components/ui/button';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const page = usePage<PageProps>();
const { workspaces } = useAdminNavigation();
const spotlight = useFeatureSpotlight('home-quick-access-v1');

const links = computed(() => [
  page.props.auth?.can?.accessAdministration
    ? { key: 'administration', label: 'Administravimas', href: route('administration'), icon: ShieldCheck }
    : null,
  workspaces.value.some(workspace => workspace.key === 'rezervacijos')
    ? { key: 'reservations', label: 'home.quick_access.manage_reservations', href: route('dashboard.reservations'), icon: Boxes }
    : null,
].filter(link => link !== null));
</script>
