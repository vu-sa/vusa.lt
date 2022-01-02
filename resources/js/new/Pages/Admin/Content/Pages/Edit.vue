<template>
  <AdminLayout :title="page.permalink">
    <h2 class="font-bold text-xl mb-2">Turinys</h2>
    <div class="p-4 border-2 rounded-xl border-red-200"><TipTap v-model="content" /></div>
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