<template>
  <NCard
    style="width: 600px"
    title="vusa.lt pasikeitimų sąrašas"
    :bordered="false"
    size="huge"
    role="dialog"
    aria-modal="true"
  >
    <section>
      <h4>v0.1.0 (2022-06-11)</h4>
      <ul class="list-inside">
        <li>
          Padalinius galima surasti puslapyje:
          <a
            class="text-vusa-red"
            target="_blank"
            :href="'/kontaktai/kategorija/padaliniai'"
            >Padaliniai</a
          >
        </li>
      </ul>
    </section>
  </NCard>
</template>

<script setup>
import { NCard } from "naive-ui";
</script>
