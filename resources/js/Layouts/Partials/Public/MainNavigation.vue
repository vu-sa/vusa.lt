<template>
  <nav
    class="flex flex-row fixed justify-between py-8 px-16 border border-amber-300 w-full backdrop-blur-sm bg-white/90 text-gray-700 items-center"
  >
    <div class="flex flex-row space-x-4 items-center">
      <div>VU SA logo</div>
      <div>Padaliniai</div>
    </div>
    <div class="flex flex-row space-x-4 items-center">
      <div>VU SA</div>
      <div>Studijos ir mokslas</div>
      <div>Saviraiška</div>
      <div>Kontaktai</div>
      <NIcon size="18"><FacebookF /></NIcon>
      <NIcon size="18"><Instagram /></NIcon>
      <NIcon size="18"><Search20Filled /></NIcon>
      <div>EN</div>
    </div>
  </nav>
</template>

<script setup>
import { FacebookF, Instagram } from "@vicons/fa";
import { Search20Filled } from "@vicons/fluent";
import { NIcon } from "naive-ui";
</script>
