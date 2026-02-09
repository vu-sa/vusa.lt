<template>
  <div class="mt-6 space-y-8 sm:mt-8 sm:space-y-12">
    <!-- Header -->
    <ContactPageHeader :title="$t('Kontaktai')" :subtitle="$t(type.title)" />

    <!-- Institution cards with enhanced organization -->
    <div v-if="institutionsWithContent.length > 0">
      <div class="grid grid-cols-1 gap-6">
        <StaggeredTransitionGroup appear>
          <article v-for="(institution, index) in institutionsWithContent" :key="institution.id" :data-index="index"
            class="group relative rounded-lg border border-zinc-200 bg-white p-5 shadow-[0_1px_3px_rgba(0,0,0,0.04)] transition-all duration-200 hover:shadow-[0_4px_12px_rgba(0,0,0,0.08)] hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-[0_1px_3px_rgba(0,0,0,0.3)] dark:hover:shadow-[0_4px_12px_rgba(0,0,0,0.4)] sm:p-6"
            :aria-labelledby="`institution-${institution.id}-title`">
            <InstitutionFigure :institution hide-types>
              <!-- Modern section navigation buttons -->
              <template #more>
                <nav v-if="institution.alias === institution.tenant?.alias" class="mt-6 rounded-md dark:bg-zinc-900/50"
                  :aria-label="$t('Navigation to different contact sections')">
                  <div class="flex flex-wrap gap-1.5">
                    <Button v-for="section in padaliniaiSections" :key="section.alias" :as="SmartLink" :href="route('contacts.alias', {
                      institution: section.alias,
                      subdomain: institution.alias === 'vusa' ? 'www' : institution.alias,
                      lang: $page.props.app.locale,
                    })" variant="ghost" size="sm"
                      class="text-xs border border-zinc-200 bg-white text-zinc-700 transition-colors hover:bg-zinc-50 hover:border-zinc-300 hover:text-zinc-900 focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-1 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600 dark:hover:border-zinc-500 dark:hover:text-zinc-100"
                      :aria-label="`${$t('View')} ${$t(section.title)}`">
                      {{ $t(section.title) }}
                    </Button>
                  </div>
                </nav>
              </template>
            </InstitutionFigure>
          </article>
        </StaggeredTransitionGroup>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-16 text-center">
      <div class="rounded-full bg-zinc-100 p-6 dark:bg-zinc-800">
        <UsersIcon class="size-8 text-zinc-400 dark:text-zinc-500" />
      </div>
      <h3 class="mt-6 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
        {{ $t('No contacts available') }}
      </h3>
      <p class="mt-2 text-base text-zinc-600 dark:text-zinc-400">
        {{ $t('There are currently no contacts available for this category.') }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { UsersIcon } from 'lucide-vue-next';

import ContactPageHeader from '@/Components/Public/ContactPageHeader.vue';
import InstitutionFigure from '@/Components/Public/InstitutionFigure.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import Button from '@/Components/ui/button/Button.vue';
import StaggeredTransitionGroup from '@/Components/Transitions/StaggeredTransitionGroup.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { UserIcon, TypeIcon } from '@/Components/icons';

const $page = usePage();

const props = defineProps<{
  institutions: App.Entities.Institution[];
  type: App.Entities.Type;
}>();

// Set breadcrumbs for contact category page
usePageBreadcrumbs(() => {
  const items = [];

  // Main contacts link
  items.push(
    BreadcrumbHelpers.createRouteBreadcrumb(
      'Kontaktai',
      'contacts',
      {
        subdomain: 'www',
        lang: $page.props.app.locale,
      },
      UserIcon,
    ),
  );

  // Current category type
  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(
      String(props.type.title ?? props.type.slug),
      undefined,
      TypeIcon,
    ),
  );

  return BreadcrumbHelpers.publicContent(items);
});

// Filter institutions that have meaningful content to display
const institutionsWithContent = computed(() => {
  return props.institutions.filter((institution) => {
    // Show institution if it has:
    // 1. Contact information (email, phone, website, social media)
    // 2. Navigation sections (tenant sections)
    // 3. Description (even though controller sets it to empty, structure remains)
    const hasContactInfo = !!(institution.email || institution.phone || institution.website
      || institution.facebook_url || institution.instagram_url);
    const hasNavigationSections = !!(institution.alias === institution.tenant?.alias);
    const hasBasicInfo = !!(institution.name); // All institutions should have names

    return hasContactInfo || hasNavigationSections || hasBasicInfo;
  });
});

const padaliniaiSections = computed(() => {
  const currentLocale = $page.props.app.locale;

  const allSections = [
    {
      title: 'Koordinatoriai',
      alias: 'koordinatoriai',
    },
    {
      title: 'Kuratoriai',
      alias: 'kuratoriai',
      showOnlyForLanguage: 'lt', // Only show on Lithuanian pages
    },
    {
      title: 'Mentors',
      alias: 'mentors', // Use separate alias for mentors
      showOnlyForLanguage: 'en', // Only show on English pages
    },
    {
      title: 'Studentų atstovai',
      alias: 'studentu-atstovai',
    },
  ];

  // Filter sections based on language
  return allSections.filter((section) => {
    // If no language restriction, show for all languages
    if (!section.showOnlyForLanguage) {
      return true;
    }
    // Only show if current locale matches the section's target language
    return section.showOnlyForLanguage === currentLocale;
  });
});

</script>
