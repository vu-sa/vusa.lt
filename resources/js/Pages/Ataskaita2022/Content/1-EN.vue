<template>
  <Sveikinimai>
    <h1 class="text-3xl text-center col-span-4 md:col-span-3 mb-8 -order-2">
      {{ $t("Sveikinimai") }}
    </h1>
    <div
      class="col-span-4 order-1 md:-order-1 md:col-span-3 col-start-1 prose max-w-full md:max-w-prose"
    >
      <NCarousel
        draggable
        dot-placement="top"
        :show-arrow="true"
        :space-between="20"
        :show-dots="false"
        :current-index="carouselIndex"
        @update:current-index="handleCarouselIndex"
      >
        <NCarouselItem>
          <img
            class="carousel-img"
            style="object-position: 50% 15%"
            src="/images/ataskaita2022/sveikinimai/MP portretas.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students of Vilnius University,</p>
            <p>
              The past year and the beginning of this year have again brought
              great, previously unexplored challenges. For three years now, we
              have been living in a climate of global uncertainty and are
              learning to do the best we can – we are looking for solutions,
              trying to see the possibilities of each situation and reacting
              quickly to change, and at the same time we do not forget. raise
              questions that have matured for a long time and look for
              constructive solutions.
            </p>
            <p>
              The determined students of Vilnius University are very delightful
              – when faced with difficulties, you have not been left behind, you
              are not left behind, you are not passive observers, but you
              yourself are constantly looking for ways and suggesting how your
              community can contribute to solving relevant problems.
            </p>
            <p>
              By smoothly tackling challenges, at the same time you consistently
              raise issues relevant to the student and society as a whole,
              related to ensuring the quality of studies, paying attention to
              the weight of students' voice in the study process, possibilities
              of access to higher education through the prism of the social
              dimension. I am very glad that when you talk about improving the
              quality of studies, you, as a Students’ Representation, develop
              the topic of academic honesty and act to ensure it, for example,
              you have created and implemented the "Sąžiningai" program for many
              years. You promote awareness and encourage students to
              independently manage and personalize their study plans. Your
              initiative to talk about the integration of students with
              disabilities from their own perspective is very welcome. On the
              one hand, the bureaucratic system needs to be improved, and on the
              other hand, the ability of the community to accept students with
              various needs is no less significant and your emphasis on the
              ability of the community to accept students with different needs.
              Thank you also for actively contributing to the development and
              improvement of the concept of the social dimension, analyzing how
              to involve students in scientific projects, deciding what can be
              done to ensure that doctoral studies meet the expectations of
              young scientists, and after graduation They could strengthen
              Lithuanian science and successfully join the ranks of the
              Lithuanian scientific community.
            </p>
            <p>
              Thank you for your ideas and constructive cooperation in finding
              the best solutions to make studies accessible to everyone who
              wants to follow the academic path and inspire you to never stop
              learning and look curiously at the world.
            </p>
            <p>Prime Minister of the Republic of Lithuania</p>
            <strong>Ingrida Šimonytė</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            style="object-position: 50% 20%"
            src="/images/ataskaita2022/sveikinimai/JurgitaSiugzdiniene.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students,</p>

            <p>
              I am glad that the Vilnius University Students’ Representation is
              characterized by unfailing activity and citizenship, the ability
              to act and unite. At this turbulent time, you have put in a huge
              deposit to provide as much clarity and encouragement as possible
              to your community. I believe that you will be happy to welcome
              those who are experiencing the most at the moment – let's do our
              best to make Ukrainians in a brotherly country feel welcome, feel
              safe.
            </p>
            <p>
              I wish you to continue your meaningful work and fully improve, to
              develop not only the ability to adapt to new challenges, but also
              the stubbornness to find effective ways to solve them. Despite a
              sense of uncertainty.
            </p>
            <p>
              I warmly congratulate you on the significant achievements of the
              past year and hope that you will not stop showing youthful courage
              and solidarity with today's freedom fighters.
            </p>
            <p>Minister for Education, Science and Sport</p>
            <strong>Jurgita Šiugždinienė</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            style="object-position: 50% 20%"
            src="/images/ataskaita2022/sveikinimai/Žukauskas.XIII-020-S20210121-O Posaškovos-0234.JPG"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students,</p>

            <p>
              congratulations on another year of successful work! Your activity
              in finding the best solutions turns into energy that encourages
              you to move forward, look deeper, look for more perfect formulas
              for change. The past few years have been marked by extraordinary
              challenges, but I admire your focus, bold and youthful initiatives
              that inspire and leave you indifferent. Continue the work you have
              started, boldly and responsibly take on new ones! Protect and love
              dear Lithuania, be proud of the past of your homeland and build a
              prosperous future by following the path of educational and
              scientific progress! I wish you the determination and energy to
              achieve leadership and professionalism in your fields and the
              fulfillment of noble aspirations!
            </p>

            <strong>Prof. Artūras Žukauskas</strong>
            <p>Chairman of the Committee on Education and Science</p>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            src="/images/ataskaita2022/sveikinimai/rektorius.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students,</p>
            <p>
              The year has been challenging and full of global challenges that
              affect all of our lives. I believe that they have further
              strengthened the role of the University and revealed its essence.
              It is at the University that ideas are born and developed that
              create, support the state and society. University frees you to
              think independently and act socially. The university provides the
              tools and conditions to make sense of itself. And even becomes a
              refuge from adversity and misfortune.
            </p>
            <p>
              I would like to sincerely welcome the fact that the students of
              Vilnius University, both in the face of the pandemic and the war
              in Ukraine, demonstrated not only solidarity, but also became real
              leaders of society. Thanks to you, the University turns the
              declared values into real works. Students became volunteers,
              supporters, mentors, protesters, support voters, dealers, and at
              the same time remained active and significant members of the
              academic community.
            </p>
            <p>
              I congratulate the Students' Representative Office of Vilnius
              University, which expresses itself not only as an active part of
              the community, but also as a source of new ideas, academic and
              social activity and respectful dialogue. I believe that the annual
              self-evaluation of the works will be an opportunity not only to
              enjoy what has been done, but also to appreciate what else you can
              do for the University, society, the state, which means for your
              own good.
            </p>
            <p>
              It is important that the voice of students is heard even more
              widely not only at the University, but also in society. It is
              banal to repeat certain truths, but we keep repeating them because
              we are convinced of their legitimacy. I repeat, you are the
              future. It is our responsibility to help you to rise up, to find
              the motivation and courage to fulfil our ambitions.
            </p>
            <p>
              I wish to continue to successfully represent the University and
              its students, to develop ideas and to prepare proposals that
              increase the common good of all of us.
            </p>
            <p>Rector of Vilnius university</p>
            <strong>Prof. Rimvydas Petrauskas</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            src="/images/ataskaita2022/sveikinimai/senato pirmininke.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students of Vilnius University,</p>
            <p>
              The first European universities established in Italy, Bologna and
              Padua are approaching the millennium, and some of the new ones are
              just counting down their first decades, the history of Vilnius
              University gives us an honorable place at the very center of
              European development. We will soon be celebrating the 445th
              anniversary of our Alma Mater, so for the fifth century Vilnius
              University is creating the history of Lithuania by ageing once and
              for a generation.
            </p>
            <p>
              For our university, the goddess of history, Klio, has led radical
              changes and experiences that we need to constantly reflect on and
              appreciate in order to creatively develop her intellectual
              identity. The 2021-2022 school year was marked by the pandemic,
              the beginning of hostilities in Ukraine, which led to vigilance,
              concentration and determination. As the ancient Greek thinker
              Thucydides observed, history is the truest form of practical
              philosophy.
            </p>
            <p>
              The Senate of Vilnius University, formed in 2018, is leaving its
              term of office in the spring semester of 2022. Thanking the
              students operating in the Senate and other academic and
              organizational fields of Vilnius Alma Mater, I would like to
              emphasize that their responsibility, sensitivity and attentiveness
              in solving issues relevant to the whole community are an important
              part of the self-government of our University, which must be
              developed and nurtured. Therefore, I wish every student of Vilnius
              University to remain a member of a spirit-raising community for
              the rest of their lives, to realize as many of their goals as
              possible and to cherish with hope and love the face of a
              University dear to all of us, which was created even in the most
              difficult times before us and will remain after us.
            </p>
            <p>Chairwoman of the VU Senate</p>
            <strong>Prof. Dainora Pociūtė-Abukevičienė</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            style="object-position: 50% 20%"
            src="/images/ataskaita2022/sveikinimai/Radzeviciene.5M5A1668r_3.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear colleagues,</p>
            <p>
              Another year of tension and challenge. I dare say that the year of
              science and learning was not only for our Alma mater students’
              community, but also for society as a whole. Together we have
              transformed the forms of work and learning, clarified our values
              and social position, and together we created a modern and
              inclusive Vilnius University. It is good to see that the students
              were active and mature participants and partners of all these
              transformations.
            </p>
            <p>
              On behalf of the entire VU Council, I thank you all for your
              partnership, initiative and volunteering. With all your youthful
              energy, continue to build your university – an international
              community based on mutual respect, where there is a dialogue
              between different generations, where science and studies combine
              with meaningful leisure and social responsibility and
              volunteering.
            </p>

            <strong>Eglė Radzevičienė</strong>
            <p>Head of Vilnius University Council</p>
          </div>
        </NCarouselItem>

        <NCarouselItem>
          <img
            class="carousel-img-2"
            src="/images/ataskaita2022/sveikinimai/Kriviene.biblioteka.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students,</p>

            <p>
              You, the largest and most dynamic part of the Vilnius University
              community, are the main visitors to our library. Here you study,
              work, make acquaintances, communicate. I am very pleased with your
              interest in what the library does and what it lives for. We, the
              library staff, are sincerely grateful for the openly based
              cooperation and constructive dialogue between our organizations.
            </p>

            <p>
              The active involvement of VU SR representatives in the preparation
              of the 2022-2025 Library Strategic Action Plan has helped us to
              generate the areas on which we will focus on improving the
              informational study environment at the most recent time. This is a
              response to the individual information needs of consumers,
              strengthening the activities of the library as an information /
              academic literacy center, as well as a space that unites the
              Universite community, etc. The thoughts of the President of VU SR,
              expressed in the discussion on the issue of values, made a
              significant contribution to finding a common solution on which way
              we could synchronize the values of the organization (in this case,
              the University) and its structural unit (in this case, the
              Library).
            </p>

            <p>
              The Library is grateful to you for encouraging you to improve, for
              not only for not only evaluating its work, but also for offering
              what and how to do so that information services and the
              information environment meet the expectations of service users as
              much as possible. During the reporting period of VU SR, the
              Library, using the consumer opinion research tool LibQual, which
              is recognized and widely used in the world of information
              institutions, conducted an extended consumer survey for the first
              time. When researching the opinion of users, I was surprised and
              was also pleased with the huge number of comments from those who
              filled out the questionnaires, confirming the fact that the
              library cares about you. We sincerely thank you for the help in
              the publication of the LibQual poll, the activism in expressing
              our opinion and the feedback given.
            </p>

            <p>
              Young people are always in the avant-garde of the genealogy of
              innovations and their application, therefore, the involvement of
              the representative of VU SR in the working group on the
              preparation of the Open Science Policy Guidelines was both
              expected and very successful: significant contributions were made
              to the preparation of this document and the emergence of the topic
              of open teaching / resources in it.
            </p>

            <p>
              Very soon you will join Lithuanian or foreign businesses, state
              institutions or academic activities. I have no doubt that the
              managerial, civic and social responsibility experience gained by
              VU SR, as well as the established contacts will help you along the
              path of professional career and personal life. I wish I never lost
              touch with my Alma Mater. You are and will be welcome at the
              Library!
            </p>

            <strong>Irena Krivienė</strong>
            <p>General Director of VU Library</p>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img-2"
            style="object-position: 50% 20%"
            src="/images/ataskaita2022/sveikinimai/dr. Loreta Tauginienė.MART1397B.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students,</p>
            <p>
              You persistently, actively and consistently promote adherence to
              the principles of academic ethics and thus contribute to the
              quality of studies. You bring innovations that comprehensively
              change the culture of academic ethics. Academic ethics is
              especially fostered through direct education and talking to the
              academic community. Invitation for students to take a more active
              interest in the specific course "Academic Ethics", preparation of
              methodological material, openness to consultations and other
              initiatives are some examples when your involvement is
              significant. The most important thing is to move forward
              creatively and boldly – this is how changes in the Lithuanian
              study and science system become possible.
            </p>
            <p>
              I appreciate your contribution and invite you to work together
              towards international standards of academic ethics.
            </p>
            <p>Unitedly For the Future of Students!</p>
            <p>Ombudswoman for academic ethics and procedures</p>
            <strong>dr. Loreta Tauginienė</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img-2"
            style="object-position: 50% 10%"
            src="/images/ataskaita2022/sveikinimai/Simasius.jpeg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students,</p>
            <p>
              The first steps of your independent life are once again tested by
              complex challenges. The restrictions of the coronavirus pandemic
              have been replaced by a confrontation with the shocking Russian
              aggression.
            </p>
            <p>
              And here society especially needs your positivity, courage and,
              most importantly, real involvement. Share information, explain to
              the doubters, create the state and the future in which you want to
              live.
            </p>
            <p>
              And Vilnius will always be yours – established after studying or
              deciding to look abroad after studying. We look forward to coming
              back.
            </p>
            <p>Mayor of Vilnius</p>
            <strong>Remigijus Šimašius</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img-2"
            src="/images/ataskaita2022/sveikinimai/arminas_2021_profilis.jpg"
          />
          <div class="bg-white p-6 text-sm">
            <p>VU SR,</p>

            <p>
              congratulations on a year full of results! VU SR is visible and
              heard at the national level - funding for higher education,
              improving the situation of students, issues of the social
              dimension - no decisions are made without you. You are invited to
              read reports as experts. You are actively involved in the
              activities of the Arqus University Alliance. You are the main
              force pushing the University to move to a more Western model of
              study organization, allowing students to choose at least 60
              credits on their own.
            </p>

            <p>
              It is great to see that you've created good conditions for
              students to participate, and the range of PCP is expanding. It is
              welcome that you have started to implement a culture of reflection
              in the organization (although it will take time to become a
              reality), you have integrated a new unit from Šiauliai. And you
              have managed to achieve all this in the context of a pandemic that
              has not yet ended and the war that began a few months ago. What's
              more, you've been able to react quickly to a situation and offer
              help to those who need it most.
            </p>

            <p>
              Last year was hard. This year has been difficult. The coming years
              will not be easier either. I have five tips that are necessary for
              VU SR to continue to grow as an organization and solve even more
              student problems:
            </p>
            <p>
              Openness, responsibility, quality of performance, unity and
              improvement. Never forget!
            </p>

            <p>Unitedly,</p>
            <strong>Arminas Varanauskas</strong>
          </div>
        </NCarouselItem>
        <NCarouselItem>
          <img
            class="carousel-img"
            src="/images/ataskaita2022/sveikinimai/Romas-LMT-9.jpeg"
          />
          <div class="bg-white p-6 text-sm">
            <p>Dear students,</p>

            <p>
              Thank you for your creativity, public-spirited actions, and
              determination to complete a broad-based university education.
              These are also your first steps in building the new knowledge that
              will contribute to our competitiveness as a country. That’s also
              the essence of having high quality degree courses.
            </p>

            <p>
              This year we saw an increase in funding of research and
              experimental (social, cultural) development. A portion of the
              funding is allocated to the summer research in addition to the
              term time projects funded by the Research Council of Lithuania. I
              hope it will encourage you to further engage in research and work
              on your expertise. I am particularly glad to see that Vilnius
              University students are some of the most active in these research
              projects.
            </p>

            <p>
              Your never-ending determination and energy brings me joy. I hope
              you will treasure these qualities for the bright future of our
              country. Best of luck in your future endeavors, keep your energy
              levels high.
            </p>

            <em>Gaudeamus igitur, iuvenes dum sumus! </em>

            <p>Chairman of the Research Council of Lithuania</p>
            <strong>Prof. Romas Baronas</strong>
          </div>
        </NCarouselItem>
      </NCarousel>
    </div>
    <div
      class="flex flex-wrap justify-center md:justify-start md:flex-col mb-8 gap-2 col-span-4 md:col-span-1 items-start"
    >
      <NButton text="" @click="carouselIndex = 0">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 0 }"
          src="/images/ataskaita2022/sveikinimai/MP portretas.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 1">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 1 }"
          src="/images/ataskaita2022/sveikinimai/JurgitaSiugzdiniene.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 2">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 2 }"
          src="/images/ataskaita2022/sveikinimai/Žukauskas.XIII-020-S20210121-O Posaškovos-0234.JPG"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 3">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 3 }"
          src="/images/ataskaita2022/sveikinimai/rektorius.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 4">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 4 }"
          src="/images/ataskaita2022/sveikinimai/senato pirmininke.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 5">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 5 }"
          src="/images/ataskaita2022/sveikinimai/Radzeviciene.5M5A1668r_3.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 6">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 6 }"
          src="/images/ataskaita2022/sveikinimai/Kriviene.biblioteka.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 7">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          style="object-position: 50% 10%"
          :class="{ grayscale: carouselIndex !== 7 }"
          src="/images/ataskaita2022/sveikinimai/dr. Loreta Tauginienė.MART1397B.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 8">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          style="object-position: 50% 10%"
          :class="{ grayscale: carouselIndex !== 8 }"
          src="/images/ataskaita2022/sveikinimai/Simasius.jpeg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 9">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 9 }"
          src="/images/ataskaita2022/sveikinimai/arminas_2021_profilis.jpg"
        />
      </NButton>
      <NButton text="" @click="carouselIndex = 10">
        <img
          class="w-20 h-20 object-cover rounded-lg hover:grayscale-0 duration-200"
          :class="{ grayscale: carouselIndex !== 10 }"
          src="/images/ataskaita2022/sveikinimai/Romas-LMT-9.jpeg"
        />
      </NButton>
    </div>
    <!-- <div class="col-span-4 mb-12 order-3">
      <Link
        class="text-lg duration-200 hover:text-red-600 font-bold"
        :href="
          route('main.ataskaita2022', { lang: $page.props.app.locale, permalink: 'vu-sa' })
        "
        >Apie VU SA >></Link
      >
    </div> -->
  </Sveikinimai>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NCarousel, NCarouselItem } from "naive-ui";
import { ref } from "vue";
import Sveikinimai from "../1-Sveikinimai.vue";

const carouselIndex = ref(0);

const handleCarouselIndex = (index) => {
  carouselIndex.value = index;
};
</script>

<style scoped>
.carousel-img {
  width: 100%;
  height: 280px;
  max-width: 65ch;
  object-fit: cover;
  border-radius: 1em 1em 0 0;
  margin-top: 0;
  margin-bottom: 0;
}
.carousel-img-2 {
  width: 100%;
  height: 365px;
  object-fit: cover;
  border-radius: 1em 1em 0 0;
  margin-top: 0;
  margin-bottom: 0;
}
</style>
