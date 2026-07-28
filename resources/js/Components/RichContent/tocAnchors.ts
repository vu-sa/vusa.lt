export interface AnchorLink {
  title: string;
  href: string;
  children: Omit<AnchorLink, 'children'>[];
}

interface HeadingNode {
  type: 'heading';
  attrs: {
    // h4 (added alongside size/accent/align — see CustomHeading.ts) is deliberately
    // NOT indexed below: it's a visual sub-level, not a new ToC tier, so extending the
    // filter to include it would need a third nesting level this extractor doesn't have.
    level: 2 | 3 | 4;
    id: string;
  };
  content: { text: string }[];
}

interface TipTapNode {
  type: string;
  content?: TipTapNode[];
}

export interface AnchorablePart {
  id?: number;
  type: string;
  json_content?: {
    content?: (TipTapNode | HeadingNode)[];
  };
  options?: Record<string, unknown> | null;
}

/**
 * Content types whose display renders an RCSection header from `options.title` (see
 * Editor/RCSectionOptions.vue) — each contributes one top-level ToC entry, in addition
 * to the tiptap h2/h3 headings this extractor already picked up. Before this, a page
 * built entirely from section blocks (hero, accordion, card-stack, …) got an empty
 * table of contents even though every block has a title on screen.
 */
const SECTION_TITLED_TYPES = new Set([
  'hero',
  'shadcn-accordion',
  'card-stack',
  'carousel-slide-deck',
  'photo-gallery',
  'number-stat-section',
  'link-list',
  'event-list',
  'person-quote',
  'content-grid',
  'section',
]);

/**
 * Build the sidebar/mobile table-of-contents links from a page's content parts.
 * Two sources, in document order:
 *  - `tiptap` blocks: h2 → top-level entry, h3 → nested under the preceding h2 (or
 *    promoted to top-level if there isn't one yet).
 *  - section-chrome blocks (see `SECTION_TITLED_TYPES`): one top-level entry per
 *    non-empty `options.title`, anchored to `#rc-{part.id}` — the id `RCSection.vue`
 *    renders on its root element (see `RichContentParser`'s `:anchor-id` prop).
 */
export function extractAnchorLinks(parts: AnchorablePart[] | null | undefined): AnchorLink[] {
  return (parts ?? []).reduce((acc: AnchorLink[], part) => {
    if (part.type === 'tiptap' && part.json_content?.content) {
      const partHeadings = part.json_content.content.filter(
        (node): node is HeadingNode => node.type === 'heading' && 'attrs' in node && (node.attrs.level === 2 || node.attrs.level === 3),
      );

      if (!partHeadings || partHeadings.length === 0) {
        return acc;
      }

      const headings = partHeadings.reduce((headingsAcc: AnchorLink[], node: HeadingNode) => {
        if (node.content && node.content[0] && node.content[0].text) {
          if (node.attrs.level === 2) {
            headingsAcc.push({
              title: node.content[0].text,
              href: `#${node.attrs.id}`,
              children: [],
            });
          }
          else if (node.attrs.level === 3) {
            const lastHeading = headingsAcc[headingsAcc.length - 1];
            if (lastHeading?.children) {
              lastHeading.children.push({
                title: node.content[0].text,
                href: `#${node.attrs.id}`,
              });
            }
            else {
              headingsAcc.push({
                title: node.content[0].text,
                href: `#${node.attrs.id}`,
                children: [],
              });
            }
          }
        }
        return headingsAcc;
      }, []);

      if (headings) {
        acc.push(...headings);
      }
    }
    else if (SECTION_TITLED_TYPES.has(part.type) && part.id != null) {
      const title = part.options?.title;
      if (typeof title === 'string' && title.trim().length > 0) {
        acc.push({ title, href: `#rc-${part.id}`, children: [] });
      }
    }

    return acc;
  }, []);
}
