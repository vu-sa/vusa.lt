<template>
  <NModal v-model:show="showTaryba">
    <div class="w-2/3 md:w-1/2 bg-white/95 shadow-lg rounded-lg p-6">
      <h2 class="text-center mb-4">VU SA Board</h2>
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 overflow-auto gap-4 duration-200"
      >
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Ignas Mugenis_Pirmininkas_CHGF-2.jpg"
          />
          <p class="mt-1 text-sm">{{ __("VU SA CHGF") }}</p>
          <strong>Ignas Mugenis</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Justinas Sacevičius_Pirmininkas_EVAF.jpg"
          />
          <p class="mt-1 text-sm">{{ __("VU SA EVAF") }}</p>
          <strong>Justinas Sacevičius</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Raimondas Šiškevičius_Pirminkas_FF.jpg"
          />
          <p class="mt-1 text-sm">VU SA FF</p>
          <strong>Raimondas Šiškevičius</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Elena Pranevičiūtė_Pirmininkė_FilF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FilF</p>
          <strong>Elena Pranevičiūtė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Martyna Vilutytė_Pirmininke_FsF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA FsF</p>
          <strong>Martyna Vilutytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Kornelija Buivydaitė_Pirmininkė_GMC-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA GMC</p>
          <strong>Kornelija Buivydaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Ieva Šiaudvytytė_Pirmininke_IF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA IF</p>
          <strong>Ieva Šiaudvytytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Dominyka Goldbergaitė_pirmininkė_KF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA KF</p>
          <strong>Dominyka Goldbergaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Lijana Savickienė_Pirmininke_KnF.jpg"
          />
          <p class="mt-1 text-sm">VU SA KnF</p>
          <strong>Lijana Savickienė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Emilija Narušytė_Pirminnke_MF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MF</p>
          <strong>Emilija Narušytė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Paulina Bilinskaitė_Pirminink_MIF-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA MIF</p>
          <strong>Paulina Bilinskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Paulius-Ladukas_Pirmininkas_ŠA-2.jpg"
          />
          <p class="mt-1 text-sm">VU SA ŠA</p>
          <strong>Paulius Ladukas</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Airina Mikulėnaitė_Pirmininke_TF.jpg"
          />
          <p class="mt-1 text-sm">VU SA TF</p>
          <strong>Airina Mikulėnaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Ugnė Bičkauskaitė_Pirmininkė_TSPMI.jpg"
          />
          <p class="mt-1 text-sm">VU SA TSPMI</p>
          <strong>Ugnė Bičkauskaitė</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Justinas Brazaitis – VU SA VM Pirmininkas (iki 2021 gruodžio mėn.).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Justinas Brazaitis (iki 2021 gruodžio mėn.)</strong>
        </div>
        <div>
          <img
            class="md:h-40 w-full object-cover rounded-md shadow-sm"
            src="/images/ataskaita2022/koordinatoriu-nuotraukos/taryba/Edgaras Kodis_Pirmininkas_VM(nuo 2021-12).jpg"
          />
          <p class="mt-1 text-sm">VU SA VM</p>
          <strong>Edgaras Kodis (nuo 2021 gruodžio mėn.)</strong>
        </div>
      </div>
    </div></NModal
  >
  <VUSA>
    <!-- <div
      class="order-1 col-span-1 top-40 sticky ml-4"
      style="height: 200px; align-self: start; justify-self: start"
    > -->
    <!-- <NAnchor>
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="Apie VU SA"
          href="#apie-vu-sa"
        />
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="VU SA strategija"
          href="#strategija"
        />
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="VU SA valdymo schema"
          href="#valdymo-schema"
        />
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="Parlamentas"
          href="#parlamentas"
        >
          <NAnchorLink
            style="font-size: 12pt; font-weight: initial"
            title="Raštai"
            href="#rastai"
          />
        </NAnchorLink>
        <NAnchorLink
          style="font-size: 12pt; font-weight: bold"
          title="Taryba"
          href="#taryba"
        />
      </NAnchor> -->
    <!-- </div> -->
    <div class="relative mb-4 h-1/2 w-full">
      <img
        class="max-w-[92ch] rounded-lg w-full h-60 object-cover mx-auto brightness-50"
        style="object-position: 0% 20%"
        src="/images/ataskaita2022/kitos-nuotraukos/IMG_8823.jpg"
      />
      <p
        class="absolute text-white font-bold text-3xl sm:text-4xl md:text-5xl w-full text-center bottom-8"
      >
        VU SR
      </p>
    </div>

    <div class="w-full mx-auto" style="align-self: end">
      <NCollapse class="max-w-[92ch] mx-auto" arrow-placement="right">
        <NCollapseItem>
          <template #header
            ><h2 id="apie-vu-sa" class="rounded-lg">About SR</h2></template
          >
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              Vilnius University Students’ Representation (VU SR) is the oldest and the
              biggest expert education organization in Lithuania. It represents the
              interests of students at Vilnius University and beyond, fostering university
              culture and creating the most favorable conditions for students’
              self-expression.
            </p>
            <p>
              In total, VU SR has 15 units in each core academic unit (faculty, institute,
              or center) that represent students locally. VU SR also has 15 programs,
              clubs, projects and public bodies (hereinafter - PCP) as well as one Central
              Office, which coordinates all the units, PCP and Vilnius University Alumni
              Society. For this day VU SR unites around 1 000 members.
            </p>
            <p>VU SR:</p>
            <ul>
              <li>
                represents students – defends students’ rights in University’s governing
                bodies and on the national level;
              </li>
              <li>
                contributes to the improvement of the study process in the University, is
                a part of all the most important governing bodies of Vilnius University;
              </li>
              <li>
                takes care of social welfare – provides a comfortable and student–friendly
                environment, takes care of student integration, participates in the
                distribution of scholarships; organizes meaningful leisure time;
              </li>
              <li>
                spreads the information so that all relevant news reaches the students.
              </li>
            </ul>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header
            ><h2 id="strategija">
              The Strategy of Vilnius University Students’ Representation
            </h2></template
          >
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              <strong>VU SR Vision</strong> – an open environment for independent student
              development in the unified community of Vilnius University.
            </p>
            <p>
              <strong>VU SR Mission</strong> – to create the most favourable conditions
              for student self-expression by effectively representing the students of
              Vilnius University and fostering the university culture.
            </p>
            <p><strong>Core values</strong>:</p>

            <ul>
              <li>Openness;</li>

              <li>Responsibility;</li>

              <li>Quality of work;</li>

              <li>Unity;</li>

              <li>Development.</li>
            </ul>
            <p><strong>Strategic directions</strong>:</p>

            <ul>
              <li>High quality studies and their environment;</li>

              <li>Strong organization;</li>

              <li>Sustainable University community.</li>
            </ul>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header
            ><h2 id="valdymo-schema">VU SR management scheme</h2></template
          >
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              The governance structure of the Vilnius University Students' Representation
              consists of: Conference (the highest self-government institution of VU SR),
              the President, parliament, the Board and the Revision Commission. These
              management bodies, within their remit, shall take decisions in the areas
              assigned to them or ensure the implementation of the resolutions adopted in
              other management bodies of VU SR.
            </p>
            <p>
              You can get acquainted with the structure of VU SR here:
              <Link
                :href="
                  route('main.page', {
                    lang: $page.props.locale,
                    permalink: 'vu-sa-struktura',
                  })
                "
                >https://vusa.lt/lt/vu-sa-struktura</Link
              >
            </p>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header><h2 id="parlamentas">VU SR Parliament</h2></template>
          <div class="relative">
            <img
              class="max-w-[80ch] rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 mx-auto"
              src="/images/ataskaita2022/kitos-nuotraukos/Parlamentas.jpg"
            />
            <p
              class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
            >
              Parlamentas
            </p>
          </div>
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              The Parliament of VU SR (lt. VU SA) was chaired by Aistė Rinkevičiūtė (until
              February 2022) and Ugnius Sejonas (since February 2022).
            </p>

            <p>In 2021-2022, the VU SR Parliament consisted of:</p>
            <ul>
              <li>VU SR President Neda Žutautaitė</li>
              <li>VU SR Vice-President Arnoldas Solovjovas</li>
            </ul>
            <h3>VU SA padalinių atstovai</h3>
            <NCollapse>
              <NCollapseItem title="VU SA CHGF" name="1">
                <ul>
                  <li>Ignas Mugenis</li>
                  <li>Gabija Navašinskaitė</li>
                  <li>Viktorija Vrubliauskaitė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA EVAF" name="2">
                <ul>
                  <li>Justinas Sacevičius</li>
                  <li>Vanesa Ražinskytė</li>
                  <li>Nadia Ronlev</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA FilF" name="3">
                <ul>
                  <li>Elena Pranevičiūtė</li>
                  <li>Anastasija Liubinskytė</li>
                  <li>Lauryna Martin Marin</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA FsF" name="4">
                <ul>
                  <li>Martyna Vilutytė</li>
                  <li>Vakarė Punelytė (iki 2022 m. vasario mėn.)</li>
                  <li>Aistė Rinkevičiūtė (iki 2022 m. vasario mėn.)</li>
                  <li>Vismantas Daujotas (nuo 2022 m. vasario mėn.)</li>
                  <li>Andrė Treinytė (nuo 2022 m. vasario mėn.)</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA FF" name="5">
                <ul>
                  <li>Raimondas Šiškevičius</li>
                  <li>Kasparas Daranda</li>
                  <li>Kornelija Kairytė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA GMC" name="6">
                <ul>
                  <li>Kornelija Buivydaitė</li>
                  <li>Evelina Prosevičiūtė (iki 2022 m. vasario mėn.)</li>
                  <li>Lukas Šarauskas (iki 2022 m. vasario mėn.)</li>
                  <li>Eglė Jašinskaitė (nuo 2022 m. vasario mėn.)</li>
                  <li>Daniel Šematovič (nuo 2022 m. vasario mėn.)</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA IF" name="7">
                <ul>
                  <li>Ieva Šiaudvytytė</li>
                  <li>Aušrinė Dichavičiūtė</li>
                  <li>Tomas Mikalauskas</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA KnF" name="8">
                <ul>
                  <li>Lijana Savickienė</li>
                  <li>Indrė Bagdžiūtė</li>
                  <li>Ieva Šilingaitė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA KF" name="9">
                <ul>
                  <li>Dominyka Goldbergaitė</li>
                  <li>Gabija Laibakojytė</li>
                  <li>Kristijonas Ražickas</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA MIF" name="10">
                <ul>
                  <li>Paulina Bilinskaitė</li>
                  <li>Nikita Gladčenko</li>
                  <li>Rita Žilinskaitė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA MF" name="11">
                <ul>
                  <li>Emilija Narušytė</li>
                  <li>Julija Elena Koehnke</li>
                  <li>Gabija Venclovaitė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA ŠA" name="12">
                <ul>
                  <li>Paulius Ladukas</li>
                  <li>Akvilė Bušininkaitė</li>
                  <li>Ieva Šukytė</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA TSPMI" name="13">
                <ul>
                  <li>Ugnė Bičkauskaitė</li>
                  <li>Ilijas Kulinič</li>
                  <li>Ugnius Sejonas</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA TF" name="14">
                <ul>
                  <li>Airina Mikulėnaitė</li>
                  <li>Klaudija Maliauskaitė</li>
                  <li>Dominykas Mečanec</li>
                </ul>
              </NCollapseItem>
              <NCollapseItem title="VU SA VM" name="15">
                <ul>
                  <li>Justinas Brazaitis (iki 2021 m. gruodžio mėn.)</li>
                  <li>Edgaras Kodis (nuo 2021 m. gruodžio mėn.)</li>
                  <li>Matas Krikščiūnas</li>
                  <li>Ričardas Ratkevičius</li>
                </ul>
              </NCollapseItem>
            </NCollapse>
            <p>
              VU SR Parliament is the governing body of VU SR, which discusses the most
              important social and academic issues related to students or other strategic
              issues of VU SR. The Parliament shall consist of the president of the
              organization, the vice-president, the chairmen of the VU SR U (unit)
              performing the function of representation and two representatives from each
              unit. The work of Parliament shall be organized by the President.
            </p>
            <p>
              During the reference period, 14 meetings of the VU SR Parliament took place
              (of which 5 electronic, 8 remote and 1 face-to-face).
            </p>
            <NDivider></NDivider>
            <section id="rastai">
              <h3 class="text-center text-2xl">Documents</h3>
              <NDivider></NDivider>
              <NCollapse accordion>
                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      A temporary working group was set up and a resolution on the
                      integration of foreign students at Vilnius University was accepted
                    </h4>
                  </template>

                  <p>
                    The Parliament of VU SR, confirming the main preconditions for the
                    integration process and submitting proposals for the improvement of
                    this process, adopted a resolution on the integration of foreign
                    students at the University. Realizing the importance of integration,
                    which is evident when foreign students try to integrate into the
                    social and academic life of the University, the Parliament of VU SR
                    tries in this letter to draw attention to the dissemination of
                    information in the University's communication channels and physical
                    spaces – it encourages to spread the message not only in Lithuanian,
                    but also in English, and thus to provide all the necessary information
                    to foreign students(s). The letter also emphasizes the topic of
                    foreign language competences of the University's staff, accountability
                    and inclusiveness of university formations, creation of additional
                    activities in a foreign language for all members of the community
                    (proposals on how to implement all this were also submitted).
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      A temporary working group has been set up and a position on the
                      prevention of violations of academic ethics has been accepted
                    </h4>
                  </template>

                  <p>
                    Based on the position on the prevention of violations of academic
                    ethics, the VU SR Parliament declared its intention to contribute to
                    the elimination of violations of the principal provisions of the Code
                    of Academic Ethics. The main problems raised are: the difficulty of
                    access to information and the lack of a position as an academic ethics
                    consultant(s) for students – the letter contains proposals on how to
                    solve these problems, including proposals to conduct research in the
                    field of academic ethics or to establish a post of a person consulting
                    students.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      A temporary working group has been set up and a position on the
                      legal situation of foreign students(s) has been approved
                    </h4>
                  </template>

                  <p>
                    In order to draw the attention of the State of Lithuania to the legal
                    situation of foreign students in Lithuania and their growing number,
                    the Parliament of VU SR emphasizes that the legal status of foreign
                    and Lithuanian students must be equivalent, thus creating suitable
                    conditions for foreign students(s) to become full residents of the
                    Republic of Lithuania. The main proposals to solve this problem are
                    focused on equally accessible social services, equal opportunities for
                    academic development and relief of financial burdens.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      A temporary working group has been set up and a resolution on the
                      scientific activities of students(s) in Lithuanian universities has
                      been accepted
                    </h4>
                  </template>

                  <p>
                    In order to draw attention to the importance of students' scientific
                    activities and understanding their needs and untapped opportunities in
                    the course of scientific activities, the Parliament of VU SR included
                    proposals in the letter aimed at improving the possibilities and
                    quality of scientific activities of students(s). The main proposals
                    mentioned in the resolution are focused on the definition of the
                    concept of "student(s) scientific activity", increasing the funding of
                    scientific activities of students(s), creating appropriate conditions
                    for the conduct of scientific activities and increasing the
                    accountability of universities by creating suitable conditions for
                    students(s) to carry out scientific activities.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      A temporary working group has been set up and a position on
                      improving the organization of student(s) practices has been accepted
                    </h4>
                  </template>

                  <p>
                    In order to draw attention to the problems arising in the process of
                    organizing practices, by this letter the Parliament of VU SR draws
                    attention to the shortcomings of both Vilnius University and the
                    mechanisms regulating and regulating the quality of practice at the
                    national level. The main proposals are focused on compensating for the
                    costs incurred during unpaid internships or in the creation of paid
                    placements, as well as the creation of a culture of feedback and a
                    system during practice. In addition, the focus is on ensuring equal
                    practice opportunities for Lithuanian and foreign students at the
                    place of practice.
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      A temporary working group has been set up and a position on the low
                      level of financial literacy of Vilnius University students(s) has
                      been approved
                    </h4>
                  </template>

                  <p>
                    In order to draw attention to the low level of financial literacy of
                    students at Vilnius University, in this letter the VU SR Parliament
                    submits proposals for solutions to the problem, emphasizing the
                    importance of financial literacy through the prism of study
                    accommodation and the financial well-being of the student(s). The main
                    proposals are focused on the preparation of training, public education
                    and general university studies subjects available to the entire
                    Vilnius University student(s) community, which would expand the
                    knowledge of financial literacy of students(s).
                  </p>
                </NCollapseItem>

                <NCollapseItem>
                  <template #header>
                    <h4 class="mb-0">
                      During the meeting, a resolution on the position of the organization
                      was approved in the light of the processes taking place in Ukraine
                    </h4>
                  </template>

                  <p>
                    By this resolution, the Parliament of VU SR, expressing the
                    organization's support for the Republic of Ukraine in the face of the
                    war and condemning the military actions of the Russian Federation and
                    the outbreak of violence on the territory of the Republic of Ukraine,
                    formed the position of the organization in relation to the war in
                    Ukraine.
                  </p>
                </NCollapseItem>
              </NCollapse>
            </section>
            <NDivider></NDivider>

            <strong>Other works done</strong>

            <ul>
              <li>
                Elected representatives of students in the VU Senate (Ugnė Bičkauskaitė,
                Kornelija Buivydaitė, Julija Jarutytė, Andželika Jankauskytė, Justas
                Kvedaravičius, Vanesa Ražinskytė, Arnoldas Solovjovas, Daniel Šematovič)
                and VU Council external student representative Justinas Kandrotas;
              </li>
              <li>
                The regulations of VU SR MF have been approved by establishing the Council
                of International Students of the Vilnius University Students’
                Representation at the Faculty of Medicine and approving the rules of
                procedure of the latter;
              </li>
              <li>
                VU SR Strategic Year's Operational Guidelines (September 2021) have been
                approved and the annual activity plan has been updated after the
                restructurings (February 2022);
              </li>
              <li>
                The approved procedure regarding the election of an external
                representative of students in the Council of Vilnius University (VU SR did
                not have such a representative(s) before);
              </li>
              <li>The process of updating the Rules of Procedure has been initiated;</li>
              <li>
                The process of establishing permanent working groups (committees) of the
                VU SA Parliament was initiated and work on legal regulation was started;
              </li>
              <li>
                In March, during the meeting, the study of the 2021 process of integration
                of freshmen at the University was presented;
              </li>
              <li>
                During each meeting, the practice of information relevant to the
                Parliament of VU SR, which was presented by the President of VU SR, was
                applied;
              </li>
              <li>
                A working group on communication of the Parliament of VU SR was set up,
                which during the meetings discussed and generated proposals for the
                resumption of Parliament's communication on social networks;
              </li>
              <li>
                A guide for the MP of VU SR has been compiled, which contains all the most
                important information about the Parliament and its functioning, for new
                people who are preparing to participate in the Parliament;
              </li>
              <li>All remote meetings were broadcasted on VU SR's Facebook page;</li>
              <li>
                After each remote and contact session of the VU SR Parliament, articles
                were published on the VU SR page informing about what was discussed and
                approved during the meetings;
              </li>
              <li>
                In the summer, training of VU SR Parliament was organized to strengthen
                the skills of parliamentarians in the fields of advocacy, writing,
                perception of Parliament and the organization, as well as the development
                of general competences;
              </li>
              <li>
                Individual interviews were conducted twice between the chairperson of the
                Parliament of VU SR and the parliamentarians of VU SR units, aimed at
                collecting problems and opinions of the academic core units;
              </li>
              <li>
                The estimate of financial expenditure and revenue for 2022 has been
                approved;
              </li>
              <li>
                During the meetings, studies took place on:
                <ul>
                  <li>integration of students;</li>
                  <li>scientific activities of students(s);</li>
                  <li>integration of foreign students;</li>
                  <li>
                    execution and quality assurance of student(s) practices at the
                    University and beyond.
                  </li>
                </ul>
              </li>
              <li>
                The sittings of the VU SR Parliament were attended and talked about by
                invited guests – Andrius Uždanavičius, head of the Department of Quality
                and Development of Studies at Vilnius University, and Vigita Vėbraitė,
                Chairwoman of the Central Commission for Academic Ethics at Vilnius
                University.
              </li>
            </ul>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header><h2 id="taryba">VU SA Board</h2></template>
          <div class="relative">
            <NButton
              text
              class="relative"
              style="white-space: normal; margin: 0 auto 0 auto; width: 100%"
              @click="showTaryba = true"
            >
              <img
                class="rounded-lg max-w-[80ch] w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 mx-auto"
                src="/images/ataskaita2022/koordinatoriu-nuotraukos/bendros/Taryba-2.jpg"
              />
            </NButton>
            <p
              class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
            >
              VU SA Board
            </p>
          </div>
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>In 2021-2022, the VU SR (lt. VU SA) Board consisted of:</p>
            <ul>
              <li><strong>VU SR President:</strong> Neda Žutautaitė</li>
              <li><strong>VU SA CHGF:</strong> Ignas Mugenis</li>
              <li><strong>VU SA EVAF:</strong> Justinas Sacevičius</li>
              <li><strong>VU SA FF:</strong> Raimondas Šiškevičius</li>
              <li><strong>VU SA FilF:</strong> Elena Pranevičiūtė</li>
              <li><strong>VU SA FsF:</strong> Martyna Vilutytė</li>
              <li><strong>VU SA GMC:</strong> Kornelija Buivydaitė</li>
              <li><strong>VU SA IF:</strong> Ieva Šiaudvytytė</li>
              <li><strong>VU SA KF:</strong> Dominyka Goldbergaitė</li>
              <li><strong>VU SA KnF:</strong> Lijana Savickienė</li>
              <li><strong>VU SA MF:</strong> Emilija Narušytė</li>
              <li><strong>VU SA MIF:</strong> Paulina Bilinskaitė</li>
              <li><strong>VU SA ŠA:</strong> Paulius Ladukas</li>
              <li><strong>VU SA TF:</strong> Airina Mikulėnaitė</li>
              <li><strong>VU SA TSPMI:</strong> Ugnė Bičkauskaitė</li>
              <li>
                <strong>VU SA VM:</strong> Justinas Brazaitis (iki 2021 m. gruodžio mėn.),
                Edgaras Kodis (nuo 2021 m. gruodžio mėn.)
              </li>
            </ul>

            <p>
              The VU SR Board is the management body of VU SR, which deals with
              cooperation and internal issues of the organization. During the reference
              period, 9 meetings of the Council of VU SR took place. VU SR Board discussed
              and adopted decisions on institutional strengthening, internal affairs and
              the budget.
            </p>

            <h3>Internal issues</h3>

            <p>
              During the reporting period, the VU SR Board approved the composition of the
              VU Institutional Strengthening Fund, the position of vice-president of VU
              SR, and actively participated in the process of updating the Strategic
              Action Plan of VU SR for 2022-2025.
            </p>
            <strong>The VU SR Board accepted decisions:</strong>
            <ul>
              <li>
                To approve the updated VU SR training organization application template;
              </li>
              <li>
                In each VU SR U, have a person responsible for monitoring exams, thus
                ensuring high-quality activities of the VU SR program "Sąžiningai";
              </li>
              <li>
                Setting up a working group to update the "Chairperson's Guide" in order to
                facilitate the transfer of experience to new heads of departments;
              </li>
              <li>
                Implement the renewal of the VU SR DPG (Data Protection Group) process;
              </li>
              <li>To organize the 2022 reporting-election conferences by contact;</li>
              <li>
                To direct the centralized marketing process of VU SR, in which VU SA U
                marketing coordinators (if any) and volunteers collected by VU SR
                marketing coordinator operate from 1st of July 2022;
              </li>
              <li>
                To validate the concept of membership of the organization, which is based
                on more effective employee engagement, the equal involvement of curators,
                student representatives, programs, clubs, projects and long-term members
                in the ongoing processes on the basis of a culture of self-reflection;
              </li>
              <li>
                To update the membership system, expand the member's test with knowledge
                about the VU structures, the services provided by the University, the VU
                SA strategy, the organisation's structure and image. It was decided to
                develop an e-learning course alongside the test, which will help to ensure
                deeper development of competences in each topic.
              </li>
            </ul>
          </div>
        </NCollapseItem>
        <NCollapseItem>
          <template #header><h2 id="taryba">VU SA Revision Commission</h2></template>
          <div class="bg-white py-6 px-12 rounded-lg max-w-[80ch] prose mx-auto my-4">
            <p>
              The Revision Commission is a controlling institution performing financial
              and operational inspection of VU SR self-government institutions, VU SR
              subdivisions and other structural units of VU SR, as well as legal entities
              established by VU SR. The commission consists of five members elected at the
              VU SR annual convention.
            </p>

            <strong
              >During the reference period, the Revision Commission consisted of:</strong
            >
            <ul>
              <li>Evaldas Matijošaitis (<em>Chairman of the Commission</em>)</li>
              <li>Tomas Bedulskij</li>
              <li>Aušrinė Gedminaitė</li>
              <li>Kamilė Gineikaitė</li>
              <li>Agnė Tamošiūnaitė</li>
            </ul>

            <p>
              2021-2022 The Revision Commission has updated the former methodology of
              evaluation of the activities of VU SR according to the strategy of the
              organization and the descriptions of the processes taking place in the
              organization. Over the course of the year, The Commission carried out
              interim and final evaluations of activities according to this methodology.
              During the evaluation, the Revision Commission regularly kept in touch with
              the units being reviewed, checked the compliance of VU SR, VU SR U, VU SA
              PCP, VU SA DPG members, VU SA ISF and VU SR Parliament with quantitative and
              qualitative evaluation criteria, assessed activities in conducting surveys
              and meetings with the units being revised.
            </p>

            <p>
              During the reporting period, 2 open meetings of the Revision Commission and
              25 closed working meetings took place. The Revision Commission participated
              in VU SR Parliament and Council meetings, trainings carried out by the
              organization, planning strategic goals and strategic plan development
              sessions. The evaluation of the organization's activities was carried out by
              collecting information in different ways: by participating in the joint
              activities of the organization, organizing individual meetings with the
              sitting members of VU SR and conducting written surveys. The Revision
              Commission also advised the members of VU SR and provided answers to their
              questions related to the activities and legal acts of VU SR.
            </p>

            <p>
              The Audit Commission shall submit the report on the evaluation of the
              activities of the organization during the report-election conference of VU
              SR (the evaluation report can be accessed
              vusa.lt/lt/veiklos-ir-tyrimu-ataskaitos).
            </p>
          </div>
        </NCollapseItem>
      </NCollapse>
    </div>
    <!-- <div class="mt-6 text-right max-w-[92ch]">
      <Link
        class="underline duration-200 hover:text-red-600"
        :href="
          route('main.ataskaita2022', { lang: $page.props.locale, permalink: 'mvp' })
        "
        >Toliau: Metų veiklos planas >></Link
      >
    </div> -->
  </VUSA>
</template>

<script setup>
import VUSA from "../2-VU-SA.vue";
import { Link } from "@inertiajs/inertia-vue3";
import {
  NCollapse,
  NCollapseItem,
  NDivider,
  NAnchor,
  NAnchorLink,
  NModal,
  NButton,
} from "naive-ui";
import { ref } from "vue";

const showTaryba = ref(false);
</script>

<style scoped>
h2 {
  font-size: 1.5rem;
  font-weight: bold;
  /* text-align: center; */
  margin: 0em 0 0em 0;
}
h3,
h4 {
  margin: 1em 0 1em 0;
}
h4.mb-0 {
  margin: 0em 0 0em 0;
}

/* section {
  scroll-margin-top: 120px;
} */

:target::before {
  content: "";
  display: block;
  height: 180px;
  margin-top: -180px;
}
</style>
