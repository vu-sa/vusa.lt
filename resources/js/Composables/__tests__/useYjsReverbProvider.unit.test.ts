import { describe, expect, it } from 'vitest';
import * as Y from 'yjs';

import { ReverbYjsProvider, type WhisperTransport } from '../useYjsReverbProvider';

/**
 * A pair of in-memory whisper transports that relay events to the *other* side
 * only (never echo back to the sender), mimicking Reverb client-events.
 */
function createWhisperPair(): { a: WhisperTransport; b: WhisperTransport } {
  const listenersA: Record<string, Array<(data: unknown) => void>> = {};
  const listenersB: Record<string, Array<(data: unknown) => void>> = {};

  const make = (own: typeof listenersA, peer: typeof listenersB): WhisperTransport => ({
    whisper: (event, data) => {
      (peer[event] ?? []).forEach(cb => cb(data));
    },
    listenForWhisper: (event, cb) => {
      (own[event] ??= []).push(cb);
    },
    stopListeningForWhisper: (event) => {
      delete own[event];
    },
  });

  return { a: make(listenersA, listenersB), b: make(listenersB, listenersA) };
}

describe('ReverbYjsProvider', () => {
  it('relays live document edits so both docs converge', () => {
    const { a, b } = createWhisperPair();
    const docA = new Y.Doc();
    const docB = new Y.Doc();
    const providerA = new ReverbYjsProvider(docA, a);
    const providerB = new ReverbYjsProvider(docB, b);
    providerA.connect();
    providerB.connect();

    docA.getText('notes').insert(0, 'Labas');

    expect(docB.getText('notes').toString()).toBe('Labas');

    docB.getText('notes').insert(5, ' pasauli');
    expect(docA.getText('notes').toString()).toBe('Labas pasauli');

    providerA.destroy();
    providerB.destroy();
  });

  it('hydrates a late joiner via the sync handshake', () => {
    const { a, b } = createWhisperPair();
    const docA = new Y.Doc();
    const providerA = new ReverbYjsProvider(docA, a);
    providerA.connect();

    // Content created before B is listening — B misses the live update.
    docA.getText('notes').insert(0, 'Ankstesnės pastabos');

    const docB = new Y.Doc();
    const providerB = new ReverbYjsProvider(docB, b);
    providerB.connect();
    expect(docB.getText('notes').toString()).toBe('');

    // B asks peers for what it is missing; A replies with a diff.
    providerB.requestSync();
    expect(docB.getText('notes').toString()).toBe('Ankstesnės pastabos');

    providerA.destroy();
    providerB.destroy();
  });

  it('does not echo a peer-applied update back to the sender', () => {
    const { a, b } = createWhisperPair();
    const docA = new Y.Doc();
    const docB = new Y.Doc();
    const providerA = new ReverbYjsProvider(docA, a);
    const providerB = new ReverbYjsProvider(docB, b);
    providerA.connect();
    providerB.connect();

    let bOutgoing = 0;
    const originalWhisper = b.whisper;
    b.whisper = (event, data) => {
      if (event === 'yjs-update') {
        bOutgoing += 1;
      }
      return originalWhisper(event, data);
    };

    docA.getText('notes').insert(0, 'x');

    // B applied A's update but must not re-broadcast it.
    expect(bOutgoing).toBe(0);
    expect(docB.getText('notes').toString()).toBe('x');

    providerA.destroy();
    providerB.destroy();
  });
});
