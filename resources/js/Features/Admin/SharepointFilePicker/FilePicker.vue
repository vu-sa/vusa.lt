<template>
  <NButton @click="openPicker">
    <slot />
  </NButton>
</template>

<script setup lang="ts">
import { PublicClientApplication } from "@azure/msal-browser";
import { combine } from "@pnp/core";
import { usePage } from "@inertiajs/vue3";

import type { FilePickerOptions, Item } from "./picker.ts";

const emit = defineEmits<{
  pick: [items: Item[]]
}>()

// random string generate
const channelId = Math.random().toString(36).substring(7);

const options: FilePickerOptions = {
  "sdk": "8.0",
  "entry": {
    "sharePoint": {
      "byPath": {
        "web": "https://vustudentuatstovybe.sharepoint.com/sites/vieningai",
        //"list": "Documents",
        "folder": "Archyvas"
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
  title: "Pridėk failą prie vusa.lt dokumentų",
}

const baseUrl = "https://vustudentuatstovybe.sharepoint.com";

const msalParams = {
  auth: {
    authority: `https://login.microsoftonline.com/${import.meta.env.VITE_SHAREPOINT_TENANT_ID}`,
    clientId: import.meta.env.VITE_SHAREPOINT_CLIENT_ID,
    redirectUri: usePage().props.app.url
  },
}

const app = new PublicClientApplication(msalParams);

async function getToken(command): Promise<string> {
  let accessToken = "";
  const authParams = { scopes: [`${combine(command.resource, ".default")}`] };

  await app.initialize()

  try {

    // see if we have already the idtoken saved
    const resp = await app.acquireTokenSilent(authParams!);
    accessToken = resp.accessToken;

  } catch (e) {

    // per examples we fall back to popup
    const resp = await app.loginPopup(authParams!);
    app.setActiveAccount(resp.account);

    if (resp.idToken) {

      const resp2 = await app.acquireTokenSilent(authParams!);
      accessToken = resp2.accessToken;

    } else {

      // throw the error that brought us here
      throw e;
    }
  }

  return accessToken;
}

async function openPicker() {

  const win = window.open("", "Picker", "width=1080,height=680");

  const queryString = new URLSearchParams({
    filePicker: JSON.stringify(options),
    locale: 'en-us'
  });

  // we create the absolute url by combining the base url, appending the _layouts path, and including the query string
  const url = baseUrl + `/_layouts/15/FilePicker.aspx?${queryString}`;

  // create a form
  const form = win?.document.createElement("form");

  const accessToken = await getToken({
    resource: baseUrl,
    command: "authenticate",
    type: "SharePoint",
  });

  if (form === undefined) {
    throw new Error("Unable to create form element.");
  }

  // set the action of the form to the url defined above
  // This will include the query string options for the picker.
  form.setAttribute("action", url);

  // must be a post request
  form.setAttribute("method", "POST");

  // Create a hidden input element to send the OAuth token to the Picker.
  // This optional when using a popup window but required when using an iframe.
  const tokenInput = win?.document.createElement("input");

  if (tokenInput === undefined) {
    throw new Error("Unable to create input element.");
  }

  tokenInput.setAttribute("type", "hidden");
  tokenInput.setAttribute("name", "access_token");
  tokenInput.setAttribute("value", accessToken);
  form.appendChild(tokenInput);

  // append the form to the body
  win?.document.body.append(form);

  // submit the form, this will load the picker page
  form.submit();


  // Establish Messaging
  let port: MessagePort;

  async function channelMessageListener(message: MessageEvent): Promise<void> {
    const payload = message.data;

    switch (payload.type) {

      case "notification":
        const notification = payload.data;

        if (notification.notification === "page-loaded") {
          // here we know that the picker page is loaded and ready for user interaction
        }

        break;

      case "command":

        // all commands must be acknowledged
        port.postMessage({
          type: "acknowledge",
          id: message.data.id,
        });

        // this is the actual command specific data from the message
        const command = payload.data;

        // command.command is the string name of the command
        switch (command.command) {

          case "authenticate":
            // the first command to handle is authenticate. This command will be issued any time the picker requires a token
            // 'getToken' represents a method that can take a command and return a valid auth token for the requested resource
            try {
              const token = await getToken(command);

              //const token = microsoftToken

              if (!token) {
                throw new Error("Unable to obtain a token.");
              }

              // we report a result for the authentication via the previously established port
              port.postMessage({
                type: "result",
                id: message.data.id,
                data: {
                  result: "token",
                  token: token,
                }
              });
            } catch (error) {
              port.postMessage({
                type: "result",
                id: message.data.id,
                data: {
                  result: "error",
                  error: {
                    code: "unableToObtainToken",
                    message: error.message
                  }
                }
              });
            }

            break;

          case "close":
            win?.close();
            break;
          case "pick":
            try {

              emit("pick", message.data.data.items);

              // let the picker know that the pick command was handled (required)
              port.postMessage({
                type: "result",
                id: message.data.id,
                data: {
                  result: "success"
                }
              });

              port.close();

              win?.close();

            } catch (error) {
              port.postMessage({
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
            // Always send a reply, if if that reply is that the command is not supported.
            port.postMessage({
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

  // this adds a listener to the current (host) window, which the popup or embed will message when ready
  window.addEventListener("message", (event) => {

    if (event.source && event.source === win) {

      const message = event.data;

      if (message.type === "initialize" && message.channelId === options.messaging.channelId) {

        port = event.ports[0];
        port.addEventListener("message", channelMessageListener);
        port.start();

        port.postMessage({
          type: "activate",
        });

        console.log(port, "port");
      }
    }
  });

  window.onbeforeunload = () => {
    port.postMessage({
      type: "result",
      id: "close",
      data: {
        result: "success"
      }
    });

    port.close();

    win?.close();
  }
}
</script>
