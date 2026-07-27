<template>
  <div class="space-y-10 py-4">
    <!-- Hero: kept short so the camps grid is reachable without scrolling -->
    <header
      class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100 p-5 sm:p-6 dark:from-zinc-900 dark:to-zinc-800"
    >
      <div class="absolute -right-20 -top-20 size-56 rounded-full bg-vusa-red/5 blur-3xl" />
      <div class="absolute -bottom-24 -left-16 size-56 rounded-full bg-vusa-yellow/5 blur-3xl" />

      <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
        <img
          src="/images/photos/stovykla.jpg"
          alt=""
          class="hidden aspect-square w-32 shrink-0 rounded-2xl object-cover shadow-md ring-1 ring-zinc-900/5 sm:block lg:w-40 dark:ring-white/10"
          loading="lazy"
        >

        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-wider text-vusa-red">
            {{ $t("Vilniaus universiteto Studentų atstovybės organizuojamos") }}
          </p>
          <h1 class="mt-1 text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-zinc-50">
            {{ summerCampTitle }}
          </h1>
          <p class="mt-2 max-w-prose text-sm leading-6 text-zinc-600 sm:text-base dark:text-zinc-400">
            {{
              $t(
                "Įstojai į Vilniaus universitetą? Nepraleisk pirmojo studentiško nuotykio – pirmakursių stovyklos!"
              )
            }}
          </p>

          <p
            v-if="events.length > 0"
            class="mt-3 text-sm font-medium text-zinc-500 dark:text-zinc-400"
          >
            <span class="tabular-nums">{{ events.length }}</span>
            {{ $tChoice("summerCamps.camp_count", events.length) }}
            ·
            <span class="tabular-nums">{{ campsByTenant.length }}</span>
            {{ $tChoice("summerCamps.unit_count", campsByTenant.length) }}
            · {{ year }}
          </p>
        </div>
      </div>
    </header>

    <!-- Camps -->
    <section>
      <div class="mb-5 flex items-center gap-4">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
          {{ $t("Stovyklos pagal padalinius") }}
        </h2>
        <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ events.length }}</span>
      </div>

      <div v-if="campsByTenant.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <SummerCampCard
          v-for="group in campsByTenant"
          :key="group.tenant.id"
          :tenant="group.tenant"
          :events="group.events"
        />
      </div>

      <div
        v-else
        class="rounded-2xl bg-zinc-50 py-16 text-center ring-1 ring-zinc-200/50 dark:bg-zinc-800/50 dark:ring-zinc-700/50"
      >
        <div
          class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800"
        >
          <IFluentTent24Regular class="size-6 text-zinc-400" />
        </div>
        <p class="text-zinc-600 dark:text-zinc-400">
          {{ $t("Šiais metais stovyklų informacija dar neskelbiama.") }}
        </p>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-500">
          {{ $t("Sek savo padalinio socialinius tinklus ir sužinok pirmas (-a)!") }}
        </p>
      </div>
    </section>

    <!-- About -->
    <section>
      <div class="mb-6 flex items-center gap-4">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
          {{ $t("Daugiau apie stovyklas") }}
        </h2>
        <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
      </div>

      <div class="grid gap-8 lg:grid-cols-5">
        <div class="typography col-span-1 text-base leading-7 lg:col-span-3">
          <template v-if="isCurrentYear">
            <p>
              {{
                $t(
                  "Džiaugiamės, kad pradėjai įdomiausią gyvenimo etapą ir pasirinkai Universitetą, kur Hinc itur ad astra – iš čia kylama į žvaigždes."
                )
              }}
            </p>

            <p>
              {{
                $t(
                  "Prieš prasidedant mokslo metams Tavęs laukia ilgas, bet labai įdomus"
                )
              }}
              <a class="font-bold" target="_blank" href="/pirmakursiams">{{
                $t("susipažinimo")
              }}</a>
              {{ $t("su Vilniaus universitetu etapas.") }}
            </p>

            <p>
              {{
                $t(
                  "Tai puiki galimybė ne tik praplėsti pažinčių ratą, bet ir gauti atsakymus į visus rūpimus klausimus, susijusius su studijomis ar studentišku gyvenimu. Ne iš nuogirdų, interneto platybių ar reklaminių lankstinukų, o iš pirmų lūpų – lygiai tą pačią studijų programą pasirinkusių vyresnių kursų studentų (-čių)."
                )
              }}
            </p>

            <p>
              {{
                $t(
                  "Net kelias dienas truksiančioje pirmakursių stovykloje susirasi bendraminčių visam likusiam gyvenimui – todėl negali pražiopsoti kvietimo įsilieti į VU bendruomenę dar net neprasidėjus studijoms!"
                )
              }}
            </p>
          </template>
          <template v-else>
            <p>
              {{
                $t(
                  "Pirmakursių stovyklos - tai ilgametes tradicijas turintis Vilniaus universiteto studentų atstovybės organizuojamas renginys VU pirmakursiams (-ėms), kuris vyksta kiekvienais metais."
                )
              }}
            </p>
            <p>
              {{ $t("Tačiau dar prieš tai,") }}
              <a class="font-bold" target="_blank" href="/apie">{{
                $t("Vilniaus universiteto Studentų atstovybė (VU SA)")
              }}</a>
              {{
                $t(
                  "kviečia Tave susipažinti su tais (-omis), kurie (-ios) per visus mokslo metus lydės daugiausiai – tai Tavo padalinio,"
                )
              }}
              <Link
                class="font-bold"
                target="_blank"
                :href="
                  route('contacts.category', {
                    type: 'padaliniai',
                    lang: 'lt',
                    subdomain: 'www',
                  })
                "
              >
                {{ $t("kuratoriai (-ės)") }}
              </Link>.
            </p>
          </template>
        </div>

        <aside class="col-span-1 space-y-6 lg:col-span-2">
          <div
            v-if="isCurrentYear"
            class="rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 p-5 ring-1 ring-zinc-200/50 dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50"
          >
            <p class="font-semibold text-zinc-900 dark:text-zinc-100">
              {{
                $t(
                  "Bilietų prekyba ir tikslesnė informacija bus paskelbta vėliau! Į kainą įskaičiuotas transportas į ir iš stovyklos. 🚌"
                )
              }}
            </p>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
              {{
                $t("Sek savo padalinio socialinius tinklus ir sužinok pirmas (-a)!")
              }}
            </p>
          </div>

          <a
            target="_blank"
            href="https://vu.lt/parduotuve/"
            aria-label="Visit VU merchandise store"
            class="block overflow-hidden rounded-2xl ring-1 ring-zinc-200/50 transition-shadow hover:shadow-lg dark:ring-zinc-700/50"
          >
            <img
              :src="isCurrentYear ? '/images/photos/atributika_banner3.jpg' : '/images/photos/atributika_banner2.jpg'"
              alt="VU merchandise and accessories banner"
              class="w-full"
              loading="lazy"
            >
          </a>
        </aside>
      </div>
    </section>

    <!-- Year archive -->
    <section v-if="yearsWhenEventsExist.length > 0">
      <div class="mb-4 flex items-center gap-4">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
          {{ $t("Kitų metų stovyklos") }}
        </h2>
        <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
      </div>

      <div class="flex flex-wrap gap-2">
        <SmartLink
          v-for="eventsYear in yearsWhenEventsExist"
          :key="eventsYear"
          :href="route('pirmakursiuStovyklos', { year: eventsYear, lang: locale })"
        >
          <Button
            :variant="eventsYear === year ? 'default' : 'outline'"
            size="sm"
            class="rounded-full tabular-nums"
          >
            {{ eventsYear }}
          </Button>
        </SmartLink>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import SmartLink from '@/Components/Public/SmartLink.vue';
import SummerCampCard from '@/Components/Public/SummerCamps/SummerCampCard.vue';

type CampTenant = { id: number; alias: string; fullname: string };

const props = defineProps<{
  events: App.Entities.Calendar[];
  year: number;
  yearsWhenEventsExist: number[];
}>();

const page = usePage();
const locale = computed(() => page.props.app.locale);

const isCurrentYear = computed(() => props.year === new Date().getFullYear());

const summerCampTitle = computed(() =>
  isCurrentYear.value
    ? $t('Pirmakursių stovyklos')
    : `${props.year} ${$t('m. pirmakursių stovyklos')}`,
);

/**
 * A faculty can run more than one camp, so cards are keyed by tenant and hold every
 * camp that tenant organises. The controller already orders by tenant, then date.
 */
const campsByTenant = computed(() => {
  const groups = new Map<number, { tenant: CampTenant; events: App.Entities.Calendar[] }>();

  props.events.forEach((event) => {
    const tenant = event.tenant as CampTenant | undefined;
    if (!tenant) return;

    const group = groups.get(tenant.id);

    if (group) {
      group.events.push(event);
    }
    else {
      groups.set(tenant.id, { tenant, events: [event] });
    }
  });

  return [...groups.values()];
});
</script>
