import imageCompression from 'browser-image-compression';

export interface CompressionOptions {
  /** Maximum file size in MB (default: 2) */
  maxSizeMB?: number;
  /** Maximum width or height in pixels (default: 1600) */
  maxWidthOrHeight?: number;
  /** Use web worker for compression (default: true) */
  useWebWorker?: boolean;
  /** File type to convert to (default: 'image/webp') */
  fileType?: 'image/jpeg' | 'image/png' | 'image/webp';
  /** Quality for lossy compression 0-1 (default: 0.8) */
  quality?: number;
  /** Preserve EXIF data (default: false) */
  preserveExif?: boolean;
}

export interface CompressionResult {
  file: File;
  originalSize: number;
  compressedSize: number;
  compressionRatio: number;
  wasCompressed: boolean;
}

const defaultOptions: Required<CompressionOptions> = {
  maxSizeMB: 2,
  maxWidthOrHeight: 1600,
  useWebWorker: true,
  fileType: 'image/webp',
  quality: 0.8,
  preserveExif: false,
};

/**
 * Composable for client-side image compression using browser-image-compression.
 * 
 * @example
 * const { compressImage, compressImages } = useImageCompression();
 * 
 * // With defaults
 * const result = await compressImage(file);
 * 
 * // With custom options
 * const result = await compressImage(file, { maxSizeMB: 1, quality: 0.7 });
 */
export function useImageCompression(globalOptions: CompressionOptions = {}) {
  const mergedDefaults = { ...defaultOptions, ...globalOptions };

  /**
   * Compress a single image file
   */
  async function compressImage(
    file: File,
    options: CompressionOptions = {}
  ): Promise<CompressionResult> {
    const opts = { ...mergedDefaults, ...options };
    const originalSize = file.size;

    // Skip compression for small files (under 100KB) unless conversion is needed
    const isSmallFile = originalSize < 100 * 1024;
    const needsConversion = !file.type.includes('webp') && opts.fileType === 'image/webp';
    
    if (isSmallFile && !needsConversion) {
      return {
        file,
        originalSize,
        compressedSize: originalSize,
        compressionRatio: 0,
        wasCompressed: false,
      };
    }

    try {
      const compressedFile = await imageCompression(file, {
        maxSizeMB: opts.maxSizeMB,
        maxWidthOrHeight: opts.maxWidthOrHeight,
        useWebWorker: opts.useWebWorker,
        fileType: opts.fileType,
        initialQuality: opts.quality,
        preserveExif: opts.preserveExif,
      });

      // Ensure the file has the correct extension
      let fileName = file.name;
      if (opts.fileType === 'image/webp' && !fileName.endsWith('.webp')) {
        fileName = fileName.replace(/\.[^.]+$/, '.webp');
      }

      // Create new File with correct name and type
      const resultFile = new File([compressedFile], fileName, {
        type: opts.fileType,
        lastModified: Date.now(),
      });

      const compressedSize = resultFile.size;
      const compressionRatio = Math.round((1 - compressedSize / originalSize) * 100);

      return {
        file: resultFile,
        originalSize,
        compressedSize,
        compressionRatio: Math.max(0, compressionRatio),
        wasCompressed: true,
      };
    } catch (error) {
      console.error('Image compression failed:', error);
      // Return original file if compression fails
      return {
        file,
        originalSize,
        compressedSize: originalSize,
        compressionRatio: 0,
        wasCompressed: false,
      };
    }
  }

  /**
   * Compress multiple image files
   */
  async function compressImages(
    files: File[],
    options: CompressionOptions = {}
  ): Promise<CompressionResult[]> {
    return Promise.all(files.map(file => compressImage(file, options)));
  }

  /**
   * Compress a File and return just the compressed File (convenience method)
   */
  async function compress(
    file: File,
    options: CompressionOptions = {}
  ): Promise<File> {
    const result = await compressImage(file, options);
    return result.file;
  }

  /**
   * Get human-readable file size
   */
  function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`;
  }

  return {
    compressImage,
    compressImages,
    compress,
    formatFileSize,
    defaultOptions: mergedDefaults,
  };
}
