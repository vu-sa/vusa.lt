import { computed } from 'vue';
import { format, formatDistanceToNow } from 'date-fns';
import { lt } from 'date-fns/locale';
import {
  FileText,
  FileImage,
  FileSpreadsheet,
  FileVideo,
  Link,
  FileCode,
  Archive,
} from 'lucide-vue-next';
import { Icon } from '@iconify/vue';

// Document interface
export interface DocumentDisplayItem {
  id: string | number;
  title: string;
  summary?: string;
  content_type?: string;
  language?: string;
  document_date?: string;
  effective_date?: string;
  expiration_date?: string;
  is_in_effect?: boolean | null;
  anonymous_url: string;
  share_url?: string;
  tenant_shortname?: string;
  tenant_name?: string;
  institution_name_lt?: string;
  name?: string;
}

/**
 * Parse a document date value (ISO string, numeric string, or Unix timestamp)
 * into a JavaScript Date. Returns null for invalid or missing input.
 */
export function parseDocumentDate(dateValue: string | number | null | undefined): Date | null {
  if (dateValue === null || dateValue === undefined || dateValue === '') {
    return null;
  }

  let date: Date;
  if (typeof dateValue === 'number'
    || (typeof dateValue === 'string' && /^\d+$/.test(dateValue))) {
    const timestamp = Number(dateValue);
    date = new Date(timestamp < 10000000000 ? timestamp * 1000 : timestamp);
  }
  else {
    date = new Date(dateValue);
  }

  if (isNaN(date.getTime())) {
    return null;
  }

  return date;
}

/**
 * Appends `web=1` to a SharePoint URL to force it to open in the browser viewer
 * rather than the native OneDrive / Office / Copilot mobile app.
 * Skipped when the URL already has `web=1` or `download=1`.
 */
export function forceBrowserDocumentUrl(url: string | null | undefined): string | undefined {
  if (!url) return undefined;
  if (url.includes('web=1') || url.includes('download=1')) return url;
  const separator = url.includes('?') ? '&' : '?';
  return `${url}${separator}web=1`;
}

/**
 * Composable for document display utilities
 * Centralizes all document formatting and display logic
 */
export const useDocumentDisplay = (document: DocumentDisplayItem) => {
  // Date formatting utilities
  const formatDate = (dateString: string): string => {
    const date = parseDocumentDate(dateString);
    if (!date) return dateString;

    try {
      return format(date, 'yyyy-MM-dd', { locale: lt });
    }
    catch {
      return dateString;
    }
  };

  const formatDocumentDate = (): string => {
    const date = parseDocumentDate(document.document_date);
    if (!date) return 'Nėra datos';

    try {
      const now = new Date();

      // Show relative time for recent documents (for grid view)
      const diffTime = Math.abs(now.getTime() - date.getTime());
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays <= 7) {
        return `${diffDays} d. atgal`;
      }
      else if (diffDays <= 30) {
        return `${Math.ceil(diffDays / 7)} sav. atgal`;
      }
      else {
        return format(date, 'yyyy-MM-dd', { locale: lt });
      }
    }
    catch {
      return document.document_date ?? 'Nėra datos';
    }
  };

  const formatDocumentDateSimple = (): string => {
    const date = parseDocumentDate(document.document_date);
    if (!date) return 'Nėra datos';

    try {
      return format(date, 'yyyy-MM-dd', { locale: lt });
    }
    catch {
      return document.document_date ?? 'Nėra datos';
    }
  };

  const getRelativeDate = (): string => {
    const date = parseDocumentDate(document.document_date);
    if (!date) return '';

    try {
      return formatDistanceToNow(date, { addSuffix: true, locale: lt });
    }
    catch {
      return '';
    }
  };

  // File and icon utilities
  const getFileExtension = (): string => {
    if (!document.name) return '';
    const extension = document.name.split('.').pop()?.toLowerCase();
    return extension || '';
  };

  const getDocumentIcon = () => {
    const extension = getFileExtension();

    // Return iconify icon names for better file type representation
    const iconMap = {
      'pdf': 'ant-design:file-pdf-filled',
      'doc': 'ant-design:file-word-filled',
      'docx': 'ant-design:file-word-filled',
      'xls': 'ant-design:file-excel-filled',
      'xlsx': 'ant-design:file-excel-filled',
      'ppt': 'ant-design:file-ppt-filled',
      'pptx': 'ant-design:file-ppt-filled',
      'jpg': 'ant-design:file-image-filled',
      'jpeg': 'ant-design:file-image-filled',
      'png': 'ant-design:file-image-filled',
      'gif': 'ant-design:file-image-filled',
      'zip': 'ant-design:file-zip-filled',
      'rar': 'ant-design:file-zip-filled',
      '7z': 'ant-design:file-zip-filled',
      'html': 'ant-design:link',
      'url': 'ant-design:link',
      'txt': 'ant-design:file-text-filled',
      'md': 'ant-design:file-markdown-filled',
    } as const;

    return iconMap[extension as keyof typeof iconMap] || 'ant-design:file-filled';
  };

  const getDocumentIconClasses = (): string => {
    const extension = getFileExtension();
    const baseClasses = 'flex items-center justify-center w-10 h-10 rounded-lg';

    const colorMap = {
      pdf: 'bg-red-100 dark:bg-red-950/30 text-red-600 dark:text-red-400',
      doc: 'bg-blue-100 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400',
      docx: 'bg-blue-100 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400',
      xls: 'bg-green-100 dark:bg-green-950/30 text-green-600 dark:text-green-400',
      xlsx: 'bg-green-100 dark:bg-green-950/30 text-green-600 dark:text-green-400',
      ppt: 'bg-orange-100 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400',
      pptx: 'bg-orange-100 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400',
      html: 'bg-purple-100 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400',
      url: 'bg-purple-100 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400',
    } as const;

    return `${baseClasses} ${colorMap[extension as keyof typeof colorMap] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'}`;
  };

  // Content type utilities
  const getShortContentType = (): string => {
    if (!document.content_type) return 'Dokumentas';
    return document.content_type.replace(/^VU SA P? /, '');
  };

  const getContentTypeBadgeClasses = (): string => {
    // Simplified, non-colorful styling for all content types
    return 'bg-muted text-muted-foreground border-border';
  };

  // Language utilities
  const getLanguageCode = (): string => {
    if (!document.language) return '';

    const lang = document.language.toLowerCase();
    if (lang.includes('lietuvių') || lang.includes('lithuanian')) return 'LT';
    if (lang.includes('anglų') || lang.includes('english')) return 'EN';

    return 'OTHER';
  };

  // Organization utilities
  const getTenantDisplayName = (): string => {
    if (document.tenant_shortname) return document.tenant_shortname;
    if (document.tenant_name) return document.tenant_name;
    return 'VU SA';
  };

  // Metadata utilities
  const hasAdditionalMetadata = computed(() => {
    return !!(document.effective_date
      || document.expiration_date
      || document.institution_name_lt);
  });

  // Analytics tracking
  const trackDocumentClick = () => {
    if (typeof window !== 'undefined' && (window as any).posthog) {
      (window as any).posthog.capture('document_click', {
        document_id: document.id,
        document_title: document.title,
        content_type: document.content_type,
        tenant: document.tenant_shortname,
        source: 'search_results',
      });
    }
  };

  return {
    // Date functions
    formatDate,
    formatDocumentDate,
    formatDocumentDateSimple,
    getRelativeDate,

    // File functions
    getFileExtension,
    getDocumentIcon,
    getDocumentIconClasses,

    // Content functions
    getShortContentType,
    getContentTypeBadgeClasses,

    // Language functions
    getLanguageCode,

    // Organization functions
    getTenantDisplayName,

    // Metadata functions
    hasAdditionalMetadata,

    // Analytics functions
    trackDocumentClick,
  };
};

export type DocumentDisplayController = ReturnType<typeof useDocumentDisplay>;
