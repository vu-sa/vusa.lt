<template>
  <div class="space-y-4" data-slot="installation-instructions">
    <label class="block space-y-2">
      <span class="text-sm font-medium">{{ $t('learning.path.installation.device') }}</span>
      <NativeSelect v-model="platform" :options="platformOptions" />
    </label>
    <ol class="list-decimal space-y-2 pl-5 text-sm leading-relaxed">
      <li v-for="step in 3" :key="step">{{ $t(`learning.path.installation.${platform}.step_${step}`) }}</li>
    </ol>
    <p class="text-sm text-muted-foreground">{{ $t(`learning.path.installation.${platform}.note`) }}</p>
    <p v-if="isInstalled" class="text-sm font-medium" role="status">{{ $t('learning.path.installation.installed_here') }}</p>
    <Button
      v-else-if="canInstall"
      class="h-auto min-h-11 max-w-full whitespace-normal text-left"
      variant="default"
      voice="sentence"
      :disabled="installing"
      @click="install"
    >
      <Download class="size-4" aria-hidden="true" />{{ $t('learning.path.installation.install') }}
    </Button>
    <p v-if="installFailed" role="alert" class="text-sm text-destructive">{{ $t('learning.path.installation.failed') }}</p>
  </div>
</template>

<script setup lang="ts">
import { Download } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { InstallationPlatform } from '@/Utils/installationPlatform';

import { NativeSelect } from '@/Components/ui/native-select';
import { Button } from '@/Components/ui/button';
import { usePWA } from '@/Composables/usePWA';
import { installationPlatform } from '@/Utils/installationPlatform';

const { canInstall, promptInstall, isInstalled } = usePWA();
const platform = ref<InstallationPlatform>('desktop');
const installing = ref(false);
const installFailed = ref(false);
const platformOptions = computed(() => (['ios', 'android', 'desktop'] as const).map(value => ({ value, label: $t(`learning.path.installation.${value}.label`) })));

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
