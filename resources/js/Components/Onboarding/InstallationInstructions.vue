<template>
  <div class="space-y-4" data-slot="installation-instructions">
    <label class="block space-y-2">
      <span class="text-sm font-medium">{{ $t('onboarding.installation.device') }}</span>
      <NativeSelect v-model="platform" :options="platformOptions" />
    </label>
    <ol class="list-decimal space-y-2 pl-5 text-sm leading-relaxed">
      <li v-for="step in 3" :key="step">{{ $t(`onboarding.installation.${platform}.step_${step}`) }}</li>
    </ol>
    <p class="text-sm text-muted-foreground">{{ $t(`onboarding.installation.${platform}.note`) }}</p>
    <template v-if="tutorial">
      <video
        controls
        playsinline
        preload="none"
        :src="tutorial.src"
        :poster="tutorial.poster"
        class="w-full border border-border"
        :aria-label="$t('onboarding.installation.video')"
      >
        <track kind="captions" :src="tutorial.captions" :srclang="locale" :label="$t('onboarding.installation.captions')" default>
      </video>
      <a :href="tutorial.transcript" class="inline-block py-2 text-sm underline underline-offset-4">{{ $t('onboarding.installation.transcript') }}</a>
    </template>
    <p v-if="isInstalled" class="text-sm font-medium" role="status">{{ $t('onboarding.installation.installed_here') }}</p>
    <Button
      v-else-if="canInstall"
      class="h-auto min-h-11 max-w-full whitespace-normal text-left"
      variant="outline"
      voice="sentence"
      :disabled="installing"
      @click="install"
    >
      {{ $t('onboarding.installation.install') }}
    </Button>
    <p v-if="installFailed" role="alert" class="text-sm text-destructive">{{ $t('onboarding.installation.failed') }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import type { InstallationPlatform, InstallationTutorial } from './types';

import { NativeSelect } from '@/Components/ui/native-select';
import { Button } from '@/Components/ui/button';
import { usePWA } from '@/Composables/usePWA';
import { installationPlatform } from '@/Utils/installationPlatform';

const props = defineProps<{ tutorials?: Partial<Record<InstallationPlatform, InstallationTutorial>> }>();
const { canInstall, promptInstall, isInstalled } = usePWA();
const platform = ref<InstallationPlatform>('desktop');
const installing = ref(false);
const installFailed = ref(false);
const locale = computed(() => usePage().props.app.locale);
const tutorial = computed(() => props.tutorials?.[platform.value]);
const platformOptions = computed(() => (['ios', 'android', 'desktop'] as const).map(value => ({ value, label: $t(`onboarding.installation.${value}.label`) })));

onMounted(() => {
  platform.value = installationPlatform(navigator.userAgent, navigator.platform, navigator.maxTouchPoints);
});

async function install() {
  installing.value = true;
  installFailed.value = false;
  try {
    await promptInstall();
  }
  catch {
    installFailed.value = true;
  }
  finally {
    installing.value = false;
  }
}
</script>
