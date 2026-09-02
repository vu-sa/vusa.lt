---
paths:
  - 'resources/js/Components/ui/alert-dialog/**,resources/js/Components/ui/dialog/**'
---

# Dialog

## Never combine slide-in-from-* with translate centring
Tailwind v4 compiles `-translate-x-1/2 -translate-y-1/2` to the standalone `translate:` property, while tw-animate-css's `enter`/`exit` keyframes write `transform:`. The two stack, so a centred overlay carrying `slide-in-from-left-1/2 slide-in-from-top-[48%]` (the old shadcn AlertDialogContent template) starts an extra 50%/48% up-and-left and visibly flies in from the corner. Centred overlays get `fade` + `zoom` only — see DialogContent, which AlertDialogContent now matches. `slide-*` stays correct for edge-anchored surfaces (dropdown, popover, tooltip, sheet, select), which are not centred by translate. Re-check this after regenerating anything under `ui/`.
