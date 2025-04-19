<template>
  <div class="relative" :class="containerClass">
    <Avatar 
      :class="[
        sizeClass, 
        'transition-all duration-200',
        interactive ? 'hover:ring-2 hover:ring-primary/40 cursor-pointer' : '',
        border ? 'ring-1 ring-border' : ''
      ]"
    >
      <AvatarImage 
        v-if="user?.profile_photo_path" 
        :src="user.profile_photo_path" 
        alt="Profile photo"
        class="object-cover"
      />
      <AvatarFallback 
        v-if="user"
        :class="[
          'text-foreground font-medium transition-colors',
          textSizeClass
        ]"
      >
        {{ userInitials(user.name) }}
      </AvatarFallback>
    </Avatar>
    
    <!-- Status indicator -->
    <!-- <div 
      v-if="status" 
      class="absolute bottom-0 right-0 transform translate-x-1/4 translate-y-1/4 rounded-full ring-2 ring-background"
      :class="[
        statusSizeClass,
        status === 'online' ? 'bg-success' : 
        status === 'away' ? 'bg-warning' : 
        status === 'busy' ? 'bg-destructive' : 
        status === 'offline' ? 'bg-muted' : ''
      ]"
      aria-hidden="true"
    ></div> -->
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarImage, AvatarFallback } from '@/Components/ui/avatar';

type StatusType = 'online' | 'offline' | 'away' | 'busy' | null;

const props = defineProps<{
  user?: App.Entities.User;
  size?: number;
  interactive?: boolean;
  border?: boolean;
  status?: StatusType;
  class?: string;
}>();

const containerClass = computed(() => props.class || '');

const sizeClass = computed(() => {
  const sizeValue = props.size || 40;
  return `h-[${sizeValue}px] w-[${sizeValue}px]`;
});

const textSizeClass = computed(() => {
  const size = props.size || 40;
  if (size < 24) return 'text-xs';
  if (size < 32) return 'text-sm';
  if (size < 48) return 'text-base';
  return 'text-lg';
});

const statusSizeClass = computed(() => {
  const size = props.size || 40;
  if (size < 24) return 'h-1.5 w-1.5';
  if (size < 32) return 'h-2 w-2';
  if (size < 48) return 'h-2.5 w-2.5';
  return 'h-3 w-3';
});

const userInitials = (name: string | null) => {
  if (!name) return "";

  const words = name.split(" ");
  if (words.length === 1) return words[0].substring(0, 2).toUpperCase();
  return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
};
</script>
