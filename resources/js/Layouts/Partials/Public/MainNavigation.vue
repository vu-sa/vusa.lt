<template>
  <nav
    class="flex flex-row fixed justify-between sm:px-12 lg:px-24 border shadow-sm w-full backdrop-blur-sm bg-white/80 text-gray-700 items-center py-4 z-50 top-0"
  >
    <div class="flex flex-row space-x-4 items-center">
      <a :href="mainHost">
        <img class="object-contain min-w-[10vw]" src="/logos/vusa.lin.hor.svg"
      /></a>
      <NDropdown
        :options="options_padaliniai"
        placement="top-start"
        size="medium"
        @select="handleSelect"
      >
        <NButton
          :disabled="!route().current('*home')"
          size="small"
          style="border-radius: 0.5rem"
        >
          <!-- <NGradientText type="warning"> -->
          {{ padalinys }}
          <!-- </NGradientText> -->
          <NIcon class="ml-1" size="18"><ChevronDown20Filled /></NIcon></NButton
      ></NDropdown>
    </div>
    <div class="flex flex-row space-x-4 items-center">
      <!-- <n-gradient-text type="error"> -->
      <div>VU SA</div>
      <!-- </n-gradient-text> -->
      <div>Studijos ir mokslas</div>
      <div>Saviraiška</div>
      <div>Kontaktai</div>
      <NIcon size="18"><FacebookF /></NIcon>
      <NIcon size="18"><Instagram /></NIcon>
      <NBadge dot processing
        ><NIcon color="#000000" size="22"><Search20Filled /></NIcon
      ></NBadge>
      <NDropdown placement="top-end" :options="options"
        ><NButton text
          ><img
            src="https://hatscripts.github.io/circle-flags/flags/gb.svg"
            width="16" /></NButton
      ></NDropdown>
    </div>
  </nav>
</template>

<script setup>
import { FacebookF, Instagram } from "@vicons/fa";
import { Search20Filled, ChevronDown20Filled } from "@vicons/fluent";
import { NIcon, NDropdown, NButton, NGradientText, NBadge } from "naive-ui";
import { usePage, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { ref } from "vue";

// map padaliniai to options_padaliniai

const padaliniai = usePage().props.value.padaliniai;

const options_padaliniai = padaliniai.map((padalinys) => ({
  label: _.split(padalinys.fullname, "atstovybė ")[1],
  key: padalinys.alias,
}));

const getPadalinys = (alias = usePage().props.value.alias) => {
  for (let padalinys of padaliniai) {
    if (padalinys.alias == alias) {
      return padalinys.shortname.split(" ").pop();
    }
  }
  return "Padaliniai";
};

const getMainHost = () => {
  if (window.location.host.includes("localhost")) {
    return "localhost";
  }
  let host = window.location.host.split(".");
  console.log(host);
  let last = host.pop();
  let second_to_last = host.pop();
  return `${second_to_last}.${last}`;
};

const padalinys = ref("");
padalinys.value = getPadalinys();

const mainHost = "http://" + getMainHost();

const handleSelect = (key) => {
  Inertia.reload({
    data: {
      padalinys: key,
    },
    preserveScroll: true,
    only: ["alias", "news"],
    onSuccess: () => {
      padalinys.value = getPadalinys(key);
      console.log(padalinys);
    },
  });
};
</script>
