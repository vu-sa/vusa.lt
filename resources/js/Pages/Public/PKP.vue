<template>
  <h1>
    {{ $t('Studentiškos iniciatyvos') }}
  </h1>
  <div class="typography">
    <template v-if="$page.props.app.locale === 'lt'">
      <p>
        VU SA studentiškos iniciatyvos – plati erdvė Vilniaus universiteto studentų(-čių) idėjoms, kūrybiškumui ir
        savirealizacijai. Kiekviena(s) gali įsitraukti į veiklą, kuri padės augti, lavinti įgūdžius, įgyti naujų
        kompetencijų ir susipažinti su bendraminčiais – nesvarbu, ar tai mokslinė draugija, sporto klubas, kasmetinis
        projektas, socialinė programa ar laisvalaikio būrelis.
      </p>
      <p>
        Nerandi sau tinkamos veiklos, bet turi idėją, kuri gali įkvėpti, suburti ar atnešti teigiamų pokyčių? Skatiname
         <!-- eslint-disable-next-line vue/singleline-html-element-content-newline -->
        imtis lyderystės ir, su <SmartLink class="font-bold" href="/kontaktai/sic">Studentų iniciatyvų centro</SmartLink> pagalba, <SmartLink class="font-bold" href="/nauju-stud-org-ikurimas">sukurti savo iniciatyvą</SmartLink>!
      </p>
    </template>
    <template v-else>
      <p>
        VU SA student initiatives are a broad space for the ideas, creativity and self-realization of Vilnius University
        students. Everyone can get involved in activities that will help them grow, develop skills, gain new
        competencies and meet like-minded people - whether it is a scientific society, a sports club, an annual project,
        a social program or a leisure group.
      </p>
      <p>
        Can't find the right activity for you, but have an idea that could inspire, unite or bring about positive
        <!-- eslint-disable-next-line vue/singleline-html-element-content-newline -->
        change? We encourage you to take the lead and, with the help of the <SmartLink class="font-bold" href="/kontaktai/sic"> Student Initiatives Centre</SmartLink>, <SmartLink class="font-bold" href="/procedure-for-the-establishment"> create your own initiative</SmartLink>!
      </p>
    </template>
  </div>
  <h2>
    {{ $t('Visos iniciatyvos') }}
  </h2>
  <div class="grid max-w-7xl grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <StaggeredTransitionGroup appear>
      <NewInstitutionCard v-for="(institution, index) in institutions" :key="institution.id" :institution :href="route('contacts.alias', {
        institution: institution.alias,
        subdomain:
          institution.tenant?.alias === 'vusa'
            ? 'www'
            : institution.tenant?.alias ?? 'www',
        lang: $page.props.app.locale,
      })" :data-index="index" />
    </StaggeredTransitionGroup>
  </div>
</template>

<script setup lang="ts">
import NewInstitutionCard from "@/Components/Cards/NewInstitutionCard.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";
import StaggeredTransitionGroup from "@/Components/Transitions/StaggeredTransitionGroup.vue";

defineProps<{
  institutions: Array<App.Entities.Institution>;
}>();

</script>
