<template>
  <HoverCard :open-delay="300" :close-delay="100">
    <HoverCardTrigger as-child>
      <div class="inline-block">
        <UserAvatar 
          v-if="!showName" 
          v-bind="$attrs" 
          :user="user" 
          :size="size" 
          :interactive="true"
        />
        <div 
          v-else 
          v-bind="$attrs" 
          class="inline-flex items-center gap-2 px-1 py-0.5 rounded-md transition-colors hover:bg-accent group"
        >
          <UserAvatar :user="user" :size="size" :interactive="true" />
          <span :class="[size > 20 ? 'text-base' : 'text-sm', 'font-medium group-hover:text-accent-foreground']">
            {{ user.name }}
          </span>
        </div>
      </div>
    </HoverCardTrigger>
    
    <HoverCardContent class="w-72 p-0 shadow-lg">
      <div class="relative">
        <!-- User photo header -->
        <div v-if="photo" class="relative h-24 w-full overflow-hidden rounded-t-md">
          <img 
            class="w-full h-full object-cover" 
            :src="photo" 
            alt="User photo"
            loading="lazy"
          >
          <div class="absolute inset-0 bg-gradient-to-t from-background/80 to-transparent"></div>
        </div>
        
        <!-- User information -->
        <div class="flex flex-col gap-4 p-4">
          <!-- User name and role -->
          <div class="flex flex-col gap-1">
            <h3 class="font-semibold text-base">{{ user.name }}</h3>
            <p v-if="user.role || user.position" class="text-sm text-muted-foreground">
              {{ user.role || user.position }}
            </p>
          </div>

          <!-- Contact information -->
          <div class="space-y-2 text-sm">
            <div v-if="user.email" class="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors">
              <IFluentMail20Regular width="14" height="14" />
              <a class="line-clamp-1 hover:underline" :href="`mailto:${user.email}`">{{ user.email }}</a>
            </div>
            <div v-if="user.phone" class="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors">
              <IFluentPhone20Regular width="14" height="14" />
              <a class="line-clamp-1 hover:underline" :href="`tel:${user.phone}`">{{ user.phone }}</a>
            </div>
          </div>

          <!-- Additional information slot -->
          <slot name="additional-info"></slot>
        </div>
      </div>
    </HoverCardContent>
  </HoverCard>
</template>

<script setup lang="ts">
import { computed } from "vue";
import UserAvatar from "./UserAvatar.vue";
import { HoverCard, HoverCardContent, HoverCardTrigger } from "@/Components/ui/hover-card";

const props = defineProps<{
  showName?: boolean;
  size?: number;
  user: Record<string, any>;
}>();

const photo = computed(() => {
  if (props.user.src) return props.user.src;
  if (props.user.profile_photo_path) return props.user.profile_photo_path;
  return null;
});
</script>
