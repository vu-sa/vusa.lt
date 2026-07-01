<template>
  <div class="fixed inset-x-0 bottom-0 z-50 flex justify-center p-3 sm:p-4">
    <div
      class="w-full max-w-lg rounded-xl bg-white p-4 shadow-lg dark:bg-zinc-800 sm:p-5">
      <div class="flex items-start gap-3">
        <IFluentCookies24Regular class="mt-0.5 size-5 shrink-0 text-vusa-red" />
        <p class="typography text-sm">
          {{ $t("Naudojame seanso slapukus, kurie yra privalomi tinklalapio veikimui.") }}
          {{ $t("Su jūsų sutikimu taip pat naudojame analitikos slapukus, kad suprastume, kaip naudojamasi svetaine.") }}
        </p>
      </div>

      <div v-if="showDetails" class="mt-3 flex flex-col gap-2 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
        <div class="flex items-center justify-between gap-4">
          <span>{{ $t("Būtinieji slapukai") }}</span>
          <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t("Visada įjungti") }}</span>
        </div>
        <div class="flex items-center justify-between gap-4">
          <label for="analytics-consent">{{ $t("Analitikos slapukai") }}</label>
          <Switch id="analytics-consent" v-model="analyticsChoice" />
        </div>
      </div>

      <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center justify-center gap-1 sm:justify-start">
          <Button as="a" :href="`${$page.props.app.url}/privatumas`" target="_blank" size="sm" variant="link">
            {{ $t("Privatumo politika") }}
          </Button>
          <Button size="sm" variant="ghost" class="rounded-full" @click="showDetails = !showDetails">
            {{ $t("Tvarkyti") }}
          </Button>
        </div>

        <div v-if="showDetails" class="flex">
          <Button size="sm" variant="default" class="w-full rounded-full" @click="saveChoices">
            {{ $t("Išsaugoti pasirinkimus") }}
          </Button>
        </div>
        <div v-else class="grid grid-cols-2 gap-2 sm:flex">
          <Button size="sm" variant="default" class="w-full rounded-full sm:w-auto" @click="rejectAll">
            {{ $t("Tik būtinieji") }}
          </Button>
          <Button size="sm" variant="default" class="w-full rounded-full sm:w-auto" @click="acceptAll">
            {{ $t("Sutikti su visais") }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { useCookieConsent } from '@/Composables/useCookieConsent';

const { acceptAll, rejectAll, setAnalytics, analyticsAllowed } = useCookieConsent();

const showDetails = ref(false);
// Seed from the stored choice so reopening the banner reflects the saved state.
const analyticsChoice = ref(analyticsAllowed.value);

function saveChoices() {
  setAnalytics(analyticsChoice.value);
}
</script>
