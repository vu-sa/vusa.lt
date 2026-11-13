import { stripHtmlTags } from '@/Utils/String';

export const hasRichText = (html: string): boolean => stripHtmlTags(html).trim() !== '' || /<(img|iframe|video)\b/i.test(html);
