<template>
  <Dialog v-model:open="dialogOpen">
    <DialogTrigger as-child>
      <Button :loading="loading" :size="size">
        <slot />
      </Button>
    </DialogTrigger>
    <DialogContent class="sm:max-w-[95vw] w-[1400px] h-[85vh] p-0 gap-0 overflow-hidden" :show-close-button="true">
      <DialogTitle class="sr-only">{{ options.title }}</DialogTitle>
      <iframe ref="iframeRef" :name="iframeName" class="w-full h-full border-0" />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { PublicClientApplication } from "@azure/msal-browser";
import { combine } from "@pnp/core";
import { usePage } from "@inertiajs/vue3";
import { ref, watch, nextTick } from "vue";

import type { FilePickerOptions, Item } from "./picker.ts";
import { Button } from "@/Components/ui/button";
import { Dialog, DialogContent, DialogTrigger, DialogTitle } from "@/Components/ui/dialog";

defineProps<{
  loading?: boolean;
  round?: boolean;
  size?: string;
}>()

const emit = defineEmits<{
  pick: [items: Item[]]
}>()

// random string generate
const channelId = Math.random().toString(36).substring(7);
const iframeName = `sp-picker-${channelId}`;

const dialogOpen = ref(false);
const iframeRef = ref<HTMLIFrameElement | null>(null);

const options: FilePickerOptions = {
  "sdk": "8.0",
  "entry": {
    "sharePoint": {
      "byPath": {
        // TODO: Move to configuration - hardcoded SharePoint URLs and paths
        "web": "https://vustudentuatstovybe.sharepoint.com/sites/vieningai",
        "list": "Bendrai naudojami dokumentai", // TODO: Make configurable
        "folder": "Archyvas" // TODO: Make configurable
      }
    }
  },
  "authentication": {},
  messaging: {
    "origin": usePage().props.app.url,
    "channelId": channelId
  },
  typesAndSources: {
    mode: "files",
    access: {
      mode: "read",
    },
    pivots: {
      oneDrive: false,
      shared: false,
      myOrganization: false
    },
    locations: {
      sharePoint: {
        "byPath": {
          // TODO: Move to configuration - hardcoded SharePoint site URL
          "folder": "https://vustudentuatstovybe.sharepoint.com/sites/vieningai"
        }
      }
    }
  },
  selection: {
    mode: "multiple",
    maximumCount: 20
  },
  leftNav: {
    enabled: false
  },
  title: "Select Documents from SharePoint Archive",
}

// TODO: Move to configuration - hardcoded SharePoint base URL
const baseUrl = "https://vustudentuatstovybe.sharepoint.com";

const msalParams = {
  auth: {
    authority: `https://login.microsoftonline.com/${import.meta.env.VITE_SHAREPOINT_TENANT_ID}`,
    clientId: import.meta.env.VITE_SHAREPOINT_CLIENT_ID,
    redirectUri: usePage().props.app.url,
  },
}

const app = new PublicClientApplication(msalParams);
const msalReady = app.initialize().then(() => app.handleRedirectPromise().catch(() => null));

async function getToken(command): Promise<string> {
  let accessToken = "";
  const authParams = { scopes: [`${combine(command.resource, ".default")}`] };

  await msalReady;

  try {
    const resp = await app.acquireTokenSilent(authParams!);
    accessToken = resp.accessToken;
  } catch (e) {
    try {
      const resp = await app.loginPopup(authParams!);
      app.setActiveAccount(resp.account);

      if (resp.idToken) {
        const resp2 = await app.acquireTokenSilent(authParams!);
        accessToken = resp2.accessToken;
      } else {
        throw new Error('Authentication failed: No ID token received');
      }
    } catch (authError) {
      console.error('SharePoint authentication failed:', authError);
      throw new Error(`Authentication failed: ${authError.message || 'Please try again'}`);
    }
  }

  return accessToken;
}

let port: MessagePort | null = null;
let messageListener: ((event: MessageEvent) => void) | null = null;

async function loadPickerInIframe() {
  try {
    const iframe = iframeRef.value;
    if (!iframe) return;

    const accessToken = await getToken({
      resource: baseUrl,
      command: "authenticate",
      type: "SharePoint",
    });

    const queryString = new URLSearchParams({
      filePicker: JSON.stringify(options),
      locale: 'en-us'
    });

    const url = baseUrl + `/_layouts/15/FilePicker.aspx?${queryString}`;

    // Create a form targeting the iframe and submit it with the access token
    const form = document.createElement("form");
    form.setAttribute("action", url);
    form.setAttribute("method", "POST");
    form.setAttribute("target", iframeName);

    const tokenInput = document.createElement("input");
    tokenInput.setAttribute("type", "hidden");
    tokenInput.setAttribute("name", "access_token");
    tokenInput.setAttribute("value", accessToken);
    form.appendChild(tokenInput);

    // Temporarily append form to document body, submit, then remove
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    // Set up PostMessage listener for iframe communication
    setupMessageListener(iframe);
  } catch (error) {
    console.error('SharePoint FilePicker error:', error);
    dialogOpen.value = false;
  }
}

function setupMessageListener(iframe: HTMLIFrameElement) {
  // Clean up any previous listener
  cleanupMessageListener();

  messageListener = (event: MessageEvent) => {
    if (event.source && event.source === iframe.contentWindow) {
      const message = event.data;

      if (message.type === "initialize" && message.channelId === options.messaging.channelId) {
        port = event.ports[0];
        port.addEventListener("message", channelMessageListener);
        port.start();

        port.postMessage({
          type: "activate",
        });
      }
    }
  };

  window.addEventListener("message", messageListener);
}

async function channelMessageListener(message: MessageEvent): Promise<void> {
  const payload = message.data;

  switch (payload.type) {
    case "notification": {
      const notification = payload.data;
      if (notification.notification === "page-loaded") {
        // Picker page is loaded and ready for user interaction
      }
      break;
    }

    case "command": {
      // All commands must be acknowledged
      port?.postMessage({
        type: "acknowledge",
        id: message.data.id,
      });

      const command = payload.data;

      switch (command.command) {
        case "authenticate":
          try {
            const token = await getToken(command);

            if (!token) {
              throw new Error("Unable to obtain a token.");
            }

            port?.postMessage({
              type: "result",
              id: message.data.id,
              data: {
                result: "token",
                token: token,
              }
            });
          } catch (error) {
            console.error('SharePoint authentication error:', error);
            port?.postMessage({
              type: "result",
              id: message.data.id,
              data: {
                result: "error",
                error: {
                  code: "unableToObtainToken",
                  message: error.message || 'Authentication failed. Please try again.'
                }
              }
            });
          }
          break;

        case "close":
          dialogOpen.value = false;
          break;

        case "pick":
          try {
            emit("pick", message.data.data.items);

            port?.postMessage({
              type: "result",
              id: message.data.id,
              data: {
                result: "success"
              }
            });

            cleanup();
            dialogOpen.value = false;
          } catch (error) {
            port?.postMessage({
              type: "result",
              id: message.data.id,
              data: {
                result: "error",
                error: {
                  code: "unusableItem",
                  message: error.message
                }
              }
            });
          }
          break;

        default:
          port?.postMessage({
            type: "result",
            id: message.data.id,
            data: {
              result: "error",
              error: {
                code: "unsupportedCommand",
                message: command.command
              }
            }
          });
          break;
      }
      break;
    }
  }
}

function cleanupMessageListener() {
  if (messageListener) {
    window.removeEventListener("message", messageListener);
    messageListener = null;
  }
}

function cleanup() {
  if (port) {
    port.postMessage({
      type: "result",
      id: "close",
      data: { result: "success" }
    });
    port.close();
    port = null;
  }
  cleanupMessageListener();
}

// Load picker when dialog opens, cleanup when it closes
watch(dialogOpen, async (isOpen) => {
  if (isOpen) {
    await nextTick();
    loadPickerInIframe();
  } else {
    cleanup();
  }
});
</script>
