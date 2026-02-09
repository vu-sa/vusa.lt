<template>
  <section class="z-5 relative grid grid-cols-[min-content__1fr] rounded-b-2xl pl-12">
    <SmartLink prefetch href="/"
      class="my-auto mr-6 whitespace-nowrap text-sm font-bold tracking-normal text-gray-900 dark:text-gray-200 dark:hover:text-vusa-red">
      {{
        $page.props.tenant?.shortname
          ? $t($page.props.tenant?.shortname)
          : "VU SA"
      }}
    </SmartLink>
    <nav class="grid grid-cols-[1fr_auto_auto] items-center gap-5 whitespace-nowrap py-1 text-xs tracking-wide">
      <div class="relative flex items-center gap-4 overflow-hidden w-full">
        <QuickLink v-for="link in $page.props.tenant?.links" :key="link?.id" :quick-link="link" />
        <div
          class="absolute pointer-events-none right-0 h-8 overflow-hidden bg-linear-to-br from-transparent to-zinc-100 dark:from-transparent dark:to-[rgb(29,_29,_30)] w-10" />
      </div>
      <div class="inline-flex gap-1">
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="ghost"
              size="sm"
              class="h-6 px-1.5 hover:bg-nav-hover-bg-light dark:hover:bg-nav-hover-bg-dark tracking-wide"
              :title="$t('Daugiau nuorodų')"
            >
              <IFluentLineHorizontal1Dot20Filled class="h-4 w-4" />
              <span class="sr-only">{{ $t('Daugiau nuorodų') }}</span>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-48">
            <DropdownMenuItem
              v-for="option in dropdownOptions"
              :key="option.key"
              class="cursor-pointer"
              @click="() => handleSelect(option.key)"
            >
              {{ option.label }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
      <div class="ml-auto flex pr-4 gap-2 items-center">
        <SearchButton class="h-6 px-2 text-xs tracking-wide hover:bg-nav-hover-bg-light dark:hover:bg-nav-hover-bg-dark">
          {{ $t('Paieška') }}
        </SearchButton>
        <a href="/login"
          class="inline-flex items-center justify-center h-6 px-2 text-xs tracking-wide hover:bg-nav-hover-bg-light dark:hover:bg-nav-hover-bg-dark gap-1 rounded-md transition-colors"
          :title="$page.props.auth?.user ? $page.props.auth.user?.name : $t('auth.login')">
          <IFluentPerson24Filled v-if="$page.props.auth?.user" class="h-4 w-4" aria-hidden="true" />
          <IFluentPerson24Regular v-else class="h-4 w-4" aria-hidden="true" />
          <span class="hidden sm:inline">
            {{ $page.props.auth?.user ? $page.props.auth.user?.name : $t('Mano VU SA') }}
          </span>
          <span class="sr-only sm:hidden">
            {{ $page.props.auth?.user ? $page.props.auth.user?.name : $t('Mano VU SA') }}
          </span>
        </a>
      </div>
    </nav>
  </section>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '../SmartLink.vue';

import QuickLink from './QuickLink.vue';
import SearchButton from './SearchButton.vue';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const dropdownOptions = (usePage().props.tenant?.links ?? [])
  .filter((link): link is NonNullable<typeof link> => link?.text != null && link?.link != null)
  .map(link => ({
    label: link.text!,
    key: link.link!,
  }));

const handleSelect = (value: string) => {
  window.location.href = value;
};
</script>
