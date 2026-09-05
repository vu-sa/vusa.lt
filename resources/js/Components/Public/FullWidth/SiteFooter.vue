<template>
  <footer class="w-full border-t border-border bg-secondary/40">
    <div class="mx-auto max-w-7xl px-5 py-12 sm:px-6 lg:px-8 lg:py-14">
      <div class="grid gap-10 lg:grid-cols-[1.3fr_2fr] lg:gap-16">
        <!-- Brand, contacts, social -->
        <section class="space-y-6" aria-labelledby="organization-info">
          <h2 id="organization-info" class="sr-only">
            {{ $t("Vilniaus universiteto Studentų atstovybė") }}
          </h2>

          <HeaderWordmark variant="wordmark" />

          <address class="space-y-2.5 text-sm not-italic text-muted-foreground">
            <p class="flex items-start gap-2.5">
              <IFluentBuilding20Regular class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" />
              <span>
                <span class="block">{{ legal.address.street }}</span>
                <span class="block">{{ legal.address.city }}</span>
              </span>
            </p>
            <p class="flex items-center gap-2.5">
              <IFluentPhone20Regular class="size-4 shrink-0 text-brand" aria-hidden="true" />
              <a :href="`tel:${contacts.phone}`" class="no-underline transition-colors hover:text-brand hover:underline">
                +370 5 268 7144
              </a>
            </p>
            <p class="flex items-center gap-2.5">
              <IFluentMail20Regular class="size-4 shrink-0 text-brand" aria-hidden="true" />
              <a :href="`mailto:${contacts.accounting}`" class="no-underline transition-colors hover:text-brand hover:underline">
                {{ contacts.accounting }}
              </a>
            </p>
          </address>

          <nav :aria-label="$t('accessibility.social_media_and_radio')" class="flex flex-wrap items-center gap-2">
            <FacebookButton icon-only />
            <InstagramButton icon-only />
            <StartFM icon-only />
          </nav>
        </section>

        <!-- Footer navigation — up to 4 columns, managed from Admin ▸ Navigation ▸ Footer.
             A column heading without a URL renders as plain text (see hasColumnLink below);
             `.u-eyebrow` carries no hover treatment, so the two read identically either way. -->
        <nav v-if="footerColumns.length > 0" :aria-label="$t('navigation.footer_navigation')" class="grid grid-cols-2 gap-8 sm:grid-cols-4">
          <div v-for="column in footerColumns" :key="column.id">
            <component
              :is="hasColumnLink(column) ? 'a' : 'span'"
              :href="hasColumnLink(column) ? column.url : undefined"
              class="u-eyebrow block border-b border-border pb-2 no-underline hover:underline"
            >
              {{ column.name }}
            </component>
            <ul v-if="column.links.length > 0" class="mt-4 space-y-2.5">
              <li v-for="link in column.links" :key="link.id">
                <a
                  :href="link.url"
                  :target="link.new_tab ? '_blank' : undefined"
                  :rel="link.new_tab ? 'noopener noreferrer' : undefined"
                  class="text-sm text-muted-foreground no-underline transition-colors hover:text-foreground hover:underline"
                >
                  {{ link.name }}
                </a>
              </li>
            </ul>
          </div>
        </nav>
      </div>

      <div class="mt-10 flex flex-col gap-3 border-t border-border pt-5 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
        <p>{{ $t("Įmonės kodas") }}: {{ legal.company_code }} · {{ $t("PVM mokėtojo kodas") }}: {{ legal.vat_code }}</p>
        <button
          type="button"
          class="text-left transition-colors hover:text-brand hover:underline"
          @click="reopen"
        >
          {{ $t("Slapukų nustatymai") }}
        </button>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { NavFooterColumn } from '../Nav/types';
import FacebookButton from '../Nav/FacebookButton.vue';
import InstagramButton from '../Nav/InstagramButton.vue';
import StartFM from '../Nav/StartFM.vue';

import { HeaderWordmark } from '@/Components/Public/Base';
import { useCookieConsent } from '@/Composables/useCookieConsent';

const { reopen } = useCookieConsent();

// Registry details and contact addresses come from config/vusa.php via shared props, so the
// footer, the schema.org payload and the mail templates cannot disagree.
const page = usePage();
const contacts = computed(() => page.props.organization.contacts);
const legal = computed(() => page.props.organization.legal);
const footerColumns = computed(() => page.props.footerNavigation ?? []);

// A footer column heading is a plain-text label unless an editor gave it a URL — `#`
// is the placeholder every new navigation row is created with (CreateNavigation.vue),
// so it counts as "no link" too rather than rendering a dead anchor.
function hasColumnLink(column: NavFooterColumn): boolean {
  return !!column.url && column.url !== '#';
}
</script>
