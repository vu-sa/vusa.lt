<template>
  <Button variant="ghost" size="sm" as-child>
    <a :href="page.props.organization.social.facebook" target="_blank" rel="noopener noreferrer" title="Facebook">
      <ISimpleIconsFacebook class="w-4 h-4 mr-2" />
      <slot />
    </a>
  </Button>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import { Button } from '@/Components/ui/button';

const page = usePage();
</script>
