import type { ComputedRef, InjectionKey } from 'vue';

/** Mirrors App\Support\Media\ImageData. Forms post it back; `id` is what the server attaches. */
export interface ImageData {
  id: number | null;
  url: string;
  thumb: string;
  srcset: string | null;
  width: number | null;
  height: number | null;
  focal_point: string | null;
  alt: string | null;
  author: string | null;
}

/**
 * An image known only by its cached URL (avatars read from a pivot). Posted back with `id: null`,
 * the server keeps the record's current image and updates its properties.
 */
export function imageFromCachedUrl(url: string | null | undefined, focalPoint: string | null = null): ImageData | null {
  if (!url) {
    return null;
  }

  return { id: null, url, thumb: url, srcset: null, width: null, height: null, focal_point: focalPoint, alt: null, author: null };
}

export function imageFromSearch(document: Record<string, unknown>, prefix = 'image'): ImageData | null {
  const url = document[`${prefix}_display_url`];
  if (typeof url !== 'string' || !url) return null;
  const text = (key: string) => typeof document[`${prefix}_${key}`] === 'string' ? document[`${prefix}_${key}`] as string : null;
  const dimension = (key: string) => typeof document[`${prefix}_${key}`] === 'number' ? document[`${prefix}_${key}`] as number : null;
  return { id: null, url, thumb: typeof document.image === 'string' ? document.image : url,
    srcset: text('srcset'), width: dimension('width'), height: dimension('height'),
    focal_point: text('focal_point'), alt: null, author: null };
}

/** Server-checked candidates for plain CMS image URLs. */
export const cmsImageSrcsetsKey: InjectionKey<ComputedRef<Record<string, string>>> = Symbol('cmsImageSrcsets');
