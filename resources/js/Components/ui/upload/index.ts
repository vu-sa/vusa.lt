import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Upload } from "./Upload.vue"
export { default as UploadTrigger } from "./UploadTrigger.vue"
export { default as UploadPreview } from "./UploadPreview.vue"
export { default as UploadProgress } from "./UploadProgress.vue"
export { default as UploadDropzone } from "./UploadDropzone.vue"
export { default as SingleImageUpload } from "./SingleImageUpload.vue"
export { default as MultiImageUpload } from "./MultiImageUpload.vue"
export { default as MediaUpload } from "./MediaUpload.vue"
export { default as ImageUpload } from "./ImageUpload.vue"

export const uploadVariants = cva(
  'relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed transition-colors',
  {
    variants: {
      variant: {
        default: 'border-zinc-200 bg-zinc-50/50 hover:bg-zinc-100/50 dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:bg-zinc-800/50',
        active: 'border-vusa-red bg-vusa-red/5 dark:bg-vusa-red/10',
        error: 'border-red-500 bg-red-50/50 dark:border-red-900 dark:bg-red-950/50',
        success: 'border-green-500 bg-green-50/50 dark:border-green-900 dark:bg-green-950/50',
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
  }
)

export type UploadVariants = VariantProps<typeof uploadVariants>

export interface UploadFile {
  id: string
  name: string
  size: number
  type: string
  url?: string
  file?: File
  status: 'pending' | 'uploading' | 'success' | 'error'
  progress: number
  error?: string
}
