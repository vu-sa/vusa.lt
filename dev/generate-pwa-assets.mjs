/**
 * PWA Asset Generator
 * 
 * Generates PWA splash screens and favicons with zinc-800 background
 * and the VU SA vertical white logo centered.
 * 
 * Run: npm run generate:pwa
 * Or: node dev/generate-pwa-assets.mjs
 */

import sharp from 'sharp';
import { mkdir, readFile } from 'fs/promises';
import { existsSync } from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');

// Configuration
const BACKGROUND_COLOR = '#27272a'; // Tailwind zinc-800
const LOGO_SIZE_RATIO = 1; // 75% of the shortest dimension

// Paths
const LOGO_PATH = path.join(projectRoot, 'public/images/icons/logos/log.ver.balt.vusa.svg');
const FAVICONS_DIR = path.join(projectRoot, 'public/images/icons/favicons');
const PWA_DIR = path.join(projectRoot, 'public/images/pwa');

// Favicon sizes (square icons)
const FAVICON_SIZES = [
  { name: 'favicon-16x16.png', size: 16 },
  { name: 'favicon-32x32.png', size: 32 },
  { name: 'favicon-96x96.png', size: 96 },
  { name: 'favicon-128.png', size: 128 },
  { name: 'apple-touch-icon-57x57.png', size: 57 },
  { name: 'apple-touch-icon-60x60.png', size: 60 },
  { name: 'apple-touch-icon-72x72.png', size: 72 },
  { name: 'apple-touch-icon-76x76.png', size: 76 },
  { name: 'apple-touch-icon-114x114.png', size: 114 },
  { name: 'apple-touch-icon-120x120.png', size: 120 },
  { name: 'apple-touch-icon-144x144.png', size: 144 },
  { name: 'apple-touch-icon-152x152.png', size: 152 },
  { name: 'apple-touch-icon-180x180.png', size: 180 },
  { name: 'mstile-70x70.png', size: 70 },
  { name: 'mstile-144x144.png', size: 144 },
  { name: 'mstile-150x150.png', size: 150 },
  { name: 'mstile-310x150.png', width: 310, height: 150 },
  { name: 'mstile-310x310.png', size: 310 },
  { name: 'pwa-192x192.png', size: 192 },
  { name: 'pwa-512x512.png', size: 512 },
  { name: 'pwa-512x512-maskable.png', size: 512, maskable: true },
];

// iOS splash screen sizes (portrait orientation)
const SPLASH_SIZES = [
  { name: 'splash-640x1136.png', width: 640, height: 1136 },   // iPhone 5
  { name: 'splash-750x1334.png', width: 750, height: 1334 },   // iPhone 6/7/8
  { name: 'splash-828x1792.png', width: 828, height: 1792 },   // iPhone XR
  { name: 'splash-1125x2436.png', width: 1125, height: 2436 }, // iPhone X/XS
  { name: 'splash-1170x2532.png', width: 1170, height: 2532 }, // iPhone 12/13
  { name: 'splash-1179x2556.png', width: 1179, height: 2556 }, // iPhone 14
  { name: 'splash-1284x2778.png', width: 1284, height: 2778 }, // iPhone 12/13 Pro Max
  { name: 'splash-1290x2796.png', width: 1290, height: 2796 }, // iPhone 14 Pro Max
  { name: 'splash-1536x2048.png', width: 1536, height: 2048 }, // iPad
  { name: 'splash-1668x2388.png', width: 1668, height: 2388 }, // iPad Pro 11"
  { name: 'splash-2048x2732.png', width: 2048, height: 2732 }, // iPad Pro 12.9"
];

/**
 * Generate a favicon/icon with logo centered on zinc background
 */
async function generateIcon(svgBuffer, { name, size, width, height, maskable = false }, outputDir) {
  const w = width || size;
  const h = height || size;
  const shortestDim = Math.min(w, h);
  
  // For maskable icons, the safe zone is 80% of the icon, so we make the logo smaller
  const logoRatio = maskable ? LOGO_SIZE_RATIO * 0.8 : LOGO_SIZE_RATIO;
  const logoHeight = Math.round(shortestDim * logoRatio);
  
  // The SVG has viewBox "0 0 2990 3852", so aspect ratio is 2990/3852 ≈ 0.776
  const svgAspectRatio = 2990 / 3852;
  const logoWidth = Math.round(logoHeight * svgAspectRatio);
  
  // Resize the SVG to fit
  const resizedLogo = await sharp(svgBuffer)
    .resize(logoWidth, logoHeight, { fit: 'inside' })
    .png()
    .toBuffer();
  
  // Create background and composite
  const outputPath = path.join(outputDir, name);
  await sharp({
    create: {
      width: w,
      height: h,
      channels: 4,
      background: BACKGROUND_COLOR,
    },
  })
    .composite([
      {
        input: resizedLogo,
        gravity: 'center',
      },
    ])
    .png()
    .toFile(outputPath);
  
  console.log(`✓ Generated ${name} (${w}x${h})`);
}

/**
 * Generate iOS splash screen with logo centered on zinc background
 */
async function generateSplash(svgBuffer, { name, width, height }, outputDir) {
  const shortestDim = Math.min(width, height);
  const logoHeight = Math.round(shortestDim * LOGO_SIZE_RATIO);
  
  // The SVG has viewBox "0 0 2990 3852", so aspect ratio is 2990/3852 ≈ 0.776
  const svgAspectRatio = 2990 / 3852;
  const logoWidth = Math.round(logoHeight * svgAspectRatio);
  
  // Resize the SVG to fit
  const resizedLogo = await sharp(svgBuffer)
    .resize(logoWidth, logoHeight, { fit: 'inside' })
    .png()
    .toBuffer();
  
  // Create background and composite
  const outputPath = path.join(outputDir, name);
  await sharp({
    create: {
      width,
      height,
      channels: 4,
      background: BACKGROUND_COLOR,
    },
  })
    .composite([
      {
        input: resizedLogo,
        gravity: 'center',
      },
    ])
    .png()
    .toFile(outputPath);
  
  console.log(`✓ Generated ${name} (${width}x${height})`);
}

/**
 * Generate ICO file (as PNG format - browsers accept PNG as favicon.ico)
 */
async function generateIco(svgBuffer, outputDir) {
  const size = 32;
  const svgAspectRatio = 2990 / 3852;
  const logoHeight = Math.round(size * LOGO_SIZE_RATIO);
  const logoWidth = Math.round(logoHeight * svgAspectRatio);
  
  const resizedLogo = await sharp(svgBuffer)
    .resize(logoWidth, logoHeight, { fit: 'inside' })
    .png()
    .toBuffer();
  
  const outputPath = path.join(outputDir, 'favicon.ico');
  await sharp({
    create: {
      width: size,
      height: size,
      channels: 4,
      background: BACKGROUND_COLOR,
    },
  })
    .composite([
      {
        input: resizedLogo,
        gravity: 'center',
      },
    ])
    .png()
    .toFile(outputPath);
  
  console.log(`✓ Generated favicon.ico (32x32)`);
}

async function main() {
  console.log('🎨 PWA Asset Generator');
  console.log(`   Background: ${BACKGROUND_COLOR} (zinc-800)`);
  console.log(`   Logo ratio: ${LOGO_SIZE_RATIO * 100}%\n`);
  
  // Ensure output directories exist
  if (!existsSync(FAVICONS_DIR)) {
    await mkdir(FAVICONS_DIR, { recursive: true });
  }
  if (!existsSync(PWA_DIR)) {
    await mkdir(PWA_DIR, { recursive: true });
  }
  
  // Read SVG logo
  console.log('📖 Reading logo from:', LOGO_PATH);
  const svgBuffer = await readFile(LOGO_PATH);
  
  // Generate favicons
  console.log('\n📱 Generating favicons...');
  for (const config of FAVICON_SIZES) {
    await generateIcon(svgBuffer, config, FAVICONS_DIR);
  }
  
  // Generate ICO
  await generateIco(svgBuffer, FAVICONS_DIR);
  
  // Generate splash screens
  console.log('\n📱 Generating splash screens...');
  for (const config of SPLASH_SIZES) {
    await generateSplash(svgBuffer, config, PWA_DIR);
  }
  
  console.log('\n✅ All assets generated successfully!');
  console.log('\n📝 Next steps:');
  console.log('   1. Run `npm run build` to rebuild with new manifest settings');
  console.log('   2. Test PWA install on mobile device');
}

main().catch((err) => {
  console.error('❌ Error:', err);
  process.exit(1);
});
