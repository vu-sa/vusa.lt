<template>
  <!-- Hero Section -->
  <HeroSection :title="content.hero.title" :description="content.hero.description"
    image-src="/images/become-a-member/20250510_VUSA-156.webp" image-alt="VU SA students" :overlay-content="{
      title: currentLocale === 'lt' ? '1500+ aktyvių narių' : '1500+ active members',
      subtitle: currentLocale === 'lt' ? 'Prisijunk prie bendruomenės šiandien' : 'Join the community today'
    }">
    <template #buttons>
      <Link :href="currentLocale === 'lt' ? '/registracija/nariu-registracija' : '/en/registration/member-registration'">
      <Button variant="default" size="lg" animation="subtle"
        class="bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 shadow-lg w-full sm:w-auto">
        <User class="w-4 h-4 mr-2" />
        {{ content.callToAction.buttonText }}
      </Button>
      </Link>
      <Button variant="outline" size="lg" animation="subtle"
        class="border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 w-full sm:w-auto"
        @click="scrollToNextSection">
        <ChevronDown class="w-4 h-4 mr-2" />
        {{ currentLocale === 'lt' ? 'Sužinok daugiau' : 'Learn more' }}
      </Button>
    </template>
  </HeroSection>

  <!-- Why Join Section - Carousel -->
  <section id="why-join-section" class="scroll-mt-16 py-12 full-bleed relative">
    <div class="container mx-auto px-4 max-w-6xl relative z-10">
      <SectionHeader
        :title="currentLocale === 'lt' ? 'Kodėl verta prisijungti ir ką gausi?' : 'Why join and what will you get?'"
        :subtitle="currentLocale === 'lt' ? 'Sužinok, kodėl verta prisijungti prie VU SA ir kokius privalumus suteiks narystė' : 'Discover reasons to join VU SA and learn about the benefits of membership'" />

      <Carousel ref="carouselRef" class="w-full max-w-5xl mx-auto" :opts="{
        align: 'start',
        loop: true,
      }" @init-api="(val) => carouselApi = val">
        <CarouselContent>
          <!-- Environment Slide -->
          <CarouselItem>
            <CarouselSlideCard :icon="Users" :badge="currentLocale === 'lt' ? 'Bendruomenė' : 'Community'"
              :title="content.whyJoin.environment.title" :description="content.whyJoin.environment.description"
              image-src="/images/become-a-member/mokymai2025-6.webp" image-alt="Students"
              :image-left="true" />
          </CarouselItem>

          <!-- Impact Slide -->
          <CarouselItem>
            <CarouselSlideCard :icon="TrendingUp" :badge="currentLocale === 'lt' ? 'Poveikis' : 'Impact'"
              :title="content.whyJoin.impact.title" :description="content.whyJoin.impact.description"
              image-src="/images/become-a-member/VU SA 24-25-01.webp" image-alt="Student activities and engagement"
              :image-left="false" />
          </CarouselItem>

          <!-- Growth Slide -->
          <CarouselItem>
            <CarouselSlideCard :icon="BookOpen" :badge="currentLocale === 'lt' ? 'Tobulėjimas' : 'Growth'"
              :title="content.whyJoin.growth.title" :description="content.whyJoin.growth.description"
              image-src="/images/become-a-member/VU SA 24-25-06.webp" image-alt="Students developing skills"
              :image-left="true" />
          </CarouselItem>

          <!-- Training Slide -->
          <CarouselItem>
            <CarouselSlideCard :icon="Award" :badge="currentLocale === 'lt' ? 'Mokymai' : 'Training'"
              :title="content.benefits.training.title" :description="content.benefits.training.description"
              image-src="/images/become-a-member/mokymai2025-3.webp" image-alt="Student training and development"
              :image-left="true" />
          </CarouselItem>

          <!-- Diploma Supplement Slide -->
          <CarouselItem>
            <CarouselSlideCard :icon="ExternalLink" :badge="currentLocale === 'lt' ? 'Diplomas' : 'Diploma'"
              :title="content.benefits.diploma.title" :description="content.benefits.diploma.description"
              image-src="/images/become-a-member/diplomas.webp" image-alt="University diploma and achievements"
              :image-left="false" />
          </CarouselItem>

          <!-- Scholarship Slide -->
          <CarouselItem>
            <CarouselSlideCard :icon="DollarSign" :badge="currentLocale === 'lt' ? 'Stipendija' : 'Scholarship'"
              :title="content.benefits.scholarship.title" :description="content.benefits.scholarship.description"
              image-src="/images/become-a-member/mokymai2025-7.webp"
              image-alt="Student scholarship and financial support" :image-left="true" />
          </CarouselItem>
        </CarouselContent>

        <!-- Navigation buttons -->
        <CarouselPrevious
          class="hidden sm:flex -left-12 bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-500 text-zinc-900 dark:text-zinc-100"
          @click="restartCarouselAutoplay" />
        <CarouselNext
          class="hidden sm:flex -right-12 bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-500 text-zinc-900 dark:text-zinc-100"
          @click="restartCarouselAutoplay" />
      </Carousel>

      <!-- Photo Preview Navigation -->
      <div class="flex flex-wrap justify-center mt-2 xl:mt-8 gap-3">
        <button v-for="(slide, index) in carouselSlides" :key="index" class="relative group transition-all duration-200"
          :class="{ '': currentSlide === index }"
          @click="() => { carouselApi?.scrollTo(index); restartCarouselAutoplay(); }">
          <img :src="slide.image" :alt="slide.alt"
            class="w-14 h-10 sm:w-16 sm:h-12 object-cover rounded-lg shadow-sm transition-all duration-200" :class="{
              'opacity-100 scale-105': currentSlide === index,
              'opacity-70 hover:opacity-90 scale-100 hover:scale-105': currentSlide !== index
            }" loading="lazy">
          <!-- Icon overlay for active slide -->
          <div v-if="currentSlide === index"
            class="absolute inset-0 bg-zinc-900/20 rounded-lg flex items-center justify-center">
            <component :is="slide.icon" class="w-3 h-3 sm:w-4 sm:h-4 text-white drop-shadow-sm" />
          </div>
          <!-- Category label -->
          <div
            class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs text-zinc-600 font-medium opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
            {{ slide.label }}
          </div>
        </button>
      </div>
    </div>
  </section>

  <RCNumberSection class="full-bleed" :element="numberElement" />

  <!-- Subtle subtitle with last updated info and badge -->
  <div class="text-center -mt-6 mb-8">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-50/50 dark:bg-zinc-800/30">
      <div class="relative">
        <div class="w-1.5 h-1.5 bg-green-500 rounded-full" />
        <div class="absolute inset-0 w-1.5 h-1.5 bg-green-400 rounded-full animate-ping opacity-20" />
        <div class="absolute inset-0 w-1.5 h-1.5 bg-green-300 rounded-full animate-ping opacity-10"
          style="animation-delay: 1s" />
      </div>
      <span class="text-xs text-zinc-500 dark:text-zinc-400 italic">
        {{ statsSubtitle }}
      </span>
    </div>
  </div>

  <!-- Turtle Section - Lijana the Mascot -->
  <section class="py-16 2xl:rounded-lg bg-white dark:bg-zinc-950 full-bleed relative">
    <div class="container mx-auto px-4 max-w-6xl relative z-10">
      <div class="grid lg:grid-cols-2 gap-12 md:gap-16 items-center">
        <div class="space-y-4 md:space-y-6">
          <div
            class="inline-flex items-center gap-2 px-3 py-1 bg-vusa-yellow/10 dark:bg-vusa-yellow/20 rounded-full text-sm text-vusa-yellow-dark dark:text-vusa-yellow mb-4">
            <div class="w-2 h-2 bg-vusa-yellow rounded-full" />
            {{ currentLocale === 'lt' ? 'Maskotė' : 'Mascot' }}
          </div>
          <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight">
            {{ currentLocale === 'lt' ? 'Susipažink su Lijana!' : 'Meet Lijana!' }}
          </h2>
          <div class="space-y-3 md:space-y-4">
            <p class="text-sm sm:text-base md:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
              <span v-if="currentLocale === 'lt'">
                Mūsų vėžlė Lijana (vardą keičia <i>beveik</i> kasmet) yra ilgiausiai veikianti VU SA narė. Kaip ir jinai, mes tikime, kad nuoseklumas ir kantrybė lemia sėkmę – ne visada reikia skubėti, svarbiausia judėti teisinga kryptimi.
              </span>
              <span v-else>
                Our turtle Lijana (the name changes <i>almost</i> every year) is the longest-serving VU SA member. Like her, we believe that consistency and patience determine success – you don't always need to rush, the important thing is to move in the right direction.
              </span>
            </p>
            <p class="text-sm sm:text-base md:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
              {{ currentLocale === 'lt' ? 'Lijana simbolizuoja mūsų bendruomenės vieningumą – ilgalaikį įsipareigojimą studentų (-čių) gerovei. Užeik susipažinti su ja VU SA Centriniame biure Observatorijos kieme, Universiteto g. 3!' : 'Lijana symbolizes our community\'s unity – long-term commitment to student welfare. Come visit her at the VU SA Central Office in the Observatory Yard, Universiteto st. 3!' }}
            </p>
          </div>
          <div class="flex items-center gap-4 pt-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
              <div class="w-2 h-2 bg-vusa-yellow rounded-full animate-pulse" />
              {{ currentLocale === 'lt' ? 'Aktyvi VU SA narė nuo 2003 m.' : 'Active VU SA member since 2003' }}
            </div>
          </div>
        </div>
        <div class="relative order-first lg:order-last">
          <ImageWithDecorations src="/images/become-a-member/20250510_VUSA-144.webp" alt="VU SA turtle mascot Lijana"
            height-class="h-64 md:h-80 lg:h-96" :decorations="[
              { type: 'line', position: 'top-right', size: 'lg', color: 'vusa-yellow', opacity: 60 },
            ]" :overlay-content="{
              title: currentLocale === 'lt' ? 'Faktai' : 'Fun Facts',
              subtitle: currentLocale === 'lt' ? 'Mėgsta salotų lapus ir studentų (-čių) renginius' : 'Loves lettuce leaves and student events'
            }" overlay-position="bottom-4 left-4" overlay-size="max-w-48" overlay-style="backdrop" loading="lazy" />
        </div>
      </div>
    </div>
  </section>

  <!-- Activity Areas Section - Card Stack Carousel -->
  <section class="py-16 bg-zinc-50 dark:bg-zinc-900 full-bleed relative">
    <!-- Subtle decorative elements -->
    <!-- <DecorativeElement type="line" position="top-left" size="md" color="vusa-red" :opacity="20" /> -->
    <!-- <DecorativeElement type="square" position="bottom-right" size="sm" color="vusa-yellow" :opacity="30" -->
    <!--   :rotation="true" /> -->

    <div class="container mx-auto px-4 max-w-6xl relative z-10">
      <SectionHeader :title="content.activityAreas.title" :subtitle="content.activityAreas.subtitle" />

      <!-- Card Stack Component -->
      <CardStack :cards="activityCards" :autoplay="true" :autoplay-delay="5000"
        :hint-text="currentLocale === 'lt' ? 'Spustelėk kortelę arba indikatorių' : 'Click card or indicator'" />
    </div>
  </section>

  <!-- Photo Gallery Section -->
  <section class="py-12 full-bleed relative">
    <div class="container mx-auto px-4 max-w-6xl relative z-10">
      <SectionHeader 
        :title="currentLocale === 'lt' ? 'Mūsų veikla kadruose' : 'Our activities in frames'"
        :subtitle="currentLocale === 'lt' ? 'Pažvelk į mūsų bendruomenės kasdienybę ir renginius' : 'Take a look at our community\'s daily life and events'" />
      
      <PhotoGalleryGrid :images="galleryImages" />
    </div>
  </section>

  <!-- Call to Action - Clean and effective -->
  <section
    class="py-16 bg-zinc-50 dark:bg-zinc-900 full-bleed relative overflow-hidden">
    <div class="container mx-auto px-4 max-w-4xl text-center space-y-6 md:space-y-8 relative z-10">
      <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight">
        {{ content.callToAction.title }}
      </h2>
      <Link :href="currentLocale === 'lt' ? '/registracija/nariu-registracija' : '/en/registration/member-registration'">
      <Button variant="default" size="lg" animation="subtle"
        class="bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 border-zinc-900 dark:border-zinc-100 hover:border-zinc-800 dark:hover:border-zinc-200">
        <User class="w-4 h-4 mr-2" />
        {{ content.callToAction.buttonText }}
      </Button>
      </Link>
    </div>
  </section>

  <!-- FAQ Section - Clean and accessible -->
  <section class="py-16 bg-zinc-50 dark:bg-zinc-900 full-bleed relative">
    <div class="container mx-auto px-4 max-w-4xl relative z-10">
      <SectionHeader :title="currentLocale === 'lt' ? 'Dažnai užduodami klausimai' : 'What do you ask us?'" />
      <Accordion type="single" collapsible class="space-y-4">
        <AccordionItem v-for="(item, index) in faqData" :key="index" :value="`item-${index + 1}`"
          class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-800">
          <AccordionTrigger
            class="px-4 sm:px-6 py-3 sm:py-4 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-left [&[data-state=open]]:bg-zinc-50 dark:[&[data-state=open]]:bg-zinc-700">
            <span class="font-medium text-zinc-900 dark:text-zinc-100 text-sm sm:text-base">{{ item.question }}</span>
          </AccordionTrigger>
          <AccordionContent class="px-4 sm:px-6 pb-4 sm:pb-6 pt-2">
            <div
              class="text-zinc-600 mt-3 dark:text-zinc-400 leading-relaxed text-sm sm:text-base prose-sm prose-zinc dark:prose-invert [&_a]:text-vusa-red [&_a]:decoration-vusa-red dark:[&_a]:text-red-400 dark:[&_a]:decoration-red-400 [&_a:hover]:text-red-700 dark:[&_a:hover]:text-red-300"
              v-html="item.answer" />
          </AccordionContent>
        </AccordionItem>
      </Accordion>
    </div>
  </section>

  <!-- Slogan Section - Final inspiration -->
  <section class="py-12 mb-8 full-bleed">
    <div class="container mx-auto px-4 max-w-4xl text-center">
      <div class="space-y-4">
        <h2 class="text-2xl sm:text-3xl text-zinc-900 dark:text-zinc-50 md:text-4xl lg:text-5xl font-bold leading-tight tracking-tight">
          {{ currentLocale === 'lt' ? 'Vieningai Už Studentų ir Studenčių Ateitį!' : 'United for the Future of Students!' }}
        </h2>
        <div class="w-24 h-1 bg-zinc-900/80 dark:bg-zinc-50/80 mx-auto rounded-full" />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import {
  User,
  ChevronDown,
  BookOpen,
  Palette,
  TrendingUp,
  Users,
  Award,
  DollarSign,
  ExternalLink,
  ChevronRight,
  RefreshCw,
  Newspaper,
  CheckCircle
} from 'lucide-vue-next';

import RCNumberSection from '@/Components/RichContent/RCNumberStatSection/RCNumberSection.vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
import { Badge } from '@/Components/ui/badge';
import Button from '@/Components/ui/button/Button.vue';
import CardStack from '@/Components/ui/CardStack.vue';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import HeroSection from '@/Components/ui/HeroSection.vue';
import SectionHeader from '@/Components/ui/SectionHeader.vue';
import CarouselSlideCard from '@/Components/ui/CarouselSlideCard.vue';
import DecorativeElement from '@/Components/ui/DecorativeElement.vue';
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import PhotoGalleryGrid from '@/Components/ui/PhotoGalleryGrid.vue';
import type { NumberStatSection } from '@/Types/contentParts';
import { useNewsFetch } from '@/Services/ContentService';
import { formatStaticTime } from '@/Utils/IntlTime';

// Define props for membership statistics
interface Props {
  membershipStats: {
    representative_bodies: number;
    student_representatives: number;
    cached_at: string;
  };
}

const props = defineProps<Props>();

// Use the news fetch composable
const { news: newsData, loading } = useNewsFetch();
const page = usePage();

// Carousel functionality
const carouselApi = ref();
const currentSlide = ref(0);
let carouselAutoplayInterval: NodeJS.Timeout | null = null;

// Card stack functionality for activity areas - simplified since moved to component
const activityCards = computed(() => [
  {
    icon: BookOpen,
    title: content.value.activityAreas.academic.title,
    description: content.value.activityAreas.academic.description
  },
  {
    icon: Palette,
    title: content.value.activityAreas.selfExpression.title,
    description: content.value.activityAreas.selfExpression.description
  },
  {
    icon: TrendingUp,
    title: content.value.activityAreas.competency.title,
    description: content.value.activityAreas.competency.description
  }
]);

// Carousel autoplay functionality
const startCarouselAutoplay = () => {
  if (!carouselApi.value || carouselAutoplayInterval) return;

  carouselAutoplayInterval = setInterval(() => {
    carouselApi.value?.scrollNext();
  }, 8000); // 8 seconds between slides
};

const stopCarouselAutoplay = () => {
  if (carouselAutoplayInterval) {
    clearInterval(carouselAutoplayInterval);
    carouselAutoplayInterval = null;
  }
};

const restartCarouselAutoplay = () => {
  stopCarouselAutoplay();
  setTimeout(startCarouselAutoplay, 8000); // Restart after 8 seconds of user interaction
};

// Carousel slides data
const carouselSlides = computed(() => [
  {
    image: '/images/become-a-member/mokymai2025-6.webp',
    alt: 'Students having a conversation',
    icon: Users,
    label: currentLocale === 'lt' ? 'Bendruomenė' : 'Community'
  },
  {
    image: '/images/become-a-member/VU SA 24-25-01.webp',
    alt: 'Student activities and engagement',
    icon: TrendingUp,
    label: currentLocale === 'lt' ? 'Poveikis' : 'Impact'
  },
  {
    image: '/images/become-a-member/VU SA 24-25-06.webp',
    alt: 'Students developing skills',
    icon: BookOpen,
    label: currentLocale === 'lt' ? 'Tobulėjimas' : 'Growth'
  },
  {
    image: '/images/become-a-member/mokymai2025-3.webp',
    alt: 'Student training and development',
    icon: Award,
    label: currentLocale === 'lt' ? 'Mokymai' : 'Training'
  },
  {
    image: '/images/become-a-member/diplomas.webp',
    alt: 'University diploma and achievements',
    icon: ExternalLink,
    label: currentLocale === 'lt' ? 'Diplomas' : 'Diploma'
  },
  {
    image: '/images/become-a-member/mokymai2025-7.webp',
    alt: 'Student scholarship and financial support',
    icon: DollarSign,
    label: currentLocale === 'lt' ? 'Stipendija' : 'Scholarship'
  }
]);

// Watch for carousel API changes and set up slide tracking
watch(carouselApi, (api) => {
  if (!api) return;

  // Set initial slide
  currentSlide.value = api.selectedScrollSnap();

  // Listen for slide changes
  api.on('select', () => {
    currentSlide.value = api.selectedScrollSnap();
  });

  // Start autoplay after carousel is initialized
  startCarouselAutoplay();

  // Handle user interactions with carousel - pause and restart autoplay
  api.on('pointerDown', stopCarouselAutoplay);
  api.on('pointerUp', restartCarouselAutoplay);
});

// Lifecycle hooks for cleanup
onMounted(() => {
  // Autoplay will start when carousel API is ready via watcher
});

onUnmounted(() => {
  stopCarouselAutoplay();
});

// Helper function to create news route
const getNewsRoute = (item: any) => {
  return route('news', {
    lang: item.lang,
    news: item.permalink ?? '',
    newsString: 'naujiena',
    subdomain: page.props.tenant?.subdomain ?? 'www',
  });
};

// Helper function to format dates
const formatDate = (dateString: string) => {
  return formatStaticTime(new Date(dateString), { year: "numeric", month: "long", day: "numeric" }, page.props.app.locale);
};

// News archive route
const newsArchiveRoute = computed(() => {
  return route('newsArchive', {
    subdomain: page.props.tenant?.subdomain ?? 'www',
    lang: page.props.app.locale === 'lt' ? 'lt' : 'en',
    newsString: page.props.app.locale === 'lt' ? 'naujienos' : 'news',
  });
});

// Get current locale
const currentLocale = usePage().props.app.locale;

// Scroll to next section function
const scrollToNextSection = () => {
  const nextSection = document.getElementById('why-join-section');
  if (nextSection) {
    nextSection.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    });
  }
};

// Content object for both languages
const content = computed(() => {
  const isLithuanian = currentLocale === 'lt';

  return {
    hero: {
      title: isLithuanian
        ? 'Prisijunk prie VU&nbsp;SA bendruomenės!'
        : 'Join the VU&nbsp;SR community!',
      description: isLithuanian
        ? 'Atrask naujas galimybes, rask bendraminčių (-ių) ir darykime pokyčius kartu!'
        : 'Discover new opportunities, find like-minded people, and let\'s make changes together!',
      buttonText: isLithuanian ? 'Tapk VU SA nariu (-e)' : 'Become a VU SA member',
      buttonAction: scrollToNextSection
    },
    whyJoin: {
      environment: {
        title: isLithuanian
          ? 'Ar ieškai naujos aplinkos ir draugų?'
          : 'Looking for a new environment and friends?',
        description: isLithuanian
          ? 'Vilniaus universiteto Studentų atstovybė (VU SA) – tai bendruomenė, jungianti visus Universiteto fakultetus. Stiprioje, motyvuotoje aplinkoje rasi bendraminčių (-ių) bendruomenę ir draugus (-es), kurie (-ios) dalijasi panašiais tikslais ir vertybėmis.'
          : 'Vilnius University Student Representation (VU SR) is a community that brings together all University faculties. In a strong, motivated environment, you will find a community of like-minded people and friends who share similar goals and values.'
      },
      impact: {
        title: isLithuanian
          ? 'Ar nori palikti prasmingą pokytį?'
          : 'Want to make a meaningful change?',
        description: isLithuanian
          ? 'VU SA nariai (-ės) aktyviai formuoja Universiteto ateitį – dalyvauja sprendimų priėmime, inicijuoja pokyčius studijų kokybėje, infrastruktūroje ir studentų (-čių) gyvenime. Tavo idėjos gali paveikti tūkstančių studentų (-čių) kasdienybę.'
          : 'VU SA members actively shape the future of the University - participate in decision-making, initiate changes in study quality, infrastructure and student life. Your ideas can affect the daily lives of thousands of students.'
      },
      growth: {
        title: isLithuanian
          ? 'Ar sieki augti karjeroje ir kaip asmenybė?'
          : 'Seeking to grow in career and as a person?',
        description: isLithuanian
          ? 'Kartu su komanda augsi kaip asmenybė – atrasi savo stiprybes ir mėgstamas sritis. Galbūt atrasi patinkančią karjeros kryptį, o profesinį gyvenimą pradėsi su stipresniu CV ir vertingų įgūdžių bagažu.'
          : 'Together with the team, you will grow as a person - discover your strengths and favorite areas. You might discover a career direction you like, and start your professional life with a stronger CV and a valuable skills arsenal.'
      }
    },
    activityAreas: {
      title: isLithuanian
        ? 'VU SA strateginės veiklos kryptys'
        : 'VU SA strategic activity directions',
      subtitle: isLithuanian
        ? 'Mes veikiame trijose pagrindinėse strateginėse srityse, kurios formuoja universiteto bendruomenės ateitį'
        : 'We operate in three main strategic areas that shape the future of the university community',
      academic: {
        title: isLithuanian
          ? 'Kokybiškos studijos ir joms pritaikyta aplinka'
          : 'Quality studies and adapted environment',
        description: isLithuanian
          ? 'Prisidėk prie personalizuotų studijų sąlygų kūrimo – studijų programų tobulinimo, dalykų pasirinkimo laisvės, tarpdiscipliniškumo ir tarptautinės patirties užtikrinimo. Padėk gerinti fizinę bei skaitmeninę infrastruktūrą ir akademinį konsultavimą.'
          : 'Contribute to creating conditions for personalized studies – improving study programs, ensuring freedom of subject choice, interdisciplinarity and international experience. Help improve physical and digital infrastructure and academic consultation.'
      },
      selfExpression: {
        title: isLithuanian
          ? 'Stipri organizacija'
          : 'Strong organization',
        description: isLithuanian
          ? 'Prisidėk prie efektyvių organizacijos procesų kūrimo – padėk užtikrinti, kad visi studentai (-ės) galėtų dalyvauti saviraiškos ir atstovavimo veiklose. Kelk narių (-ių) kompetencijas, kur palanką aplinką ilgalaikiam tobulėjimui ir suteik našiai veiklai reikalingus išteklius.'
          : 'Contribute to creating effective organizational processes – help ensure that all students can participate in self-expression and representation activities. Develop member competencies, create a favorable environment for long-term improvement and provide resources needed for productive activities.'
      },
      competency: {
        title: isLithuanian
          ? 'Darni universitetinė bendruomenė'
          : 'Sustainable university community',
        description: isLithuanian
          ? 'Prisidėk prie vieningos ir iniciatyvios bendruomenės kūrimo – stiprink tarpusavio ryšius, skatink lyderystę ir aktyvų dalyvavimą. Padėk integruoti darnaus vystymosi principus į universiteto veiklas, plėtoti žaliąją infrastruktūrą ir didinti bendruomenės ekologinį sąmoningumą.'
          : 'Contribute to building a united and initiative community – strengthen mutual relationships, encourage leadership and active participation. Help integrate sustainable development principles into university activities, develop green infrastructure and increase community environmental awareness.'
      }
    },
    values: {
      title: isLithuanian
        ? 'Mūsų vertybės sutampa?'
        : 'Do our values align?',
      subtitle: isLithuanian
        ? 'Šios vertybės vienija mūsų bendruomenę ir formuoja mūsų veiklos principus'
        : 'These values unite our community and shape our operational principles',
      ecology: {
        title: isLithuanian ? 'Ekologiškumas' : 'Eco-consciousness',
        description: isLithuanian
          ? 'Ekologiškumas neturi būti tik madinga sąvoka, bet ir mūsų kasdienė praktika ir atsakomybė. Net mažiausi veiksmai gali tapti dideliais pokyčiais, kaip mūsų švaresnio ir tvaresnio žalio universiteto tikslas.'
          : 'Eco-consciousness should not just be a trendy concept, but also our daily practice and responsibility. Even the smallest actions can become big changes, like our goal of a cleaner and more sustainable green university.'
      },
      internationality: {
        title: isLithuanian ? 'Tarptautiškumas' : 'Internationality',
        description: isLithuanian
          ? 'Mes tikime tarptautiškumu akademinėje veikloje, bet nereikia net išvykti į ERASMUS+, kad svetimų kalbų išgirstum Universiteto koridoriuose. Atstovybėje nebijome kalbėti antrąja ar trečiąja kalba, jog visi (-os) jaustųsi saugiai galėdami (-os) kitus suprasti, jog visi (-os) Universiteto studentai (-ės) galėtų įsitraukti į VU SA veiklas.'
          : 'We believe in the international nature of academic activities. You don\'t even need to go on ERASMUS+ to hear foreign languages in University corridors. In the representation, we\'re not afraid to speak a second or third language so that everyone feels safe and can participate in VU SA activities.'
      },
      inclusivity: {
        title: isLithuanian ? 'Įtraukumas' : 'Inclusivity',
        description: isLithuanian
          ? 'Esame skirtingi (-os), įvairūs (-ios) – ir tai yra gražu. Universiteto bendruomenę sudaro LGBTQ+, žmonės su negalia (matoma ir nematoma), socio-ekonomiškai besiskiriantys (-čios) studentai (-ės). Dažnai mažumos interesai nukenčia arba nėra dar atliepti, todėl turime visi (-os) aktyviai juos atstovauti.'
          : 'We are different, diverse - and that is beautiful. The University community consists of LGBTQ+ people, people with disabilities, socio-economically different students. Often minority interests suffer or are not yet addressed, so we must all actively represent them.'
      }
    },
    benefits: {
      title: isLithuanian
        ? 'Ką gausi tapęs (-usi) VU SA nariu (-e)'
        : 'What you get as a VU SA member',
      subtitle: isLithuanian
        ? 'Narystė atsiveria daugybę galimybių asmeniniam ir profesiniam tobulėjimui'
        : 'Membership opens up numerous opportunities for personal and professional development',
      training: {
        title: isLithuanian ? 'Mokymai' : 'Training',
        description: isLithuanian
          ? 'Tapus nariu (-e) tavęs laukia studentų (-čių) atstovų (-ių), kuratorių (-ių) ir kiti mokymai, skirti įgyti vertingų žinių ir įgūdžių: nuo komandinio darbo iki Lietuvos aukštojo mokslo sistemos supratimo. Šie mokymai pravers ne tik veikloje atstovybėje, bet ir tavo asmeniniame bei profesiniame kelyje.'
          : 'As a member, you will have access to student representative, curator and other training designed to gain valuable knowledge and skills: from teamwork to understanding the Lithuanian higher education system. This training will be useful not only in representation activities, but also in personal and professional life.'
      },
      diploma: {
        title: isLithuanian ? 'Priedas prie diplomo' : 'Diploma supplement',
        description: isLithuanian
          ? 'Aktyviai dalyvaudamas (-a) Vilniaus universiteto Studentų atstovybės veiklose gali gauti priedą prie savo diplomo! Šis dokumentas įvertins tavo savanorystės valandas ir įgytą patirtį – privalumas darbo rinkoje.'
          : 'By actively participating in VU SA activities, you can get a supplement to your diploma! This document will evaluate your volunteer hours and gained experience - an advantage in the job market.'
      },
      scholarship: {
        title: isLithuanian ? 'Stipendija už savanorystę' : 'Volunteer scholarship',
        description: isLithuanian
          ? 'Už aktyvią ir į rezultatą orientuotą savanorišką veiklą kiekvieną semestrą gali gauti VU visuomeninės veiklos stipendiją, įvertinančią tavo laiką ir pastangas, iniciatyvas bei pasiekimus, prisidedančius prie Vilniaus universiteto bendruomenės stiprinimo.'
          : 'For active and result-oriented volunteer work, each semester you can receive a VU public activity scholarship that evaluates your time and efforts, initiatives and achievements that contribute to strengthening the Vilnius University community.'
      }
    },
    callToAction: {
      title: isLithuanian
        ? 'Vilniaus universiteto Studentų atstovybė laukia tavęs – veikime, kurkime ir tobulėkime kartu!'
        : 'Vilnius University Student Representation is waiting for you - let\'s work, create and grow together!',
      buttonText: isLithuanian ? 'Tapk nariu (-e)' : 'Become a member'
    }
  };
});

// FAQ data with bilingual support
const faqData = computed(() => {
  const isLithuanian = currentLocale === 'lt';

  if (isLithuanian) {
    return [
      {
        question: 'Kaip galiu tapti VU SA nariu (-e)?',
        answer: 'Tapti VU SA nariu (-e) galėsi užpildydamas (-a) narystės registracijos formą mūsų svetainėje arba apsilankydamas (-a) VU SA padalinyje, viename iš Universiteto padalinių. Narystė atvira visiems įstojusiems Vilniaus universiteto studentams (-ėms)! Oficialiai nariu (-e) tapsi išlaikęs (-usi) universiteto žinių testą, tačiau į veiklas gali įsitraukti ir anksčiau.'
      },
      {
        question: 'Koks skirtumas tarp VU SA nario (-ės) ir studentų atstovo (-ės)?',
        answer: 'VU SA nariai (-ės) kviečiami (-os) dalyvauti visuose renginiuose, veiklose, kitose Universiteto bei studentų savivaldos gyvenime. Studentų atstovas (-ė) – tai narys (-ė), kurį (-ią) bendruomenė išrenka atstovauti studentams (-ėms) Universiteto ar atstovybės valdymo organuose. Jei nori dar aktyviau įsitraukti, vėliau galėsi kandidatuoti į atstovus (-es)!'
      },
      {
        question: 'Koks yra universiteto žinių testas ir kaip jį išlaikyti?',
        answer: 'Universiteto žinių testas susideda iš įvairių socialinių akademinių klausimų, kad mūsų nariai (-ės) būtų išprusę (-usios) tam tikromis temomis ir galėtų tinkamai atstovauti universitete bei už jo ribų. Suteikiame daug paramos ruošiantis testui, galima daryti kelis bandymus, o dauguma norinčių tapti nariais (-ėmis) jį sėkmingai išlaiko. Testas nėra kliūtis – tai galimybė geriau pažinti universitetinę kultūrą!'
      },
      {
        question: 'Dar nežinau, ar tikrai noriu tapti nariu (-e). Ar galiu prisijungti vėliau?',
        answer: 'Žinoma, galėsi! Tapti VU SA nariu (-e) gali bet kuriuo metu per visus savo studijų metus. Tačiau nepamiršk – net ir nesant aktyviu (-ia) nariu (-e) visus metus, turi teisę balsuoti savo padalinio ataskaitinėje-rinkiminėje konferencijoje (dažniausiai vyksta pavasarį), kurioje sprendžiame svarbius organizacijos klausimus. Kuo anksčiau prisijungsi, tuo daugiau galimybių turėsi paveikti ir pamatyti mūsų veiklą!'
      },
      {
        question: 'O jei pajusiu, kad tai ne man – ar galiu pasitraukti?',
        answer: 'Absoliučiai! Nėra jokių įsipareigojimų ilgam laikui. Gali bet kada nuspręsti baigti dalyvavimą – tiesiog pranešk mums elektroniniu paštu arba asmeniškai. Mes suprantame, kad studentų prioritetai keičiasi. Durys visada bus atviros, jei kada nors norėsi grįžti!'
      },
      {
        question: 'Ar narystė kažkuo mokama?',
        answer: 'Ne, narystė visiškai nemokama! VU SA finansuojama iš universiteto biudžeto ir kitų šaltinių, todėl nereikia mokėti jokių mokesčių ar įnašų.'
      },
      {
        question: 'Kiek savo laiko turėčiau skirti VU SA veiklai?',
        answer: 'Viskas priklauso nuo tavęs - gali dalyvauti tik renginiuose (kelias valandas per mėnesį), prisidedant prie jų organizavimo arba aktyviai įsitraukti į projektus (5-10 val. per savaitę). Niekas tavęs nespaudžia – pats pasirenki pagal savo galimybes ir norą.'
      },
      {
        question: 'Kokias kompetencijas galėčiau įgyti?',
        answer: 'VU SA veikloje lavínsi lyderystės, komandinio darbo, komunikacijos, projektų valdymo, viešojo kalbėjimo ir derybų įgūdžius. Taip pat turi galimybę išmokti dirbti su įvairiomis programomis ir technologijomis. Šie įgūdžiai pravers ne tik čia, bet ir visame tavo gyvenime!'
      },
      {
        question: 'O jei aš ne pirmakursis (-ė)?',
        answer: 'Net geriau! Vyresnių kursų studentus (-es) ypač vertiname dėl jūsų patirties ir perspektyvos. Niekada nevėlu pradėti dalyvauti, ir tikrai turėsi ką duoti bendruomenei.'
      },
      {
        question: 'Ką daro studentų atstovai (-ės)?',
        answer: 'Studentų atstovai (-ės) dalyvauja universiteto valdymo organuose, gina studentų (-čių) interesus ir formuoja studijų politiką. Jie (-os) taip pat, kaip nariai (-ės), dažnai prisideda prie renginių organizavimo, koordinuoja projektus ir atstovauja studentams (-ėms) viešajame diskurse. Takoskyra tarp nario (-ės) ir atstovo (-ės) praktikoje dažnai yra neryški, nes abu aktyviai dalyvauja veikloje.'
      },
      {
        question: 'Ar galėsiu derinti VU SA veiklą su darbu ar praktika?',
        answer: 'Žinoma, mūsų nariai (-ės) sėkmingai derina atstovavimą su darbu ar praktika. Be to, VU SA veikla gali padėti tau rasti geresnį darbą – suteiks vertingų kontaktų ir patirties, kurią labai vertina darbdaviai (-ės). Tik prisimink, kad studijos visada yra prioritetas, ir mes padėsime tau rasti balansą!'
      },
      {
        question: 'Kaip VU SA veikla padės man karjeroje?',
        answer: 'VU SA narystė suteiks tau praktinės vadovavimo patirties, išplės profesinius kontaktus ir išlavins komunikacijos įgūdžius. Daugelis mūsų alumni sako, kad VU SA patirtis buvo viena svarbiausių jų karjeros pradžioje!'
      },
      {
        question: 'Ar galėsiu inicijuoti savo projektus?',
        answer: 'Taip! Mes labai skatiname narių iniciatyvumą ir remiame naujų projektų kūrimą. Gausi metodinę pagalbą, finansavimo galimybes ir galėsi projektui įgyvendinti. Daugelis dabartinių mūsų projektų prasidėjo būtent nuo narių (-ių) idėjų!'
      },
      {
        question: 'Ar turėsiu žodį sprendimų priėmime?',
        answer: 'Taip! VU SA sprendimai priimami demokratiškai – per balsavimus Taryboje, Parlamente ir ataskaitinėse-rinkiminėse konferencijose. Kaip narys (-ė) turėsi teisę išreikšti nuomonę, siūlyti sprendimus ir balsuoti. Skatinamas atviras dialogas – tavo balsas bus išgirstas! <a href="/vu-sa-struktura" class="text-vusa-red hover:text-red-700 underline transition-colors">Sužinok daugiau apie VU SA struktūrą</a>.'
      },
      {
        question: 'Kokių renginių ir projektų galėčiau tikėtis?',
        answer: 'Mūsų veikla labai įvairi – nuo studijų kokybės gerinimo projektų iki kultūrinių renginių organizavimo. Dalyvausi diskusijose su universiteto vadovybe, organizuosi studentų (-čių) renginius, vykdysi tyrimus, kurdamas (-a) iniciatyvas darnumui. Kiekvienam (-ai) atsiras kažkas įdomaus!'
      },
      {
        question: 'Ką daryti, jei turiu klausimų ar problemų?',
        answer: 'Drąsiai kreipkis! Gali rašyti bet kuriam (-iai) VU SA nariui (-ei), kurį (-ią) pažįsti, siųsti el. laišką savo padalinio komandai (kontaktus rasi mūsų tinklalapyje), ateiti į mūsų biurą universiteto miestelyje arba užpildyti kontaktų formą svetainėje. Visada yra kas nors pasirengęs (-usi) tau padėti – esame čia vienas kitam!'
      }
    ];
  } else {
    return [
      {
        question: 'How can I become a VU SA member?',
        answer: 'You can become a VU SA member by filling out the membership registration form on our website or visiting a VU SA unit at one of the University divisions. Membership is open to all enrolled Vilnius University students! You become an official member after passing a university knowledge test, but you can get involved in activities before that.'
      },
      {
        question: 'What is the difference between a VU SA member and a student representative?',
        answer: 'VU SA members are invited to participate in all events, activities, and other aspects of University and student self-government life. A student representative is a member elected by the community to represent students in University or representation governance bodies. If you want to get more involved, you can later run for representative positions!'
      },
      {
        question: 'What is the university knowledge test and how do I pass it?',
        answer: 'The university knowledge test consists of various social academic questions to ensure our members are knowledgeable about certain topics and can represent properly at the university and beyond. We provide ample support for test preparation, multiple attempts are possible, and most people wanting to become members pass it successfully. The test isn\'t a barrier – it\'s an opportunity to better understand university culture!'
      },
      {
        question: 'I\'m not sure if I really want to become a member. Can I join later?',
        answer: 'Of course you can! You can become a VU SA member at any time during your studies. However, don\'t forget – even without being an active member all year, you have the right to vote at your unit\'s annual reporting-election conference (usually held in spring), where we decide important organizational matters. The sooner you join, the more opportunities you\'ll have to influence and see our activities!'
      },
      {
        question: 'What if I feel like it\'s not for me – can I leave?',
        answer: 'Absolutely! There are no long-term commitments. You can decide to stop participating at any time – just let us know by email or in person. We understand that student priorities change. The doors will always be open if you ever want to come back!'
      },
      {
        question: 'Is membership paid?',
        answer: 'No, membership is completely free! VU SA is funded from the university budget and other sources, so you don\'t need to pay any fees or contributions.'
      },
      {
        question: 'How much of my time should I dedicate to VU SA activities?',
        answer: 'It all depends on you – you can just participate in events (a few hours per month), contribute to organizing them, or actively engage in projects (5-10 hours per week). No one is pressuring you – you choose according to your capabilities and desire.'
      },
      {
        question: 'What if I\'m an international student?',
        answer: 'Great! We especially welcome international students. We have English-speaking members and activities, and many processes take place in two languages. Your international experience is very valuable to our community, and you\'ll help us become even more diverse!'
      },
      {
        question: 'What competencies could I develop?',
        answer: 'In VU SA activities you\'ll develop leadership, teamwork, communication, project management, public speaking and negotiation skills. You also have the opportunity to learn to work with various programs and technologies. These skills will be useful not only here, but throughout your life!'
      },
      {
        question: 'What if I\'m not a first-year student?',
        answer: 'Even better! We especially value senior students for your experience and perspective. It\'s never too late to start participating, and you\'ll definitely have something to contribute to the community.'
      },
      {
        question: 'What do student representatives do?',
        answer: 'Student representatives participate in university governance bodies, defend student interests and shape study policies. They also, like members, often contribute to organizing events, coordinate projects and represent students in public discourse. The boundary between member and representative is often blurred in practice, as both actively participate in activities.'
      },
      {
        question: 'Can I combine VU SA activities with work or internship?',
        answer: 'Of course! Our members successfully combine representation with work or internship. Moreover, VU SA activities can help you find a better job – they provide valuable contacts and experience that employers highly value. Just remember that studies are always a priority, and we\'ll help you find balance!'
      },
      {
        question: 'How will VU SA activities help me in my career?',
        answer: 'VU SA membership will give you practical leadership experience, expand your professional contacts and develop communication skills. Many of our alumni say that VU SA experience was one of the most important at the beginning of their career!'
      },
      {
        question: 'Can I initiate my own projects?',
        answer: 'Yes! We strongly encourage member initiative and support the creation of new projects. You\'ll receive methodological assistance, funding opportunities and be able to implement the project. Many of our current projects started exactly from member ideas!'
      },
      {
        question: 'Will I have a say in decision-making?',
        answer: 'Absolutely! VU SA decisions are made democratically through voting in the Council, Parliament and annual conferences. As a member, you have the right to express opinions, propose solutions and vote. Open dialogue is encouraged – your voice will be heard! <a href="/vu-sa-struktura" class="text-vusa-red hover:text-red-700 underline transition-colors">Learn more about VU SA structure</a>.'
      },
      {
        question: 'What events and projects can I expect?',
        answer: 'Our activities are very diverse – from study quality improvement projects to organizing cultural events. You\'ll participate in discussions with university leadership, organize student events, conduct research, and create sustainability initiatives. There\'s something for everyone!'
      },
      {
        question: 'What to do if I have questions or problems?',
        answer: 'Feel free to reach out! You can contact any VU SA member you know, send an email to your unit\'s team (you\'ll find contacts on our website), come to our office in the university town or fill out the contact form on the website. There\'s always someone ready to help you – we\'re here for each other!'
      }
    ];
  }
});

// Calculate years since VU SA founding (November 17, 1989)
const organizationYears = computed(() => {
  const foundingDate = new Date(1989, 10, 17); // Month is 0-indexed, so 10 = November
  const currentDate = new Date();
  const diffTime = currentDate.getTime() - foundingDate.getTime();
  const diffYears = Math.floor(diffTime / (1000 * 60 * 60 * 24 * 365.25));
  return diffYears;
});

const numberElement: NumberStatSection = {
  json_content: [
    {
      endNumber: organizationYears.value,
      label: currentLocale === 'lt' ? 'metų veikimo' : 'years',
    },
    {
      endNumber: Math.floor(props.membershipStats.representative_bodies / 10) * 10,
      label: currentLocale === 'lt' ? 'atstovavimo organų' : 'representative bodies',
      showPlus: true,
    },
    {
      endNumber: Math.floor(props.membershipStats.student_representatives / 10) * 10,
      label: currentLocale === 'lt' ? 'studentų atstovų (-ių)' : 'student representatives',
      showPlus: true,
    },
    {
      endNumber: Math.floor(1500 / 10) * 10, // Round down to nearest 10: 1500 -> 1500
      label: currentLocale === 'lt' ? 'narių (-ių)' : 'members',
      showPlus: true,
    },
    {
      endNumber: 1,
      label: currentLocale === 'lt' ? 'vėžlė..?' : 'turtle..?',
    }
  ],
  options: {
    color: 'zinc',
    title: currentLocale === 'lt' ? 'VU SA skaičiais' : 'VU SA in numbers',
  }
};

// Computed subtitle for the last updated time
const statsSubtitle = computed(() => {
  const updatedTime = new Date(props.membershipStats.cached_at);
  const now = new Date();
  const diffMs = now.getTime() - updatedTime.getTime();
  const diffMinutes = Math.floor(diffMs / (1000 * 60));

  if (currentLocale === 'lt') {
    if (diffMinutes < 1) {
      return 'Duomenys atnaujinti prieš kelias sekundes (vėžlių skaičius taip pat!)';
    } else if (diffMinutes < 60) {
      return `Duomenys atnaujinti prieš ${diffMinutes} min. (vėžlių skaičius taip pat!)`;
    } else {
      const diffHours = Math.floor(diffMinutes / 60);
      return `Duomenys atnaujinti prieš ${diffHours} val. (vėžlių skaičius taip pat!)`;
    }
  } else if (diffMinutes < 1) {
    return 'Data updated a few seconds ago (turtle count too!)';
  } else if (diffMinutes < 60) {
    return `Data updated ${diffMinutes} min. ago (turtle count too!)`;
  } else {
    const diffHours = Math.floor(diffMinutes / 60);
    return `Data updated ${diffHours} hr. ago (turtle count too!)`;
  }
});

// Gallery images data for the photo gallery
const galleryImages = computed(() => [
  {
    src: '/images/become-a-member/VU SA 24-25-09.webp',
    alt: 'Student activities and engagement',
    heightClass: 'h-40',
    decorations: [{ type: 'line' as const, position: 'top-right' as const, size: 'sm' as const, color: 'vusa-yellow' as const, opacity: 50 }]
  },
  {
    src: '/images/become-a-member/VU SA 24-25-13.webp',
    alt: 'Student collaboration and teamwork',
    heightClass: 'h-52'
  },
  {
    src: '/images/become-a-member/VU SA 24-25-18.webp',
    alt: 'Student representation initiatives',
    heightClass: 'h-40'
  },
  {
    src: '/images/become-a-member/VU SA 24-25-23.webp',
    alt: 'Student leadership development',
    heightClass: 'h-52'
  },
  {
    src: '/images/become-a-member/VU SA 24-25-11.webp',
    alt: 'VU SA student representation activities',
    heightClass: 'h-52'
  },
  {
    src: '/images/become-a-member/VU SA 24-25-16.webp',
    alt: 'Student engagement activities',
    heightClass: 'h-40',
    decorations: [{ type: 'square' as const, position: 'bottom-left' as const, size: 'sm' as const, color: 'vusa-red' as const, opacity: 40, rotation: true }]
  },
  {
    src: '/images/become-a-member/VU SA 24-25-19.webp',
    alt: 'Student community building',
    heightClass: 'h-52'
  },
  {
    src: '/images/become-a-member/Varsuva.webp',
    alt: 'Academic ethics research',
    heightClass: 'h-40'
  },
  {
    src: '/images/become-a-member/mokymai2025-3.webp',
    alt: 'Student training workshop',
    heightClass: 'h-36'
  },
  {
    src: '/images/become-a-member/mokymai2025-4.webp',
    alt: 'Training and development session',
    heightClass: 'h-36'
  },
  {
    src: '/images/become-a-member/Nauja strategija.webp',
    alt: 'Strategic planning and development',
    heightClass: 'h-36'
  },
  {
    src: '/images/become-a-member/20250510_VUSA-88.webp',
    alt: 'VU SA community activities',
    heightClass: 'h-36'
  },
  {
    src: '/images/become-a-member/mokymai2025-5.webp',
    alt: 'Advanced training session',
    heightClass: 'h-32'
  },
  {
    src: '/images/become-a-member/mokymai2025-2.webp',
    alt: 'Skills development workshop',
    heightClass: 'h-32'
  },
  {
    src: '/images/become-a-member/mokymai2025-1.webp',
    alt: 'Sustainable practices in student activities',
    heightClass: 'h-32'
  },
  {
    src: '/images/become-a-member/mokymai2025-8.webp',
    alt: 'Professional development training',
    heightClass: 'h-32'
  }
]);
</script>
