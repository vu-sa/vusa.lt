<template>
  <section v-if="links.length > 0" data-slot="home-quick-access">
    <SpotlightPopover
      :title="$t('home.quick_access.spotlight_title')"
      :description="$t('home.quick_access.spotlight_description')"
      :is-dismissed="spotlight.isDismissed.value"
      position="right"
      float
      @dismiss="spotlight.dismiss()"
    >
      <!-- The OverviewSection home heading, kept inline so the spotlight can anchor to it; no
           hairline, the tiles draw their own. -->
      <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em] text-foreground">
        <ArrowUpRight class="size-4 shrink-0 text-brand" aria-hidden="true" />
        {{ $t('home.quick_access.title') }}
      </h2>
    </SpotlightPopover>

    <NavigationTiles :items="links" :columns="4" class="mt-3" @navigate="spotlight.dismiss()" />
  </section>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, Boxes, ShieldCheck, UserPlus, Users } from 'lucide-vue-next';
import { computed } from 'vue';

import type { HomeRegistrationForm } from './types';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { NavigationTiles, type NavigationTileItem } from '@/Components/Patterns';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const props = withDefaults(defineProps<{
  /** Shared registration forms the user may open (members, student reps). */
  registrationForms?: HomeRegistrationForm[];
}>(), {
  registrationForms: () => [],
});

const registrationLinks = {
  member: { label: 'home.quick_access.member_registrations', description: 'home.quick_access.member_registrations_description', icon: UserPlus },
  student_rep: { label: 'home.quick_access.rep_registrations', description: 'home.quick_access.rep_registrations_description', icon: Users },
} as const;

const page = usePage<PageProps>();
const { workspaces } = useAdminNavigation();
const spotlight = useFeatureSpotlight('home-quick-access-v2');

const links = computed<NavigationTileItem[]>(() => [
  page.props.auth?.can?.accessAdministration
    ? { key: 'administration', label: 'Administravimas', description: 'home.quick_access.administration_description', href: route('administration'), icon: ShieldCheck }
    : null,
  workspaces.value.some(workspace => workspace.key === 'rezervacijos')
    ? { key: 'reservations', label: 'home.quick_access.manage_reservations', description: 'home.quick_access.manage_reservations_description', href: route('dashboard.reservations'), icon: Boxes }
    : null,
  ...props.registrationForms.map(form => ({ key: `registrations-${form.key}`, href: form.href, ...registrationLinks[form.key] })),
]
  .filter(link => link !== null)
  .map(link => ({ ...link, label: $t(link.label), description: $t(link.description) })));
</script>
