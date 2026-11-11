export const hasRichText = (html: string): boolean => html.replace(/<[^>]*>/g, '').trim() !== '' || /<(img|iframe|video)\b/i.test(html);
