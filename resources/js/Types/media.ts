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

const RESIZABLE_WIDTHS = [400, 900, 1600, 2400, 3840];

/**
 * srcset for a file-manager or legacy `/uploads/` image, served scaled down by the `/img` route.
 * Mirrors App\Support\Media\ResizableImage; null for media-library, foreign and non-image URLs.
 */
export function cmsSrcset(src: string | null | undefined): string | null {
  if (!src) return null;

  let url: URL;
  try {
    url = new URL(src, 'http://same.site');
  } catch {
    return null;
  }

  const sameSite = url.host === 'same.site'
    || (typeof window !== 'undefined' && url.host === window.location.host)
    || /(^|\.)vusa\.(lt|test)$/i.test(url.hostname);
  if (!sameSite || !url.pathname.startsWith('/uploads/')) return null;

  let path: string;
  try {
    path = decodeURIComponent(url.pathname.slice('/uploads/'.length));
  } catch {
    return null;
  }

  if (!/\.(jpe?g|png|webp)$/i.test(path) || /(^|\/)\.\.?(\/|$)/.test(path) || /^(media|staging-media)\//.test(path)) {
    return null;
  }

  const encoded = path.normalize('NFC').split('/').map(encodeURIComponent).join('/');

  return RESIZABLE_WIDTHS.map(width => `/img/${width}/${encoded} ${width}w`).join(', ');
}
