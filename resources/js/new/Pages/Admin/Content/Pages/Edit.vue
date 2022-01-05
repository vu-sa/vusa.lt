<template>
  <AdminLayout :title="page.permalink">
    <div class="main-card">
      <h2 class="font-bold text-xl mb-2 inline-block bg-black p-1 text-white rounded-lg ">Turinys</h2>
      <div class="py-4"><TipTap v-model="content" /></div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import TipTap from "@/Components/TipTap.vue";
import { ref } from "vue";

const props = defineProps({
  page: Object,
});

const content = ref(props.page.text);
</script>