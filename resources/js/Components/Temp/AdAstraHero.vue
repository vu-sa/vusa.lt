<template>
  <div class="full-bleed relative mt-2 h-96 overflow-hidden">

    <img id="adastra-background" class="top-0 z-0 size-full object-cover" src="/images/adastra/adastra-fonas.jpg">
    <img src="/images/adastra/adastra-logo.svg"
      class="absolute right-16 top-12 z-10 my-auto h-1/6 md:inset-y-0 md:h-1/2 lg:right-24 xl:right-32">
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
    <div class="absolute top-0 h-3 w-full bg-gradient-to-b from-white to-transparent dark:from-zinc-900" />
    <div class="absolute bottom-0 h-5 w-full bg-gradient-to-b from-transparent to-white dark:to-zinc-900" />
    <div class="absolute bottom-[15%] left-[7%] z-10 p-4 text-white max-lg:max-w-[55ch]">
      <p class="text-4xl font-extrabold sm:text-5xl">
        Rugsėjo 6 d.
      </p>
      <p class="mt-1 text-xl font-medium sm:text-2xl sm:tracking-wide">
        Universiteto gyvenimo integracijos festivalis
      </p>

      <p class="mt-6 font-light">
        Diskusijos · Koncertas · Dirbtuvės · Kontaktų mugė · ir daugiau!
      </p>
      <a href="https://www.facebook.com/events/484501527532466" class="block sm:w-1/2" target="_blank">
        <button
          class="mt-8 w-full justify-self-end rounded-full bg-gradient-to-b from-orange-500 to-amber-400 px-4 py-2 font-bold text-white shadow-xl transition-shadow">Dalyvauk!</button></a>
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
