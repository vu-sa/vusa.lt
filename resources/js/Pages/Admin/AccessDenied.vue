<template>
  <Head :title="$t('forbidden.title')" />

  <div class="mx-auto flex w-full max-w-xl flex-col gap-8 py-8 sm:py-12">
    <header class="flex flex-col gap-3">
      <p class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
        <Lock class="size-4" aria-hidden="true" />
        403
      </p>
      <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
        {{ $t('forbidden.title') }}
      </h1>
      <p class="text-base text-muted-foreground">
        {{ $t('forbidden.intro') }}
      </p>
    </header>

    <section
      v-if="action && resource"
      class="flex flex-col gap-1 border-t border-border pt-6"
    >
      <h2 class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
        {{ $t('forbidden.missing') }}
      </h2>
      <p class="text-base text-foreground" data-testid="forbidden-missing">
        {{ action }} · {{ resource }}
      </p>
      <code
        v-if="permission"
        class="text-sm text-muted-foreground"
      >{{ permission }}</code>
    </section>

    <p
      v-else-if="message"
      class="border-t border-border pt-6 text-base text-foreground"
    >
      {{ message }}
    </p>

    <section class="flex flex-col gap-3 border-t border-border pt-6">
      <h2 class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
        {{ $t('forbidden.who_can_help') }}
      </h2>

      <p class="text-base text-foreground">
        {{ $t('forbidden.help_text') }}
      </p>
      <a
        :href="docsHref"
        target="_blank"
        rel="noopener noreferrer"
        class="w-fit text-sm text-foreground underline underline-offset-4 pointer-coarse:py-3"
      >{{ $t('forbidden.help_action') }}</a>

      <p class="text-sm text-muted-foreground">
        {{ $t('forbidden.roles_hint') }}
      </p>
    </section>

    <div class="flex flex-wrap items-center gap-3">
      <Button as-child>
        <Link :href="route('profile.roles')">
          {{ $t('forbidden.roles_action') }}
        </Link>
      </Button>
      <Button variant="outline" as-child>
        <Link :href="route('dashboard')">
          {{ $t('forbidden.home_action') }}
        </Link>
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Lock } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { useDocsHref } from '@/Composables/useDocsHref';

defineProps<{
  permission: string | null;
  action: string | null;
  resource: string | null;
  message: string | null;
}>();

const docsHref = useDocsHref('/pagrindai/teises');
</script>
