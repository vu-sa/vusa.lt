<template>
  <div>
    <Accordion class="not-typography mb-6 mt-1" type="single" collapsible>
      <AccordionItem v-for="(item, index) in mainNavigation" :key="item.name" :value="`${index}`">
        <AccordionTrigger>{{ item.name }}</AccordionTrigger>
        <AccordionContent>
          <MainNavigationMenuContent is-used-without-root :item />
        </AccordionContent>
      </AccordionItem>
    </Accordion>

    <SmartLink href="/"
      class="my-auto whitespace-nowrap text-base font-bold text-gray-900 dark:text-gray-200 dark:hover:text-vusa-red">
      {{
        $page.props.tenant?.shortname
          ? $t($page.props.tenant?.shortname)
          : "VU SA"
      }} nuorodos
    </SmartLink>
    <div class="ml-4 mt-2 flex flex-col gap-2">
      <QuickLink v-for="link in $page.props.tenant.links" :key="link?.id" :quick-link="link" />
    </div>
    <div class="flex gap-4 mt-8">
      <LocaleButton :locale="$page.props.app.locale" />
      <DarkModeButton />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import Accordion from '@/Components/ShadcnVue/ui/accordion/Accordion.vue';
import AccordionContent from '@/Components/ShadcnVue/ui/accordion/AccordionContent.vue';
import AccordionItem from '@/Components/ShadcnVue/ui/accordion/AccordionItem.vue';
import AccordionTrigger from '@/Components/ShadcnVue/ui/accordion/AccordionTrigger.vue';
import DarkModeButton from '@/Components/Buttons/DarkModeButton.vue';
import LocaleButton from './LocaleButton.vue';
import MainNavigationMenuContent from './MainNavigationMenuContent.vue';
import QuickLink from './QuickLink.vue';
import SmartLink from '../SmartLink.vue';

const mainNavigation = computed(() => usePage().props.mainNavigation);

</script>
