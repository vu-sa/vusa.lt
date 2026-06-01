/**
 * Minimal Y.js sync provider over a Laravel Reverb (Pusher-protocol) channel.
 *
 * Reverb is not a native Y.js backend, so this bridges a `Y.Doc` to a presence
 * channel using **client-events (whisper)**:
 *   - incremental document updates,
 *   - awareness (live cursors / who-is-typing),
 *   - a tiny state-vector sync handshake so late joiners catch unsaved edits.
 *
 * Durable hydration of the full document comes from the DB snapshot via the API
 * (see {@link useAgendaItemNotes}); only small deltas ever travel over the socket,
 * staying well under Reverb's ~10KB client-message limit.
 *
 * The class is transport-agnostic (it depends only on {@link WhisperTransport}),
 * which keeps it unit-testable with a fake whisper bus.
 */
import { Awareness, applyAwarenessUpdate, encodeAwarenessUpdate, removeAwarenessStates } from 'y-protocols/awareness';
import * as Y from 'yjs';

/** The subset of an Echo presence/private channel this provider relies on. */
export interface WhisperTransport {
  whisper: (event: string, data: unknown) => unknown;
  listenForWhisper: (event: string, callback: (data: any) => void) => unknown;
  stopListeningForWhisper?: (event: string) => unknown;
}

const EVENTS = {
  update: 'yjs-update',
  awareness: 'yjs-awareness',
  syncRequest: 'yjs-sync-request',
  syncReply: 'yjs-sync-reply',
} as const;

/** Encode a byte array as base64 in fixed-size chunks (avoids call-stack limits). */
function bytesToBase64(bytes: Uint8Array): string {
  let binary = '';
  const chunkSize = 0x8000;
  for (let i = 0; i < bytes.length; i += chunkSize) {
    binary += String.fromCharCode(...bytes.subarray(i, i + chunkSize));
  }
  return btoa(binary);
}

function base64ToBytes(base64: string): Uint8Array {
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i += 1) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes;
}

export class ReverbYjsProvider {
  readonly doc: Y.Doc;

  readonly awareness: Awareness;

  private readonly transport: WhisperTransport;

  private connected = false;

  private readonly teardown: Array<() => void> = [];

  constructor(doc: Y.Doc, transport: WhisperTransport, awareness?: Awareness) {
    this.doc = doc;
    this.transport = transport;
    this.awareness = awareness ?? new Awareness(doc);
  }

  /** Wire local↔remote document and awareness propagation. Idempotent. */
  connect(): void {
    if (this.connected) {
      return;
    }
    this.connected = true;

    // Local edits → peers. Skip updates we ourselves applied from a peer
    // (origin === this) to avoid an echo loop.
    const onDocUpdate = (update: Uint8Array, origin: unknown) => {
      if (origin === this) {
        return;
      }
      this.transport.whisper(EVENTS.update, { u: bytesToBase64(update) });
    };
    this.doc.on('update', onDocUpdate);
    this.teardown.push(() => this.doc.off('update', onDocUpdate));

    this.transport.listenForWhisper(EVENTS.update, (data: { u?: string }) => {
      if (data?.u) {
        Y.applyUpdate(this.doc, base64ToBytes(data.u), this);
      }
    });

    // Awareness (cursors / presence within the document).
    const onAwarenessUpdate = ({ added, updated, removed }: { added: number[]; updated: number[]; removed: number[] }) => {
      const changed = [...added, ...updated, ...removed];
      this.transport.whisper(EVENTS.awareness, {
        a: bytesToBase64(encodeAwarenessUpdate(this.awareness, changed)),
      });
    };
    this.awareness.on('update', onAwarenessUpdate);
    this.teardown.push(() => this.awareness.off('update', onAwarenessUpdate));

    this.transport.listenForWhisper(EVENTS.awareness, (data: { a?: string }) => {
      if (data?.a) {
        applyAwarenessUpdate(this.awareness, base64ToBytes(data.a), this);
      }
    });

    // Sync handshake: a peer asks for everything we have beyond its state vector;
    // we reply with a minimal diff.
    this.transport.listenForWhisper(EVENTS.syncRequest, (data: { sv?: string }) => {
      const stateVector = data?.sv ? base64ToBytes(data.sv) : undefined;
      this.transport.whisper(EVENTS.syncReply, {
        u: bytesToBase64(Y.encodeStateAsUpdate(this.doc, stateVector)),
      });
    });

    this.transport.listenForWhisper(EVENTS.syncReply, (data: { u?: string }) => {
      if (data?.u) {
        Y.applyUpdate(this.doc, base64ToBytes(data.u), this);
      }
    });
  }

  /**
   * Ask currently-connected peers to send any updates we are missing.
   * Call after the presence handshake reveals other members are online.
   */
  requestSync(): void {
    this.transport.whisper(EVENTS.syncRequest, {
      sv: bytesToBase64(Y.encodeStateVector(this.doc)),
    });
  }

  /** Detach all listeners and drop our cursor from peers' awareness. */
  destroy(): void {
    if (this.connected) {
      removeAwarenessStates(this.awareness, [this.doc.clientID], 'provider-destroy');
    }
    this.teardown.forEach(off => off());
    this.teardown.length = 0;
    Object.values(EVENTS).forEach(event => this.transport.stopListeningForWhisper?.(event));
    this.awareness.destroy();
    this.connected = false;
  }
}
