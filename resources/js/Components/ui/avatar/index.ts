import { cva, type VariantProps } from 'class-variance-authority';

export { default as Avatar } from './Avatar.vue';
export { default as AvatarFallback } from './AvatarFallback.vue';
export { default as AvatarImage } from './AvatarImage.vue';

export const avatarSizeClasses = {
  xs: 'size-6', // 24px
  sm: 'size-8', // 32px
  default: 'size-10', // 40px
  lg: 'size-12', // 48px
  xl: 'size-14', // 56px
} as const;

export const avatarVariants = cva(
  'relative flex shrink-0 overflow-hidden rounded-full',
  {
    variants: {
      size: avatarSizeClasses,
      interactive: {
        true: 'cursor-pointer transition-shadow hover:ring-1 hover:ring-primary/30',
        false: '',
      },
    },
    defaultVariants: {
      size: 'default',
      interactive: false,
    },
  },
);

export type AvatarSize = NonNullable<VariantProps<typeof avatarVariants>['size']>;

// Helper to map pixel values to size variants for backward compatibility
export function mapPixelToSize(pixels?: number): AvatarSize {
  if (!pixels) return 'default';
  if (pixels <= 24) return 'xs';
  if (pixels <= 32) return 'sm';
  if (pixels <= 40) return 'default';
  if (pixels <= 48) return 'lg';
  return 'xl';
}

// Text size classes corresponding to avatar sizes
export const avatarTextSizes: Record<AvatarSize, string> = {
  xs: 'text-xs',
  sm: 'text-xs',
  default: 'text-sm',
  lg: 'text-base',
  xl: 'text-lg',
};
