<template>
  <Head title="Individualios studijos" />
  <FadeTransition appear>
    <div
      class="mx-auto grid max-w-7xl grid-cols-1 gap-4 px-4 pt-2 last:pb-2 lg:grid-cols-5 lg:px-16"
    >
      <div class="prose prose-sm col-span-3 px-12 dark:prose-invert">
        <template v-if="$page.props.app.locale === 'lt'">
          <h1 class="font-bold">Individualios studijos</h1>
          <p>
            Vienas svarbiausių dalykų, kurį universitetas gali pasiūlyti
            studentams (-ėms) – laisvė dėliojantis savo studijas pagal asmeninį
            interesų lauką. Nuo 2023 m. (ir vėliau) Vilniaus universitete
            studijas pradėję bakalauro ar vientisųjų studijų programų studentai
            (-ės) turi galimybę įgyti norimų kompetencijų ir žinių ne tik savo
            studijuojamose studijų programose, kadangi kiekvienam studentui bus
            prieinama individualiųjų studijų galimybė. Iš visos studento
            pasirinktos studijų programos trukmės (priklausomai nuo
            studijuojamos studijų programos) net ketvirtadalis bus skirtas
            individualiosioms studijoms ir asmeniniam studento pasirinkimui.
          </p>
          <p>Individualiąsias studijas sudaro:</p>
          <ul>
            <li>studijuojamos krypties (programos) dalykai (moduliai);</li>
            <li>kitos krypties dalykai (moduliai);</li>
            <li>tarpkryptiniai studijų dalykai (moduliai);</li>
            <li>bendrieji universitetiniai studijų dalykai (moduliai);</li>
            <li>gretutinės krypties studijos;</li>
            <li>
              kitos (ne pirmosios) užsienio kalbos mokymosi dalykai (moduliai);
            </li>
            <li>
              dalinės studijos ar atskiri studijų dalykai (moduliai) pagal
              suderintą turinį, studijuojant juos kitose Lietuvos ir (ar)
              užsienio aukštosiose mokyklose.
            </li>
          </ul>
          <p>
            Ši galimybė suteikia studentams laisvę susidėlioti savo studijas
            pagal poreikius ir įgauti kuo platesnę akademinę patirtį
            universitete. Tai atsakas sparčiai besikeičiančiam dabarties
            pasauliui ir darbo rinkai, kai niekas negali žinoti, kokių
            kompetencijų reikės ateityje. Ši nauja galimybė mūsų Universitete
            atliepia tiek plataus tarpdiscipliniškumo, tiek gilaus specifinės
            srities išmanymo poreikį.
          </p>
          <p>
            Bendruomeniškumas – individualiosios studijos leis studentams
            pasijausti ne tik specifinio VU fakulteto, bet ir visos VU
            bendruomenės dalimi. Aktyvus bendravimas tarp skirtingų padalinių
            studentų ir pažintys su kitų fakultetų dėstytojais leis studentui
            pažinti visą universiteto bendruomenę ir kultūrą, matyti VU kaip
            dinamišką ir atvirą.
          </p>
          <p>
            Pagalba – Kilus iššūkiams renkantis savo individualiųjų studijų
            dalykus, studentams bus suteikiama pagalba pasirinkimo procese.
            Universitetas kartu su studentų atstovybe organizuos įvairius
            seminarus, vidinę studijų mugę, kurie padės pasirinkimo procese.
            Lygiagrečiai yra kuriamas ir mentorystės tinklas, kurį sudarys
            skirtingų fakultetų dėstytojai, su kuriais bus galima
            pasikonsultuoti dėl specifinių kompetencijų įgijimo (daugiau
            informacijos greitu metu rasi čia -
            <a
              href="https://www.idialogue.lt/lt/mentors/free/1?vu_association=1"
              target="_blank"
              >https://www.idialogue.lt/lt/mentors/free/1?vu_association=1</a
            >). Taip pat universitete yra prieinamos individualios karjeros
            konsultacijos (daugiau informacijos -
            <a
              href="https://www.vu.lt/studijos/studentams/karjeros-paslaugos/uzsiregistruok-konsultacijai"
              target="_blank"
              >https://www.vu.lt/studijos/studentams/karjeros-paslaugos/uzsiregistruok-konsultacijai</a
            >
            ). O kilus klausimų dėl techninių detalių galima kreiptis į savo
            padalinio akademinius konsultantus (kontaktus gali rasti čia -
            <a
              href="https://www.vu.lt/studijos/studentams/akademinis-konsultavimas#kontaktai"
              target="_blank"
              >https://www.vu.lt/studijos/studentams/akademinis-konsultavimas#kontaktai</a
            >) arba Studentų paslaugų ir karjeros skyrių (el.p.
            konsultavimas@vu.lt). Žinoma, visada gali kreiptis į savo padalinio
            studentų atstovybės akademinio proceso reikalų koordnatorių (-ę),
            kurie padės arba nukreips ten, kur reikia!
          </p>
          <p>
            Paruošėme Tau dažniausiai užduodamų klausimų ir atsakymų paketą apie
            individualiąsias studij, tad imk, skaityk ir dėliokis savo studijas
            taip, kaip nori!
          </p>
        </template>
        <template v-if="$page.props.app.locale === 'lt'">
          <p>DUK:</p>
          <NCollapse>
            <NCollapseItem
              title="Kokios apimties yra individualiosios studijos?"
              >Priklausomai nuo studijų programos ( gali būti 60, 45,30 ar kitas
              kreditų kiekis).Apimtį kreditais gali pamatyti savo studijų
              programos tinklelyje.</NCollapseItem
            >
            <NCollapseItem title="Kokia yra individualiųjų studijų nauda?"
              >Individualiosios studijos suteiks studentui galimybę papildyti
              savo studijuojamą studijų programą norimais studijuoti dalykais,
              gretutinėmis studijomis, užsienio kalbomis ar užsienio patirtimis.
              Iki šiol, kol studentai neturėjo tokios galimybės, studentams
              tekdavo susidaryti individualų studijų planą ar rinktis gretutines
              studijas, tačiau tam reikėjo teikti prašymus, kurie ne visada buvo
              patvirtinami ir tokie studentai negalėjo pasinaudoti galimybe
              studijuoti norimas studijas. Taip pat tai leis susiformuoti norimą
              savo kaip absolvento profilį, pagal tai, kur įsivaizduojate save
              ateityje studijuojant toliau arba veikiant darbo rinkoje.
            </NCollapseItem>
            <NCollapseItem
              title="Ar visi bakalauro ir vientisųjų studijų programų studentai turės rinktis individualiąsias studijas?"
              >Taip, nes šios studijos bus numatytos studijų programoje, todėl
              kiekvienas studentas turės rinktis. Svarbu suprasti, kad
              studentai, kurie nenorėtų nieko ypatingai keisti savo studijų
              programoje, vis dar galės pasirinkti ir savo krypties dalykus.
            </NCollapseItem>
            <NCollapseItem
              title="Ar individualiosios studijos bus įgyvendinamos II pakopos studijose?"
            >
              Ne, šiuo metu Universitete nenumatytos individualiosios studijos
              nei II, nei III pakopos studijų programose.
            </NCollapseItem>
            <NCollapseItem
              title="Kaip bus sukuriama individualiųjų studijų pasiūla studentams?"
              >Iš kiekvienos pirmos pakopos ir vientisųjų studijų programų bent
              30 kreditų apimties dalykų turės būti pasiūlyta studentams rinktis
              kaip individualiąsias studijas.
            </NCollapseItem>
            <NCollapseItem
              title="Kuo individualiosios studijos skiriasi nuo individualaus studijų plano?"
            >
              Individualiosios studijos bus numatytos studijų programoje.
              Individualus studijų planas yra studento prašymu nustatomas (ir
              esant poreikiui keičiamas) studijuojamų dalykų ir jų atsiskaitymo
              individualus išdėstymas semestro (-ų) metu.
            </NCollapseItem>
            <NCollapseItem
              title="Ar studentai galės susidaryti individualų studijų planą, kai studijų programoje bus įtrauktos individualiosios studijos?"
              >Studentai galės susidaryti individualų studijų planą, nes
              studentas galės teikti prašymą keisti privalomuosius studijų
              programos dalykus ar jų išdėstymą semestruose.</NCollapseItem
            >
            <NCollapseItem
              title="Kur vyks individualiųjų studijų dalykų registracija?"
              >Registracija vyks Vilniaus universiteto informacinėje sistemoje
              (VU IS).</NCollapseItem
            >
            <NCollapseItem
              title="Ar šiuo metu studijuojantys studentai galės rinktis studijuoti individualiųjų studijų dalykus?"
              >Ne, individualiosios studijos studijų programose bus
              įgyvendinamos nuo 2023 m. m. rudens semestro, todėl tik 2023 m.
              įstoję studentai galės jas rinktis.
            </NCollapseItem>
            <NCollapseItem
              title="Ar individualiųjų studijų dalykai galės vykti nuotoliniu būdu?"
              >Taip, jeigu studijų dalyke bus numatyta, jog jis vyks nuotoliniu
              būdu.</NCollapseItem
            >
            <NCollapseItem
              title="Ar pasirenkamiems dalykams bus išankstiniai reikalavimai (prerekvizitai)?"
              >Taip, studijų dalykuose gali būti numatyti išankstiniai
              reikalavimai, tačiau jie bus rekomendacinio pobūdžio. Studentas
              galės rinktis studijų dalykus, kurių išankstinių reikalavimų
              neatitinka, tokiu atveju studentas prisiima atsakomybę gebėti
              studijuoti pasirinktą dalyką.
            </NCollapseItem>
            <NCollapseItem
              title="Ar individualiųjų studijų dalykai bus įtraukiami skaičiuojant studentų pažangumą ir sudarant pažangumo eilę?"
              >Taip, individualiosios studijos yra studijų programos sandaros
              dalis, visų studento studijuojamų dalykų vidurkiai yra įtraukiami
              į bendrąjį vidurkį sudarant pažangumo eilę.
            </NCollapseItem>
            <NCollapseItem
              title="Ar toje pačioje programoje studijuojantys, bet skirtingus individualiųjų studijų dalykus pasirinkę studentai gaus tokius pat diplomus?"
              >Taip, studentams diplomai išduodami pagal studijuojamą pagrindinę
              studijų kryptį, todėl diplomai nesikeis. Skirsis tik studento
              diplomo priedėlis, kuriame yra įvardinti visi studijuoti studijų
              dalykai.</NCollapseItem
            >
            <NCollapseItem
              title="Ar studentai galės atlaisvintus kreditus užpildyti kitais savo fakulteto dalykais?"
              >Taip, studentai galės rinktis tiek savo, tiek kitų fakultetų
              numatytus individualiųjų studijų dalykus.
            </NCollapseItem>
            <NCollapseItem
              title="Kaip bus derinami studijų tvarkaraščiai, kad studentai galėtų pasirinkti individualiųjų studijų dalykus?"
              >Rudens semestro studijų tvarkaraščiai skelbiami iki einamųjų metų
              gegužės 1 d., o pavasario semestro – iki gruodžio 1 d. Šiuose
              tvarkaraščiuose bus numatyti pagrindinės studijuojamos programos
              privalomieji ir pasirenkamieji darbai bei nurodyta, kiek kreditų
              skirta individualiųjų studijų dalykams. Registracija į
              individualiuosius studijų dalykus prasidės po paskelbtų studijų
              tvarkaraščių. Kiekvienas individualiųjų studijų dalykas turės
              numatytą laiką, todėl studentas galės įsivertinti, ar norimų
              studijų dalykų laikas nesidubliuoja su kitų studijų programos
              tvarkaraštyje numatytų dalykų laiku.
            </NCollapseItem>
            <NCollapseItem
              title="Universiteto padaliniai Vilniuje yra įvairiose miesto dalyse. Kaip studentams reikės derinti tvarkaraštį pagal tai?"
              >Rinkdamiesi individualiųjų studijų dalykus studentai turės
              įsivertinti kelionės tarp padalinių laiką, jei dalykas
              organizuojamas kontaktiniu būdu.</NCollapseItem
            >
            <NCollapseItem
              title="Jeigu studentui teks kartoti kursą, ar yra rizika, kad tvarkaraščiai taps nesuderinami?"
              >Tokia rizika išlieka, nes kurso kartojimo atveju bus pasikeitęs
              tiek pagrindinės studijuojamos programos tvarkaraštis, tiek ir
              individualiųjų studijų dalykų laikai gali būti kitokie. Tokie
              atvejai bus sprendžiami individualiai ieškant galimybės suderinti
              tvarkaraščius ir užtikrinti, kad studentui būtų sudarytos sąlygos
              kartoti kursą arba pasirinkti studijuoti kitą studijų
              dalyką.</NCollapseItem
            >
            <NCollapseItem
              title="Ar laisvai pasirenkami dalykai turės kvotas, t.y. ar bus numatytas kiekis, kiek studentų gali būti priimta ir yra tikimybė, kad studentams neliks vietų į norimus pasirinkti studijų dalykus?"
              >Taip, kiekvienam studijų dalykui yra numatomos kvotos, todėl jos
              bus ir individualiuosiuose studijų dalykuose. Kaip ir visų
              registracijų atvejų, ne visada studentai gali suspėti
              užsiregistruoti į norimą dalyką, tačiau studentas galės bandyti į
              tą dalyką užsiregistruoti kitame semestre, kuriame yra numatyti
              laisvi kreditai individualiosioms studijoms.</NCollapseItem
            >
            <NCollapseItem
              title="Ar bus siūloma rinktis tik teorinius dalykus (paskaitų formato), ar bus ir seminarų / pratybų / laboratorinių užsiėmimų?"
              >Pasiūlą sudarys kiekvienos pirmos pakopos ir vientisųjų studijų
              programų numatyti dalykai, todėl tai priklausys nuo kiekvieno šių
              programų studijų programos komiteto. Įprastai dalykus sudaro
              paskaitų derinys su praktiniais užsiėmimais, tokiais kaip
              seminarai, pratybos ar laboratoriniai darbai.</NCollapseItem
            >
            <NCollapseItem
              title="Kas nutiks, jeigu pasirinkus norimą dalyką nesusidarys grupė ir tas dalykas nebus vykdomas?"
              >Tokiais atvejais bus leidžiama registruotis į kitą studijų
              dalyką, tokia registracija vyks per pirmąsias dvi semestro
              savaites.</NCollapseItem
            >
            <NCollapseItem
              title="Kas nutiks, jeigu studentas įgis pasirinkto individualiųjų studijų dalyko skolą?"
              >Studentas galės rinktis kartoti to paties dalyko kursą ir jį
              perlaikyti arba rinktis kitą naują individualiųjų studijų dalyką
              skolai likviduoti.</NCollapseItem
            >
          </NCollapse>
        </template>
        <template v-else>
          <h1>Individual studies</h1>
          <p>FAQ:</p>
          <NCollapse>
            <NCollapseItem title="What is the scope of individual studies?"
              >Depending on the study program, 60, 45 or 30
              credits.</NCollapseItem
            >
            <NCollapseItem title="What are the benefits of individual studies?"
              >Individual studies will provide the student the opportunity to
              supplement his study program with subjects he wants to study, side
              studies, foreign languages or foreign experiences. Until now,
              before students had such an opportunity, students had to create an
              individual study plan or choose side studies, but for this it was
              necessary to submit applications that were not always approved and
              such students could not take advantage of the opportunity to study
              the study programmes you really want.. It will also allow you to
              form the desired profile of yourself as a graduate, based on where
              you imagine yourself in the future in further studies or in the
              labor market.
            </NCollapseItem>
            <NCollapseItem
              title="Will all students of bachelor's and integrated study programs have to choose individual studies?"
              >Yes, because these studies will be included in the study program,
              so each student will have to choose. It is important to understand
              that students who do not want to change anything in their study
              program will still be able to choose the subjects of their major
              study field.
            </NCollapseItem>
            <NCollapseItem
              title="Will individual studies be implemented in the II cycle studies?"
            >
              No, currently the University does not provide individual studies
              in either the II or III cycle study programs.
            </NCollapseItem>
            <NCollapseItem
              title="How will the offer of individual studies for students be created?"
              >From each first-cycle and integrated study program, at least 30
              credits of subjects will have to be offered to students as
              individual studies.
            </NCollapseItem>
            <NCollapseItem
              title="How does individual study differ from an individual study plan?"
            >
              Individual studies will be included in the study program. An
              individual study plan is an individual arrangement of the subjects
              and exams during the semester(s) determined (and modified if
              necessary) at the student's request.
            </NCollapseItem>
            <NCollapseItem
              title="Will students be able to create an individual study plan when individual studies are included in the study program?"
              >Students will be able to create an individual study plan, as the
              student will be able to submit a request to change the mandatory
              subjects of the study program or their arrangement in
              semesters.</NCollapseItem
            >
            <NCollapseItem
              title="How will the registration of individual study subjects be conducted?"
              >Registration will take place in the information system of Vilnius
              University (VU IS).</NCollapseItem
            >
            <NCollapseItem
              title="Will currently studying students be able to choose to study individual study subjects?"
              >No, individual studies in study programs will be implemented from
              2023. m. autumn semester, therefore only in 2023 enrolled students
              will be able to choose them.
            </NCollapseItem>
            <NCollapseItem
              title="Will distance learning be available for individual study courses?"
              >Yes, if the course involves distance learning.</NCollapseItem
            >
            <NCollapseItem
              title="Will there be prerequisites for the optional subjects?"
              >Yes, study subjects may have prerequisites, but they will not be
              mandatory. The student will be able to choose study subjects that
              do not meet the prerequisites, in which case the student
              acknowledges the responsibility of being able to study the chosen
              subject.
            </NCollapseItem>
            <NCollapseItem
              title="Will individual study subjects be included in calculating student's progress and ranking?"
              >Yes, individual studies are part of the study program structure,
              the averages of all the subjects studied by the student are
              included in the overall student's progress average.
            </NCollapseItem>
            <NCollapseItem
              title="Will students studying in the same program but choosing different individual study subjects receive the same diplomas?"
              >Yes, diplomas are issued to students according to the main field
              of study, so the diplomas will not change. Only the appendix of
              the student's diploma, which lists all studied subjects, will
              differ.
            </NCollapseItem>
            <NCollapseItem
              title="Will students be able to fill the freed-up credits with other subjects in their department?"
              >Yes, students will be able to choose both their own and other
              departments' individual study subjects.
            </NCollapseItem>
            <NCollapseItem
              title="How will study schedules be coordinated so that students can choose individual study subjects?"
              >Study schedules for the autumn semester are published by May 1 of
              the current year, and for the spring semester by December 1. These
              timetables will include mandatory and optional assignments of the
              main program of study and indicate how many credits are allocated
              to individual study subjects. Registration for individual study
              subjects will begin after the published study schedules. Each
              subject of individual studies will have a scheduled time, so the
              student will be able to assess whether the time of the desired
              study subjects does not overlap with the time of other subjects
              provided in the schedule of the study program.
            </NCollapseItem>
            <NCollapseItem
              title="University departments in Vilnius are located in various parts of the city. How will students need to coordinate their schedule around this?"
              >When choosing individual study subjects, students will have to
              consider the travel time between departments, if the subject is
              taking place in person.</NCollapseItem
            >
            <NCollapseItem
              title="If the student has to repeat the course, is there a risk that the schedules will become incompatible?"
              >Such a risk remains, because in the case of repeating the course,
              both the schedule of the main studied program will change, and the
              times of individual study subjects may be different. Such cases
              will be dealt with on an individual basis by finding an
              opportunity to reconcile schedules and ensure that the student is
              given the opportunity to repeat the course or choose to study
              another subject of study.</NCollapseItem
            >
            <NCollapseItem
              title="Will optional subjects have quotas, i.e. Will there be a limit on how many students can be accepted and is there a chance that there will be no places left for students in the subjects they want to choose?"
              >Yes, there are estimated quotas for each study subject, so there
              will be quotas for individual study subjects as well. As with all
              registrations, students may not always be able to register for the
              desired subject, but the student will be able to try to register
              for that subject in the next semester where free credits for
              individual studies are available.</NCollapseItem
            >
            <NCollapseItem
              title="Will only theoretical subjects (lecture format) be offered, or will there also be seminars/exercises/laboratory sessions?"
              >The offer will consist of the subjects provided for each
              first-cycle and integrated study programs, so it will depend on
              the study programme committee of each of these programs. The
              subjects usually consist of a combination of lectures with
              practical activities such as seminars, exercises or laboratory
              work.</NCollapseItem
            >
            <NCollapseItem
              title="What happens if, after selecting the desired subject, a group is not formed and that course is not conducted?"
              >In such cases, it will be allowed to register for another study
              subject, such registration will take place during the first two
              weeks of the semester.</NCollapseItem
            >
            <NCollapseItem
              title="What will happen if the student gets a debt from the selected subject of individual studies?"
              >The student will be able to choose to repeat the course of the
              same subject and retake it or choose another new individual study
              subject to liquidate the debt.</NCollapseItem
            >
          </NCollapse>
        </template>
      </div>
    </div>
  </FadeTransition>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import { NCollapse, NCollapseItem } from "naive-ui";
import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const isThemeDark = ref(isDarkMode());

onMounted(() => {
  updateDarkMode(isThemeDark);
});
</script>
