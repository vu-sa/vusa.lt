<template>
  <footer
    class="grid items-center justify-items-center gap-2 border-t border-gray-100 bg-stone-50 p-8 text-sm text-gray-500 shadow-md dark:border-zinc-800 dark:bg-zinc-800/80 dark:text-zinc-300 md:grid-cols-2 lg:px-32"
  >
    <div>
      <p>{{ $t("Vilniaus universiteto Studentų atstovybė") }}</p>
      <p>{{ $t("Įmonės kodas") }}: 193077294</p>
      <p>{{ $t("PVM mokėtojo kodas:") }}: LT100015645710</p>
    </div>
    <div>
      <div
        class="mb-2 flex flex-row items-center gap-2 duration-200 hover:text-gray-700"
      ></div>

      <div
        style="grid-template-columns: min-content auto"
        class="grid grid-cols-2 items-center gap-2"
      >
        <NIcon><Phone20Regular /></NIcon>
        <a href="tel:+37052687144">+370 5 268 7144</a>

        <NIcon><Building20Regular /></NIcon>
        <div>
          <p>{{ $t("Universiteto g. 3, Observatorijos kiemelis") }}</p>
          <p>{{ $t("01513, Vilnius, Lietuva") }}</p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Building20Regular, Phone20Regular } from "@vicons/fluent";
import { NIcon } from "naive-ui";
</script>
