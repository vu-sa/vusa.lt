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
