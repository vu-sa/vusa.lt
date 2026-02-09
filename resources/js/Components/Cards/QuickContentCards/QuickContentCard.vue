<template>
  <Card class="max-w-sm">
    <CardHeader class="pb-2">
      <div class="flex items-center justify-between">
        <slot name="header" />
        <slot name="header-extra" />
      </div>
    </CardHeader>
    <CardContent>
      <slot />
    </CardContent>
    <CardFooter>
      <div class="flex flex-row items-center">
        <slot name="action-button" />
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="tsx">
import { Card, CardContent, CardFooter, CardHeader } from '@/Components/ui/card';
</script>
