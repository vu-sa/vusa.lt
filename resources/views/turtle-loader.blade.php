{{-- Page-transition loader: the VU SR turtle, replacing NProgress's corner spinner.

     Lives here rather than in a Vue component because it has to sit outside the Inertia root to
     survive page swaps, and because it must reach every surface (admin, public, auth, errors)
     without each layout remembering to mount it. Visibility is driven entirely by the
     `nprogress-busy` class Inertia's progress component puts on <html> — see
     resources/css/components/turtle-loader.css. --}}
<div class="turtle-loader" aria-hidden="true">
    <svg class="turtle-loader__svg" viewBox="0 0 64 37" fill="currentColor" focusable="false">
        {{-- One leg, drawn around its own hip at (0,0), so each instance only says where that hip
             sits and how big the leg is. It runs 6 units *above* the hip, and every leg is painted
             before the body: that overhang stays buried under the shell at both ends of the swing
             instead of a corner swinging clear of the silhouette. Safe to define an id here —
             unlike the Vue mascot, this markup renders exactly once per page. --}}
        <defs>
            <path id="turtle-loader-leg"
                d="M3.8 -6 C4.4 -1 4.3 3.2 3.6 5.2 C3.3 6.3 2.1 6.9 0.2 6.9 L-2.4 6.9
                   C-3.4 6.7 -3.9 6 -3.9 5 C-4 2.5 -4 -1 -3.6 -6 Z" />
        </defs>

        <ellipse class="turtle-loader__shadow" cx="35" cy="35.4" rx="14" ry="1.3" />

        {{-- Far-side legs: a shoulder-width inboard of their near-side partners and a little
             smaller, so they read as the far side of the same two pairs rather than a second
             pair in the middle. --}}
        <g class="turtle-loader__leg turtle-loader__leg--far-front">
            <use href="#turtle-loader-leg" transform="translate(26.75 28) scale(0.88)" />
        </g>
        <g class="turtle-loader__leg turtle-loader__leg--far-hind">
            <use href="#turtle-loader-leg" transform="translate(42.75 28) scale(0.88)" />
        </g>

        <g class="turtle-loader__leg turtle-loader__leg--front">
            <use href="#turtle-loader-leg" transform="translate(23.25 27.5)" />
        </g>
        <g class="turtle-loader__leg turtle-loader__leg--hind">
            <use href="#turtle-loader-leg" transform="translate(47 27.5) scale(1.05)" />
        </g>

        <g class="turtle-loader__body">
            <path class="turtle-loader__tail" d="M47 25.4 C51.5 25.2 56.4 25.6 60.6 26.7 C55.6 28.4 51 28.9 47 28.4 Z" />

            {{-- Head barely taller than the neck it sits on, and overlapping it by a third of
                 its width: any more difference and the throat reads as a seam. --}}
            <g class="turtle-loader__head">
                <g transform="rotate(12 22 23.5)">
                    <rect x="10" y="19" width="15" height="8" rx="4" />
                    <ellipse cx="9" cy="21.5" rx="5.5" ry="4.6" />
                    <circle class="turtle-loader__eye" cx="6.6" cy="20.3" r="1.05" />
                </g>
            </g>

            <path class="turtle-loader__plastron" d="M19 23 C18.5 27.2 25 29 35 29 C44 29 48 27 47 23 Z" />
            <path class="turtle-loader__shell" d="M13 25 A22 16 0 0 1 57 25 C48.5 27.6 21.5 27.6 13 25 Z" />

            {{-- A rim line and two seams: enough to read as a shell, quiet enough at 36px. --}}
            <path class="turtle-loader__scutes" fill="none" stroke-width="1.1" stroke-linecap="round"
                d="M15.5 23.2 C24 19.5 46 19.5 54.5 23.2
                   M28 12.8 C27.3 16 27.1 19 27.3 21.2
                   M42 12.8 C42.7 16 42.9 19 42.7 21.2" />
        </g>
    </svg>
</div>
