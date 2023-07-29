// More details to every item. Also, keep it in database.
// On the other case, model descriptions describe the current status of a concept implementation
// in the system. It should be kept in like a wiki or something. Maybe just markdown is enough.
export const AgendaItemDescription = (
  <>
    <p>
      Kiekvienas posėdis gali turėti posėdžio klausimų. Kiekvienas posėdžio
      klausimas gali turėti tik vieną posėdį.
    </p>
    <p>
      Posėdžio klausimai skiriasi nuo <strong>svarstomų klausimų</strong> tuo,
      kad svarstomi klausimai yra tęstiniai, didesnės trukmės negu vienas
      posėdis ir gali būti priskiriami <strong>tikslui</strong>.
    </p>
    <p>
      Kiekvienas posėdžio klausimas taip pat gali būti priskiriamas ir
      svarstomam klausimui.
    </p>
  </>
);

export const BannerDescription = (
  <>
    <p>
      Baneriai yra paveikslėliai su nuorodomis, besikeičiantys karuselės
      principu. Jie yra naudojami pagrindiniame puslapyje, skirti reklamuoti
      bendroves, VU SA PKP bei partnerius.
    </p>
    <p>
      Šiuo metu banerių funkcija yra{" "}
      <strong class="text-vusa-red">išjungta.</strong>
    </p>
  </>
);

export const CalendarDescription = (
  <>
    <p>
      Kalendoriaus įvykiai yra skirti žymėti VU SA, VU SA P ar PKP renginius,
      veiklkas ir kitas studentams svarbias datas, pavyzdžiui, artėjančius
      terminus arba šventes.
    </p>
    <p>
      Kalendoriaus įvykiai rodomi VU SA pradiniame puslapyje, taip pat
      kiekvienas įvykis turi atskirą puslapį.
    </p>
  </>
);

export const CommentDescription = (
  <>
    <p>
      Komentarus teksto pavidalu gali palikti daugelis aplikacijos naudotojų,
      atitinkamose jos vietose. Komentaras yra priskiriamas tiek asmeniui, kuris
      parašė komentarą, o jeigu komentaras parašytas, pvz.: posėdyje, ir su juo.
    </p>
  </>
);

export const ContactDescription = (
  <>
    <p>
      Kontaktai yra žmonės, kurie yra susiję su VU SA, VU SA P ar PKP, bet
      neturi prisijungimo prie platformos. Tai gali būti institucijų vadovai,
      kiti partneriai ar pažymėtini žmonės, kurie gali būti susiję su VU SA
      veikla.
    </p>
  </>
);

export const DoingDescription = (
  <>
    <p>
      Veiksmai gali bet kokia VU SA vartotojo veikla, atliekama būnant VU SA.
    </p>
    <p>
      Veiklos gali būti priskiriamos prie kokio nors tikslo arba svarstomo
      klausimo, taip susiejant veiklas į vieną visumą, tas gali geriau
      atskleisti ar veiklos yra atliekamos vedamos tikslo, kokį poveikį turėjo
      ir pan.
    </p>
  </>
);

export const DutyDescription = (
  <>
    <p>
      Pareigybės yra tiek VU SA naudotojų, tiek kontaktų pareigos, užimamos
      institucijoje.
    </p>
  </>
);

export const GoalDescription = (
  <>
    <p>
      Tikslas yra mintis, kurią įgyvendinus, yra sukūriamas norimas ir,
      dažniausiai gerai, kad būtų, teigiamas ir reikšmingas poveikis tikslą
      įgyvendinančiai žmonių grupei.
    </p>
    <p>
      Tikslas <strong>nėra</strong>:
    </p>
    <ul>
      <li>
        Veikla. Pvz.: <i>„suorganizuoti renginį 100 žmonių kokia nors proga“</i>{" "}
        NĖRA tikslas.
      </li>
      <li>
        Tikslų grupė (šioje platformoje). Pvz.:„60 kreditų įgyvendinimas
        Vilniaus universitete“, kai tai yra svarstoma padaliniuose, bus tikslų
        grupė. Tikslas bus: 60 kreditų įgyvendinimas VU FsF studijų programose.
      </li>
    </ul>
  </>
);

export const InstitutionDescription = (
  <>
    <p>Institucija yra oficialus žmonių darinys, organas, organizacija.</p>
    <p>
      Apie instituciją galima galvoti kaip informacijos centrą, jungiantį
      daugelį kitų objektų . Institucijos ryšiai:
    </p>
    <ul>
      <li>
        <strong>Pareigybės</strong> - gali turėti neribotą kiekį pareigybių.
        Pareigybėje yra nustatoma koks ją užimančių narių limitas. Pareigybės
        jungia asmenis ir kontaktus su institucijomis.
      </li>
      <li>
        <strong>Susitikimai (posėdžiai)</strong> - institucija gali turėti daug
        susitikimų. Palaikoma galimybė dviems institucijoms dalyvauti tame
        pačiame susitikime.
      </li>
      <li>
        <strong>Svarstomi klausimai (temos)</strong> - tai yra einamoji
        institucijos <i>agenda</i>, iš esmės temos, kurias institucija
        sprendžia. Iš šių temų gali būti sukurti posėdžio klausimai, kurie
        susiejami su tam tikru posėdžiu.
      </li>
      <li>
        <strong>Padalinys</strong> - fiksuotas loginis vienetas, kuris grupuoja
        institucijas ir jų žmones.
      </li>
    </ul>
  </>
);

export const MainPageDescription = (
  <>
    <p>
      Greitieji mygtukai yra mygtukai, kurie matomi vusa.lt ir padalinių
      pagrindiniuose puslapiuose.
    </p>
    <p>Ši funkcija dar bus plėtojama.</p>
  </>
);

export const MatterDescription = (
  <>
    <p>
      Svarstomas klausimas (tema) yra institucijos idėja ar periodinė veikla.
      Klausimai gali būti susieti su tikslais, t.y. išsprendus klausimą galime
      matyti tai, kaip progresą siekiant tikslo.
    </p>
    <p>
      Šis klausimas turi abstrakčiai apibrėžtą svarstymo laiką. Galima svarstomų
      klausimų visumą prilyginti institucijos, pvz.: metinei darbotvarkei.
    </p>
    <p>
      Kai yra organizuojamas posėdis, iš šių svarstomų klausimų (temų) galima
      sukurti posėdžio klausimus, kurie yra susiejami su atitinkamu posėdžiu,
      sukurtu iš institucijos objekto.
    </p>
  </>
);

export const MeetingDescription = (
  <>
    <p>
      Susitikimai (šiuo metu) yra bet kokie institucijos (-ų) susitikimai,
      kuriame turi sprendžiamų klausimų.
    </p>
    <p>
      Susitikimai gali būti nebūtinai posėdžiai, bet ir išvažiavimai, neformalūs
      susitikimai.
    </p>
  </>
);

export const NavigationDescription = (
  <>
    <p>
      Navigacija yra puslapio viršutinė sekcija, kuri yra fiksuota visame viešai
      prieiname tinklapyje.
    </p>
  </>
);

export const NewsDescription = (
  <>
    <p>
      Naujienos yra puslapiai, turintys skelbimo laiką ir rodomi naujienų
      skiltyje.
    </p>
  </>
);

export const PageDescription = (
  <>
    <p>
      Puslapiai yra tinklapio... puslapiai, turintys tekstinės ar vaizdinės
      informacijos.
    </p>
  </>
);

export const PermissionDescription = (
  <>
    <p>
      Teisės suteikia galimybę pasiekti, kuria nors dalį puslapio, jau
      prisijungus prie jo.
    </p>
    <p>
      Teisės dažniausiai susijusios su objektais - institucijomis, tikslais ir
      pan., ir šias teises priskyrus rolei, jos leis atlikti atitinkamus
      veiksmus.
    </p>
    <p>
      Teisės taip pat gali veikti tam tikra apimtimi, t.y.: 'savo', padalinio
      arba visos platformos.
    </p>
  </>
);

export const RelationshipDescription = (
  <>
    <p>
      Ryšys yra santykis tarp dviejų objektų: ryšio teikėjo ir ryšio gavėjo. Šie
      objektai šiuo metu gali būti: institucijos arba tipai.
    </p>
  </>
);

export const ReservationDescription = (
  <>
    <p>
      Rezervacijomis rezervuojami ištekliai. Ištekliai dažniausiai yra daiktai.
    </p>
  </>
);

export const ResourceDescription = (
  <>
    <p>
      Ištekliai yra daiktai, kurie gali būti rezervuojami. Ištekliai susieti su
      padaliniu, kuris juos turi.
    </p>
  </>
);

export const RoleDescription = (
  <>
    <p>
      Rolė yra šios platformos administracinė funkcija, kuri leidžia nustatyti
      veiksmų galimybę pareigybėms arba naudotojams. Kiekviena rolė turi savo
      teises, kurias galima tvarkyti kiekvienai rolei atskirai.
    </p>
    <p>
      Rolė, kuri yra priskirta pareigybei, jos naudotojams suteiks visas teises,
      kurias turi rolė.
    </p>
  </>
);

export const SaziningaiExamDescription = (
  <>
    <p>
      Sąžiningai egzaminų modulis leidžia VU SA bendruomenės nariams registruoti
      egzaminus į atsiskaitymus. Užregistruoti egzaminai užfiksuojami sistemoje
      ir jų informacija panaudojama tolimesniam procesui.
    </p>
  </>
);

export const SharepointFileDescription = (
  <>
    <p>
      Dokumentai yra failai, kuriuos galima įkelti prie objekto, pvz.: posėdžio.
      Dokumentai yra įkeliami į Sharepoint failų laikymo sistemą. Priklausomai
      nuo dokumento tipo ir objekto, dokumentas gali būti viešas arba privatus.
    </p>
  </>
);

export const TaskDescription = (
  <>
    <p>
      Užduotys yra mažos apimties veiksmai, kurie gali būti priskirti vienam ar
      keliem žmonėm ir būti atlikti.
    </p>
    <p>
      Visos naudotojo užduotys gali būti rodomos viename lange, paspaudus
      viršuje esantį užduočių mygtuką. Kai kurios užduotys yra sukūriamos
      automatiškai.
    </p>
  </>
);

export const TypeDescription = (
  <>
    <p>
      Tipai yra objektų kategorija, kuri leidžia objektus telkti pagal jų
      funkciją ar ypatybes, bei kurti ryšius.
    </p>
    <p>Objektas gali turėti tik vieną tipą.</p>
  </>
);

export const UserDescription = (
  <>
    <p>
      Naudotojai dažniausiai yra VU SA bendruomenės nariai, kurie gali
      prisijungti prie sistemos ir naudotis jos funkcijomis.
    </p>
    <p>
      Naudotojai gali turėti pareigybes, veiklas, turėti sukurtų užduočių ir
      gauti pranešimus.
    </p>
  </>
);
