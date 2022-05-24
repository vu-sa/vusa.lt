<template>
  <a :href="route('microsoft.redirect')">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Spline+Sans&display=swap"
      rel="stylesheet"
    />
    <div class="microsoft-btn pr-2">
      <div class="microsoft-icon-wrapper">
        <img
          class="microsoft-icon-svg"
          src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/94/M_box.svg/1920px-M_box.svg.png"
        />
      </div>
      <p class="btn-text">
        <b><slot>Tęsti su Microsoft</slot></b>
      </p>
    </div>
  </a>
</template>

<script>
import { Link } from "@inertiajs/inertia-vue3";

export default {
  components: {
    Link,
  },
};
</script>

<style scoped>
.microsoft-btn {
  /* width: fit-content; */
  /* min-width: 184px; */
  /* max-width: 184px; */
  height: 35px;
  background-color: #fcfcfc;
  border-radius: 8px;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  user-select: none;
  transition: all 400ms ease 0s;
  display: flex;
  align-items: center;
}
.microsoft-btn .microsoft-icon-wrapper {
  margin-top: 1px;
  margin-left: 1px;
  width: 40px;
  height: 40px;
  border-radius: 2px;
  user-select: none;
}
.microsoft-btn .microsoft-icon-svg {
  margin-top: 11px;
  margin-left: 11px;
  width: 18px;
  height: 18px;
  user-select: none;
}
.microsoft-btn .btn-text {
  color: #757575;
  font-size: 0.7em;
  letter-spacing: 0.2px;
  font-family: "Spline Sans", sans-serif;
  user-select: none;
}
.microsoft-btn:hover {
  box-shadow: 0 3px 8px rgba(117, 117, 117, 0.7);
  user-select: none;
}
.microsoft-btn:active {
  box-shadow: 0 1px 1px #757575;
  background: #f8f8f8;
  color: #fff;
  user-select: none;
}
</style>
