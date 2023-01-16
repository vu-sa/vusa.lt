<script setup lang="tsx">
import { NAvatar, useNotification } from "naive-ui";
import {
  type UseWebNotificationOptions,
  useWebNotification,
} from "@vueuse/core";
import { usePage } from "@inertiajs/vue3";
import type { NotificationData } from "./NotificationItem.vue";

const notification = useNotification();

window.Echo.private(
  "App.Models.User." + usePage().props.auth?.user.id
).notification((notificationSent: NotificationData) => {
  notification.info({
    content() {
      return <div v-html={notificationSent.text}></div>;
    },
    avatar() {
      return (
        <NAvatar
          src={
            notificationSent.subject?.image ??
            usePage().props.auth?.user.profile_photo_path
          }
        ></NAvatar>
      );
    },
  });

  const options: UseWebNotificationOptions = {
    title: notificationSent.text.replaceAll(/<\/?[^>]+(>|$)/gi, ""),
    dir: "auto",
    lang: usePage().props.locale,
    renotify: true,
    tag: "notification",
    icon: notificationSent.subject?.image ?? usePage().props.auth?.user,
  };

  const { isSupported, onClick, show } = useWebNotification(options);

  if (isSupported.value) {
    show();
    onClick.on((evt: Event) => {
      window.location.assign(notificationSent.object.url);
    });
  }
});
</script>
