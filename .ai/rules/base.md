---
paths:
  - 'resources/js/Components/Public/Base/MediaFrame.vue'
---

# Base

## MediaFrame supports a focalPoint prop for object-position
`MediaFrame` accepts an optional `focalPoint` prop (raw CSS `object-position`, e.g. "50% 30%") applied via inline style on its `<img>`. Pass a model's `*_focal_point` column through it rather than hand-rolling `:style="{ objectPosition: ... }"` wherever a `MediaFrame` is used with a cropped/off-center photo. Precedent: `NewInstitutionCard.vue` passing `institution.image_focal_point`.

## Pass ImageData to MediaFrame, not a URL
When the server sends `*_media` (ImageData), render it with `<MediaFrame :image>` (or `Brand/MediaImage`) plus a `sizes` hint matching the rendered width: that adds srcset from the shared conversions, intrinsic width/height and the image's own focal point. `src`/`focalPoint` remain only as fallbacks for plain URLs; an explicit `focalPoint` overrides the image's.
