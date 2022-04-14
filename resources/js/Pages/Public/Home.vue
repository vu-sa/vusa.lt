<template>
  <PublicLayout class="space-x-4">
    <HomeCard>
      <template #mini>Abc</template>
      <template #below-card
        ><NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
        ataskaitinė-rinkiminė konferencija</template
      >
      <template #image>
        <ImageForCard src="/images/placeholders/foto5.jpg" />
      </template>
      Priimta pozicija dėl užsienio studentų (-čių) teisinės padėties
    </HomeCard>
    <HomeCard>
      <template #mini>Vienas du trys</template>
      <template #below-card
        ><NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
        parlamentas</template
      >
      <template #image>
        <ImageForCard src="/images/placeholders/foto4.jpg" />
      </template>
      Pavadinimas
    </HomeCard>
    <HomeCard>
      <template #mini>Hahahaha</template>
      <template #below-card
        ><NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
        konferencija</template
      >
      <template #image>
        <ImageForCard src="/images/placeholders/foto2.jpg" />
      </template>
      Turi klausimų – susisiek su VU SA konsultantu (-e)
    </HomeCard>
    <HomeCard>
      <template #mini>Hahahaha</template>
      <template #below-card
        ><NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
        konferencija</template
      >
      <template #image>
        <ImageForCard src="/images/placeholders/foto2.jpg" />
      </template>
      Turi klausimų – susisiek su VU SA konsultantu (-e)
    </HomeCard>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import HomeCard from "@/Components/Public/HomeCard.vue";
import ImageForCard from "@/Components/Public/ImageForCard.vue";
import { NIcon } from "naive-ui";
import { CalendarLtr20Regular } from "@vicons/fluent";
</script>
