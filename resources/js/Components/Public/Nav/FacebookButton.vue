<template>
  <Button variant="ghost" size="sm" as-child>
    <a href="https://www.facebook.com/vieningai.vu.sa" target="_blank" rel="noopener noreferrer" title="Facebook">
      <IMdiFacebook class="w-4 h-4 mr-2" />
      <slot />
    </a>
  </Button>
</template>

<script setup lang="ts">
import { Button } from "@/Components/ui/button";
</script>
