/** Mulberry32 — the same seed always draws the same tree, so it doesn't reshuffle between renders. */
export function seededRandom(seed: number): () => number {
  let state = seed >>> 0;

  return () => {
    state = (state + 0x6d2b79f5) >>> 0;
    let t = state;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

export function jitter(random: () => number, amount: number): number {
  return (random() * 2 - 1) * amount;
}
