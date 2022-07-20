export {};

declare global {
  interface PaginatedObject {
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    // links are array of object with active, label and url
    links: Array<{
      active: boolean;
      label: string;
      url: string;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
  }
}
