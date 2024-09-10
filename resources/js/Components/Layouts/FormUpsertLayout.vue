<docs>
This layout also handles form errors. If there are any errors, it will display them in an alert.
</docs>

<template>
  <NDialogProvider>
    <NCard class="min-w-[450px]">
      <NAlert v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="mb-4"
        title="Pataisykite klaidas" type="error">
        <ul class="list-inside">
          <li v-for="(error, index) in $page.props.errors" :key="index">
            {{ error }}
          </li>
        </ul>
      </NAlert>
      <slot />
    </NCard>
  </NDialogProvider>
</template>

<script setup lang="ts">
import { NDialogProvider } from 'naive-ui';
</script>
