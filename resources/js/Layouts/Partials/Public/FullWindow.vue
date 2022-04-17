<template>
  <div class="min-h-screen antialiased bg-neutral-50">
    <!-- style="
      background-image: url('/images/elements/Background.svg');
      background-size: cover;
    " -->
    <slot></slot>
  </div>
  <footer
    class="bg-gray-50 border-t border-gray-100 text-gray-500 text-sm shadow-md px-32 py-8 grid gap-2 md:grid-cols-2 justify-items-center items-center"
  >
    <div>
      <p>Vilniaus universiteto Studentų atstovybė</p>
      <p>Įmonės kodas: 193077294</p>
      <p>Juridinis asmuo nėra PVM mokėtojas</p>
    </div>
    <div>
      <div class="flex flex-row items-center gap-2 mb-2 hover:text-gray-700 duration-200">
        <NIcon><Phone20Regular /></NIcon> <a href="tel:+37052687144">+370 5 268 7144</a>
      </div>
      <div class="flex flex-row items-center gap-2">
        <NIcon><Building20Regular /></NIcon>
        <div>
          <p>Universiteto g. 3, Observatorijos kiemelis</p>
          <p>01513, Vilnius, Lietuva</p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { Phone20Regular, Building20Regular } from "@vicons/fluent";
import { NIcon } from "naive-ui";
</script>
