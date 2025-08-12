import '@tiptap/core';

declare module '@tiptap/core' {
  interface Commands<ReturnType> {
    image: {
      setImageWithAlt: (options: { src: string; alt?: string; title?: string; width?: string | number | null; height?: string | number | null; align?: 'left' | 'center' | 'right' }) => ReturnType;
      updateImageAlt: (alt: string) => ReturnType;
      updateImageTitle: (title: string) => ReturnType;
      updateImageSize: (options: { width?: string | number | null; height?: string | number | null }) => ReturnType;
      updateImageAlignment: (align: string) => ReturnType;
    };
  }
}
