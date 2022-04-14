<template>
  <nav
    class="flex flex-row fixed justify-between sm:px-12 lg:px-24 border shadow-md w-full backdrop-blur-sm bg-white/80 text-gray-700 items-center py-4 z-50 top-0"
  >
    <div class="flex flex-row space-x-4 items-center">
      <img class="object-contain" src="/logos/vusa.lin.hor.svg" />
      <NDropdown
        :options="options"
        placement="top-start"
        size="medium"
        @select="handleSelect"
      >
        <NButton size="small" style="border-radius: 0.5rem"
          ><NGradientText type="warning">Padaliniai</NGradientText>
          <NIcon class="ml-1" size="18"><ChevronDown20Filled /></NIcon></NButton
      ></NDropdown>
    </div>
    <div class="flex flex-row space-x-4 items-center">
      <n-gradient-text type="error">VU SA</n-gradient-text>
      <div>Studijos ir mokslas</div>
      <div>Saviraiška</div>
      <div>Kontaktai</div>
      <NIcon size="18"><FacebookF /></NIcon>
      <NIcon size="18"><Instagram /></NIcon>
      <NBadge dot processing
        ><NIcon color="#000000" size="22"><Search20Filled /></NIcon
      ></NBadge>
      <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" width="16" />
    </div>
  </nav>
</template>

<script setup>
import { FacebookF, Instagram } from "@vicons/fa";
import { Search20Filled, ChevronDown20Filled } from "@vicons/fluent";
import { NIcon, NDropdown, NButton, useMessage, NGradientText, NBadge } from "naive-ui";

const message = useMessage();

const options = [
  {
    label: "Chemijos ir geomokslų fakultete",
    key: "a",
  },
  {
    label: "Ekonomikos ir verslo administravimo fakultete",
    key: "b",
  },
  {
    label: "Fizikos fakultete",
    key: "c",
  },
  {
    label: "Filosofijos fakultete",
    key: "d",
    children: [
      {
        label: "Jay Gatsby",
        key: "jay gatsby",
      },
    ],
  },
];

const handleSelect = (key) => {
  message.info(String(key));
};
</script>
