<template>
  <div class="fixed bottom-0 left-0 right-0 z-50 flex justify-center px-4">
    <div
      class="mb-8 flex size-fit max-w-sm flex-wrap items-center justify-center gap-4 bg-white p-4 shadow-lg rounded-xl dark:bg-zinc-800 md:max-w-none">
      <p class="typography flex h-fit items-center gap-4 px-4 text-sm">
        <IFluentCookies24Regular />
        <span>
          {{
            $t(
              "Naudojame seanso slapukus, kurie yra privalomi tinklalapio veikimui."
            )
          }}
        </span>
      </p>
      <div class="flex gap-2">
        <Button as="a" :href="`${$page.props.app.url}/privatumas`" target="_blank" size="sm" variant="secondary" class="rounded-full">
          {{ $t("Privatumo politika") }}
        </Button>
        <Button size="sm" variant="warning" class="rounded-full" @click="$emit('okayCookieConsent')">
          {{ $t("Gerai") }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';

defineEmits(['okayCookieConsent']);
</script>
