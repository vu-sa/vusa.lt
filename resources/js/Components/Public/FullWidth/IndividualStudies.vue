<template>
  <div>
    <NDivider />
    <div class="typography text-sm leading-7">
      <h2
        class="my-4 border-none text-2xl font-bold text-gray-900 duration-200 hover:text-vusa-red dark:text-zinc-50 lg:w-4/5 lg:text-3xl">
        <a class="flex w-fit flex-row items-center gap-2" style="text-decoration: none" :href="route('page', {
          lang: $page.props.app.locale,
          subdomain: 'www',
          permalink:
            $page.props.app.locale === 'lt'
              ? 'individualios-studijos'
              : 'individual-studies',
        })
          "><template v-if="$page.props.app.locale === 'lt'">Individualios studijos</template><template
            v-else>Individual studies</template>
          <IFluentArrowCircleRight20Regular />
        </a>
      </h2>
      <div class="space-between flex flex-row">
        <p class="typography mb-4 text-sm leading-6 lg:max-w-[80ch] lg:text-base lg:leading-6">
          <template v-if="$page.props.app.locale === 'lt'">
            <p>
              Nuo 2023 m. Vilniaus universitete kiekvienas naujai įstojęs (-usi)
              bakalauro ar vientisųjų studijų programos studentas (-ė) turi
              galimybę dėlioti savo studijas pagal asmeninius interesus,
              pasinaudodas (-a) individualių studijų galimybe. Tai suteikia
              studentams (-ėms) daugiau laisvės įgyti norimas kompetencijas ir
              žinias už pasirinktos studijų programų ribų.
              <a :href="route('page', {
                lang: $page.props.app.locale,
                subdomain: 'www',
                permalink: 'individualios-studijos',
              })
                " class="hover:text-vusa-red">Skaitykite plačiau</a>
            </p>
          </template>
          <template v-else>
            <p>
              From 2023 onwards, every bachelor and integrated studies student
              will have the ability to tailor their studies with the use of
              individual studies, resulting in an opportunity to acquire desired
              competencies and knowledge beyond their chosen study programme.
              <a :href="route('page', {
                lang: $page.props.app.locale,
                subdomain: 'www',
                permalink: 'individual-studies',
              })
                " class="no-underline hover:text-vusa-red">Read more</a>
            </p>
          </template>
        </p>
      </div>
    </div>
    <NDivider />
  </div>
</template>
