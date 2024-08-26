<template>
  <div class="full-bleed relative mt-4 h-[28rem] overflow-hidden">

    <img id="adastra-background" class="top-0 size-full object-cover" src="/images/adastra/adastra-fonas.jpg">
    <img src="/images/adastra/adastra-logo.svg"
      class="absolute -right-4 top-[-10%] my-auto w-3/5 opacity-70 sm:top-[-35%] md:bottom-0 md:right-[-10%] md:top-[-30%] md:h-4/5 lg:-top-8 lg:h-full">
    <section class="absolute left-0 top-0 size-full overflow-hidden">

      <span />
      <span />
      <span />
      <span />
      <span />
      <span />
      <span />
      <span />
      <span />
      <span />

    </section>
    <div class="absolute top-0 h-6 w-full bg-gradient-to-b from-white to-transparent dark:from-zinc-900" />
    <div class="absolute bottom-0 h-5 w-full bg-gradient-to-b from-transparent to-white dark:to-zinc-900" />
    <div class="absolute bottom-8 left-[4%] z-10 px-10 py-4 text-white md:max-w-[50%]">
      <p class="text-4xl font-extrabold tracking-wide sm:text-5xl">
        Rugsėjo 6 d.
      </p>
      <p class="mt-1 text-xl font-medium leading-6 tracking-wider sm:text-2xl">
        Universiteto gyvenimo integracijos festivalis
      </p>

      <p class="mt-4 font-light">
        Diskusijos · Koncertas · Dirbtuvės · Kontaktų mugė · ir daugiau!
      </p>
      <a href="https://www.facebook.com/events/484501527532466" class="mt-4 block sm:w-1/2" target="_blank">
        <button
          class="w-full justify-self-end rounded-full bg-gradient-to-b from-orange-600 to-amber-500 px-4 py-2 font-bold text-white shadow-xl transition-shadow">Dalyvauk!</button></a>
    </div>
  </div>
</template>

<script setup>

</script>

<style scoped>
#adastra-background {
  animation: saturate 20s infinite;
}

span {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 4px;
  height: 4px;
  background: #fff;
  border-radius: 50%;
  box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1), 0 0 0 8px rgba(255, 255, 255, 0.1), 0 0 20px rgba(255, 255, 255, 0.1);
  animation: stars 12s linear infinite;
}

span::before {
  content: '';
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 300px;
  height: 1px;
  background: linear-gradient(90deg, #fff, transparent);
}

@keyframes stars {
  0% {
    transform: rotate(315deg) translateX(0);
    opacity: 0;
  }

  10% {
    opacity: 1;
  }

  100% {
    transform: rotate(315deg) translateX(-1000px);
    opacity: 0;
  }
}

span:nth-child(1) {
  top: 0;
  right: 0;
  left: initial;
  animation-delay: 0s;
  animation-duration: 3s;
}

span:nth-child(2) {
  top: 0;
  right: 80px;
  left: initial;
  animation-delay: 0.2s;
  animation-duration: 8s;
}

span:nth-child(3) {
  top: 80;
  right: 0px;
  left: initial;
  animation-delay: 0.4s;
  animation-duration: 5s;
}

span:nth-child(4) {
  top: 0;
  right: 180px;
  left: initial;
  animation-delay: 0.6s;
  animation-duration: 4s;
}

span:nth-child(5) {
  top: 0;
  right: 400px;
  left: initial;
  animation-delay: 0.8s;
  animation-duration: 7s;
}

span:nth-child(6) {
  top: 0;
  right: 600px;
  left: initial;
  animation-delay: 1s;
  animation-duration: 6s;
}

span:nth-child(7) {
  top: 300px;
  right: 0px;
  left: initial;
  animation-delay: 1.2s;
  animation-duration: 4s;
}

span:nth-child(8) {
  top: 0px;
  right: 700px;
  left: initial;
  animation-delay: 1.4s;
  animation-duration: 8s;
}

span:nth-child(9) {
  top: 0px;
  right: 1000px;
  left: initial;
  animation-delay: 0.75s;
  animation-duration: 7s;
}

span:nth-child(9) {
  top: 0px;
  right: 450px;
  left: initial;
  animation-delay: 2.75s;
  animation-duration: 6s;
}

@keyframes saturate {
  0% {
    filter: saturate(1);
    scale: 1;
  }

  50% {
    filter: saturate(1.1);
    scale: 1.05;
  }

  100% {
    filter: saturate(1);
    scale: 1;
  }
}
</style>
