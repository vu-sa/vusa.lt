import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';

import type { ContentPart } from '../Types';
import { DEFAULT_SPACER_SIZE } from '../Types/spacerSizes';

/** Recursively pull the first text node out of a Tiptap JSON document. */
function firstTiptapText(json: unknown): string {
  if (!json || typeof json !== 'object') return '';
  const node = json as { text?: string; content?: unknown[] };
  if (node.text) return node.text;
  if (Array.isArray(node.content)) {
    for (const child of node.content) {
      const text = firstTiptapText(child);
      if (text) return text;
    }
  }
  return '';
}

function stripHtml(html: string): string {
  return html.replace(/[<>]/g, '').trim();
}

function truncate(text: string, max = 60): string {
  const trimmed = text.trim();
  return trimmed.length > max ? `${trimmed.slice(0, max).trimEnd()}…` : trimmed;
}

function hostnameOf(url: string): string {
  try {
    return new URL(url).hostname.replace(/^www\./, '');
  }
  catch {
    return url;
  }
}

/**
 * One-line summary shown on a collapsed editor block, so a large block (a full
 * accordion, a carousel with a dozen slides) can be recognised and dragged without
 * expanding it. Each case reads whatever field best identifies that type's content —
 * add a case here when a new content type is registered.
 */
export function deriveBlockSummary(part: ContentPart): string {
  const json = part.json_content as any;
  const options = part.options as any;

  const noTitle = () => $t('rich-content.summary_no_title');

  switch (part.type) {
    case 'tiptap':
      return truncate(firstTiptapText(json)) || '—';
    case 'shadcn-card':
      return options?.title ? truncate(options.title) : '—';
    case 'shadcn-accordion': {
      const items = Array.isArray(json) ? json : [];
      if (items.length === 0) return '—';
      const rest = items.length > 1 ? ` (+${items.length - 1})` : '';
      return truncate(items[0]?.label || noTitle()) + rest;
    }
    case 'image-grid':
    case 'photo-gallery': {
      const count = Array.isArray(json) ? json.length : 0;
      return $tChoice('rich-content.summary_images', count);
    }
    case 'hero':
      return truncate(stripHtml(json?.title ?? '')) || '—';
    case 'news':
    case 'calendar':
      return json?.title ? truncate(json.title) : '—';
    case 'spotify-embed':
    case 'social-embed':
      return json?.url ? hostnameOf(json.url) : '—';
    case 'flow-graph':
      return json?.preset ?? '—';
    case 'number-stat-section':
      return options?.title ? truncate(options.title) : '—';
    case 'text-box':
      return truncate(options?.title?.lt || options?.title?.en || '') || '—';
    case 'content-grid': {
      const rows = Array.isArray(json) ? json.length : 0;
      return $tChoice('rich-content.summary_rows', rows);
    }
    case 'carousel-slide-deck': {
      const slides = Array.isArray(json) ? json : [];
      if (slides.length === 0) return '—';
      const rest = slides.length > 1 ? ` (+${slides.length - 1})` : '';
      return truncate(slides[0]?.title || noTitle()) + rest;
    }
    case 'card-stack': {
      const cards = Array.isArray(json) ? json : [];
      if (cards.length === 0) return '—';
      const rest = cards.length > 1 ? ` (+${cards.length - 1})` : '';
      return truncate(cards[0]?.title || noTitle()) + rest;
    }
    case 'section':
      return options?.title ? truncate(options.title) : noTitle();
    case 'spacer':
      return $t('rich-content.summary_spacer', { size: $t(`rich-content.spacer_size_${options?.size ?? DEFAULT_SPACER_SIZE}`) });
    default:
      return '—';
  }
}
