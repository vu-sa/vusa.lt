import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export const uploadVariants = cva(
  'relative flex flex-col items-center justify-center border border-dashed transition-colors',
  {
    variants: {
      variant: {
        default: 'border-border bg-secondary/20 hover:border-foreground/40 hover:bg-secondary/40 text-muted-foreground',
        active: 'border-brand bg-brand/5 text-brand',
        error: 'border-status-danger-border bg-status-danger-surface text-status-danger',
        success: 'border-status-success-border bg-status-success-surface text-status-success',
      },
      size: {
        default: 'min-h-[200px] p-6',
        sm: 'min-h-[120px] p-4',
        lg: 'min-h-[300px] p-8',
        card: 'aspect-square p-4',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
);

export type UploadVariants = VariantProps<typeof uploadVariants>;

export interface UploadFile {
  id: string;
  name: string;
  size: number;
  type: string;
  url?: string;
  file?: File;
  status: 'pending' | 'uploading' | 'success' | 'error';
  progress: number;
  error?: string;
}
