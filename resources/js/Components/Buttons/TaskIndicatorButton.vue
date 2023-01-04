<template>
  <NBadge
    type="info"
    :offset="[0, -4]"
    :value="$page.props.auth.user.tasks_count"
  >
    <NButton text circle @click="Inertia.visit(route('userTasks'))"
      ><template #icon
        ><NIcon :size="24" :component="TasksApp24Regular"></NIcon></template
    ></NButton>
  </NBadge>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { NBadge, NButton, NIcon } from "naive-ui";
import { TasksApp24Regular } from "@vicons/fluent";
import route from "ziggy-js";
</script>
