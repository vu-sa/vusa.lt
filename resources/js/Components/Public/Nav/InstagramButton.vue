<template>
  <Button variant="ghost" size="sm" as-child>
    <a href="https://www.instagram.com/vu.studentu.atstovybe" target="_blank" rel="noopener noreferrer" title="Instagram">
      <IMdiInstagram class="w-4 h-4 mr-2" />
      <slot />
    </a>
  </Button>
</template>

<script setup lang="ts">
import { Button } from "@/Components/ui/button";
</script>
