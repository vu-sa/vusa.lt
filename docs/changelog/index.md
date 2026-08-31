---
title: Platformos atnaujinimai
lastUpdated: true
---

# Platformos atnaujinimai

Čia rasite visus mano.vusa.lt platformos pakeitimus ir patobulinimus.

## v1.35 — Administratoriai ir laiškai (2026-08-31) {#v1-35}

- 🔧 **Atnaujinta užduotis nebegrįžta seniems atsakingiesiems** — jei posėdžio darbotvarkės užduotis jau buvo atlikta, o vėliau kadencijai nurodytas administratorius, užduotį atvėrus iš naujo (pvz., pakeitus klausimo tipą) ji vėl atitekdavo visiems tuometiniams nariams — ir jiems visiems išeidavo laiškas. Dabar atveriant užduotį atsakingieji nustatomi iš naujo
- 🔧 **Pakeitus kadencijos datas persiskirstomos ir užduotys** — pratęsus ar patrumpinus kadenciją posėdžiai pereina į ją arba iš jos, tačiau jų užduotys likdavo ankstesniems atsakingiesiems

## v1.34 — Nario profilis (2026-08-30) {#v1-34}

- ✨ **Nario puslapio apžvalga perdėliota** — vietoj skaitiklių kortelės „Aktyvumas“, kuri kartojo tai, ką jau sako pareigų sąrašai, apžvalgoje rodomos dabartinės ir buvusios pareigos, o kontaktai perkelti į šoninį sąrašą. Buvusios pareigos pritemdytos, kad dabartinės liktų svarbiausios
- ✨ **Pareigų kortelės nebeneša institucijos statistikos** — nario profilyje nebeliko ženklelio „Pilnai užimta“ ir eilutės „1 / 1 užimta“: jos aprašo pareigas, o ne žmogų. Vietoj to rodomas laikotarpis ir tų pareigų el. paštas. Kas dar eina tas pačias pareigas, matyti skirtuke „Pareigos“
- ✨ **Antraštėje — nuotrauka ir pagrindinės pareigos** — vietoj bendrinės ikonos rodoma nario nuotrauka (arba inicialai), o po vardu — pagrindinės dabartinės pareigos. Datos formatuojamos pagal pasirinktą kalbą
- 🔧 **Trynimo mygtukas rodomas tik tam, kas gali trinti** — meniu „…“ anksčiau siūlė trynimą visiems, kas apskritai matė puslapį

## v1.33 — Institucijos apžvalga (2026-08-30) {#v1-33}

- ✨ **Institucijos apžvalga perdėliota** — pirmasis skirtukas nebekartoja to, kas jau turi savo skirtuką: iš jo pašalintos užduotys ir susijusios institucijos, o būsimo susitikimo juosta ir atskira paskutinio susitikimo eilutė sulietos į vieną kortelę šone. Kairėje — aprašymas ir susitikimai, dešinėje — būsena, nariai ir diskusija
- ✨ **Susitikimų sąrašas rodo darbotvarkę** — vietoj laiko kiekviena eilutė vardija pirmus tris darbotvarkės klausimus ir kiek jų liko, o būsena pažymėta „Būsimas“ arba „Įvykęs“. Nario pareigos rodomos po jo vardu
- ✨ **Antraštėje nebeliko trumpojo pavadinimo** — jis kartojo tą patį, ką ir institucijos pavadinimas

## v1.32 — Darbotvarkės punkto langas (2026-08-30) {#v1-32}

- ✨ **Darbotvarkės punkto puslapis pertvarkytas** — būsenos ženklelis dabar rodomas prieš pavadinimą, klausimo tipas, laikas ir žyma „atstovų iškeltas klausimas“ sutraukti į atskirą kortelę, o aprašymas ir diskusija įrėminti, kad būtų aišku, kur baigiasi vienas blokas ir prasideda kitas
- ⭐ **Balsavimo klausimus galima sutraukti ir pertvarkyti** — užpildytas balsavimas rodomas viena eilute su rezultatais, o nebaigtas atsidaro pats. Tvarką galima keisti tempiant, o pagrindinį balsavimą — pasirinkti ranka spustelėjus žvaigždutę
- ✨ **Administravimo puslapiai nebeišsitempia per visą ekraną** — plačiuose monitoriuose turinys dabar centruotas ir apribotas iki patogaus pločio
- 🔧 **Geltonas taškelis nebežymi tuščių atstovų pastabų** — vien atsivertus punktą būdavo išsaugomas tuščias pastabų dokumentas, todėl darbotvarkėje pažymėti atrodydavo beveik visi klausimai. Dabar žymima tik tada, kai pastabose iš tikrųjų kažkas parašyta, ir vietoje spalvoto taškelio rodoma neutrali ikona

## v1.31 — Užduočių trynimas ir laiškų eilė (2026-08-30) {#v1-31}

- 🔧 **Užduotis pagaliau įmanoma ištrinti** — trynimas nutrūkdavo duomenų bazės lygiu kiekvienai užduočiai, kuriai buvo priskirtas bent vienas žmogus, t. y. beveik visoms
- 🔧 **Ištrynus posėdį, dingsta ir jo užduotys** — anksčiau jos likdavo kaboti be posėdžio ir toliau primindavo apie tai, ko nebeįmanoma atidaryti. Tas pats galioja institucijoms ir rezervacijoms. Grąžinus posėdį iš šiukšlinės, automatinės užduotys atkuriamos su `tasks:repopulate`
- ⭐ **Administratoriai gali ištrinti automatines užduotis** — užduočių lentelėje atsirado mygtukas. Kai kurių automatinių užduočių nebeįmanoma užbaigti, ir turi būti kaip jų atsikratyti
- ⭐ **Laiškų eilės puslapis** — sistemos būsenos kortelė „Laiškų eilė“ dabar veda į puslapį, kuriame matyti, kas dar neišsiųsta: kiekvienas gavėjas ir eilutės, kurias gaus jo santrauka. Administratoriai gali pašalinti atskirą eilutę, visą gavėjo laišką arba išvalyti eilę

## v1.30 — Užduočių pranešimai ir eiga (2026-08-29) {#v1-30}

- 🔧 **VU SA darinių posėdžių užduočių eiga skaičiuojama teisingai** — Parlamento, Tarybos ir kitų VU SA darinių posėdžiuose studentų pozicija ir nauda studentams nepildoma, tačiau užduotis vis tiek jų reikalavo, tad procentas įstrigdavo ties 0 %. Dabar tokiam klausimui pakanka sprendimo
- 🔧 **Automatinės užduotys nebesiunčiamos ne savo laikotarpio nariams** — įvedus seniai įvykusį posėdį, pranešimai nebekeliauja nei iš institucijos pasitraukusiems žmonėms, nei šiandieniniams nariams, kurių tuo metu institucijoje dar nebuvo. Rankiniu būdu priskirtos užduotys ir rezervacijų užduotys nekeičiamos

## v1.29 — Institucijų administratoriai (2026-08-29) {#v1-29}

- ⭐ **Institucijai galima nurodyti administratorius** — institucijos redagavimo lange, prie kadencijų, dabar galima kiekvienai kadencijai priskirti konkrečius žmones. Jie nurodomi asmeniškai, ne per pareigybę, o žmonės, jau esantys institucijoje, siūlomi vienu paspaudimu
- ⭐ **Kai administratoriai nurodyti, posėdžių užduotys tenka tik jiems** — visi kiti nariai tų užduočių nebegauna. Jei administratorių nėra, užduotys, kaip ir anksčiau, tenka tuo metu aktyviems atstovams
- 🔧 **Užduotys ir priminimai nebekliūva buvusiems nariams** — anksčiau posėdžių priminimai keliaudavo visiems, kada nors turėjusiems pareigų institucijoje, neatsižvelgiant į datas. Dabar gauna tik tie, kurie tuo metu iš tikrųjų ėjo pareigas
- 🔧 **Komentarų ir paminėjimų adresatai taip pat pagal datą** — paminėjus instituciją ar posėdį komentare, pranešimas nebekeliauja prieš kelerius metus iš pareigų pasitraukusiems žmonėms
- ✨ **Administratoriai matomi posėdžio ir institucijos puslapiuose** — rodomi atskirai nuo narių ir vadovų, nes administratorius nebūtinai turi pareigų toje institucijoje
- ✨ **Administruojamos institucijos matomos skydelyje** — jos rodomos tarp savų institucijų su atskiru ženkleliu, kad būtų aišku, jog tai ne narystė
- ✨ **Pakeitus administratorius, atviros užduotys perskirstomos iš karto** — nereikia laukti, kol kas nors bus paleista iš naujo

## v1.28 — Veiksmų langas (2026-08-29) {#v1-28}

- ⭐ **Vienas langas dažniausiems veiksmams** — šoninėje juostoje ir pradiniame puslapyje atsirado „Greiti veiksmai“ mygtukas. Jis paklausia, kaip šiandien veiki (studentų atstovas, VU SA narys ar koordinatorius), o tada — ką nori padaryti. Rodomi tik tie veiksmai, kuriuos iš tikrųjų gali atlikti
- ⭐ **Pranešti apie posėdį per kelis paspaudimus** — institucija, posėdžio būdas, data ir laikas klausiami po vieną, dideliais mygtukais. Darbotvarkę galima surašyti iš karto arba praleisti. Telefone langas užima visą ekraną, kompiuteryje — atsidaro kaip įprastas langas
- ⭐ **Pranešti, kad posėdžių kurį laiką nebus** — anksčiau tai buvo pasiekiama tik iš ViSAK laiko juostos; dabar tą patį galima padaryti iš bet kurio puslapio
- ⭐ **Papildyti jau įvykusį posėdį** — sąraše pirmiausia rodomi posėdžiai, kuriems labiausiai trūksta informacijos, ir parašoma, ko trūksta: darbotvarkės ar sprendimų
- ✨ **Institucijų sąraše matyti, kuriai labiausiai reikia dėmesio** — prie kiekvienos parašoma, prieš kiek laiko vyko paskutinis posėdis arba iki kada galioja pranešimas apie posėdžių nebuvimą
- ✨ **Susitikimo kūrimas visur atrodo vienodai** — senasis kūrimo langas pakeistas nauju visose vietose: šoninėje juostoje, pradiniame puslapyje, užduotyse, ViSAK, institucijos ir paieškos puslapiuose
- ⭐ **Kalendoriuje skelbiami tik VU SA dariniai** — posėdį paskelbti kalendoriuje siūloma tik VU SA dariniams; organuose, į kuriuos VU SA tik deleguoja atstovus, tokios parinkties nebėra nei posėdžio kūrime, nei posėdžio puslapyje
- ✨ **Šoninės juostos „Greiti veiksmai“ pakeisti vienu mygtuku** — vietoj sąrašo dabar visada matomas vienas mygtukas, atidarantis veiksmų langą. Kartu dingo ir nustatymas, kuriuos greitus veiksmus rodyti — komandų paletėje (Ctrl+K) matomi visi, kuriuos gali atlikti
- ✨ **Peržiūroje paspaudus „Keisti“ grįžtama atgal į peržiūrą** — pakeitus vieną atsakymą nebereikia iš naujo pereiti visų likusių žingsnių
- ⭐ **Posėdžio laikas siūlomas pagal šios institucijos istoriją** — siūlomos dvi artimiausios datos tos pačios savaitės dienos, kuria ši institucija posėdžiaudavo, ir tuo pačiu laiku. Jei posėdžių dar nebuvo, nieko nespėliojama — iškart atveriamas kalendorius
- ⭐ **Atsirado laiko pasirinkimas** — pasirinkus kitą datą, atskirame lange klausiama valandos: pirmiausia siūlomas įprastas šios institucijos laikas, tada dažniausios valandos arba tikslus laikas
- ✨ **Darbotvarkės klausimai rašomi paprastu sąrašu** — po vieną eilutę, „Enter“ atidaro kitą, tuščios eilutės neįrašomos. Anksčiau čia buvo pilnas posėdžio puslapio redaktorius, kuris telefone netilpo
- ✨ **Matyti, kurį veiksmą atlieki** — lango viršuje rodoma to veiksmo piktograma šalia žingsnių
- ✨ **Aiškesnis institucijų sąrašas** — kiekviena būsena (vėluoja, artėja, pažymėta, kad posėdžio nebus, suplanuotas posėdis) turi savo piktogramą ir spalvą
- 🔧 **Datos rodomos lietuviškai** — veiksmų lange datos buvo formuojamos pagal naršyklės, o ne programos kalbą
- 🔧 **Posėdžio laikas išsaugomas teisingai** — kuriant posėdį per veiksmų langą laikas buvo siunčiamas pasaulio (UTC) laiku, tad išsaugotas posėdis nukrypdavo keliomis valandomis
- 🔧 **Telefono apatinio meniu „+“ mygtukas veikia** — anksčiau jis vedė į neegzistuojantį puslapį
- ⭐ **Darbotvarkę galima įklijuoti iš karto** — veiksmų lange atsirado trečias pasirinkimas: sukūrus posėdį iš karto atsidaro posėdžio puslapio langas, kuriame visą klausimų sąrašą galima įklijuoti vienu kartu
- ✨ **Institucijų sąraše pirmiausia rodomas artimiausias posėdis** — jei institucija turi ir suplanuotą posėdį, ir pranešimą apie posėdžių nebuvimą, rodomi abu
- ✨ **Kelių darbotvarkės punktų langas pasiekiamas ir tuščioje darbotvarkėje** — anksčiau jį buvo galima atidaryti tik tada, kai jau buvo bent vienas punktas
- ✨ **Platesnis darbotvarkės klausimų langas** — įklijuotas posėdžio klausimų sąrašas nebespaudžiamas į siaurą stulpelį
- ✨ **Aiškesni posėdžio puslapio veiksmai** — „Redaguoti posėdį“ mygtukas dabar su užrašu, o „Pridėti instituciją“ perkelta į veiksmų meniu
- 🔧 **Matomas pirmos darbotvarkės eilutės pažymėjimas** — rašant klausimus veiksmų lange pirmos eilutės rėmelis buvo nukerpamas
- 🔧 **Elektroniniam posėdžiui nebesiūlomas laikas** — pasirinkus balsavimą el. paštu siūlomos tik dienos, be valandos, o data išsaugoma kaip 23:59 terminas; laikas sutvarkomas ir tuomet, kai tipas pakeičiamas jau pasirinkus valandą, o peržiūroje pakeitus tipą iš elektroninio į kitą paklausiama tikrosios valandos
- 🔧 **Darbotvarkės klausimus galima įklijuoti ir posėdžio puslapyje** — „Įkelti iš teksto“ anksčiau veikė tik kuriant posėdį; dabar pasiekiama ir pridedant klausimus, o įklijuoti klausimai pridedami prie jau surašytų, o ne juos pakeičia
- 🔧 **„Tvarkyti pareigybių laikotarpius“ veda į laiko juostą** — veiksmų lange ši parinktis vedė į kadencijų nustatymus ir buvo rodoma tik nustatymų valdytojams; dabar atveria pareigybių laikotarpių tvarkyklę ir rodoma visiems, kurie gali matyti pareigybes

## v1.27 — Kadencijos, laikotarpių tvarkyklė ir posėdžių skelbimas (2026-08-28) {#v1-27}

### Pareigybių laikotarpių laiko juosta

- ⭐ **Pareigybių laikotarpiai vienoje laiko juostoje** — visos institucijos pareigybės matomos kaip juostos; datas galima tempti pele, o apačioje esantis skydelis rodo pažymėtą įrašą, siūlomus taisymus ir neišsaugotus pakeitimus. Pasiekiama iš „Žmonės“ srities, pareigybės, nario ir institucijos puslapių
- ⭐ **Laikotarpių tvarkyklė pasiekiama ir iš šoninės juostos** — atsirado tarp greitų veiksmų; atsidarius iš karto parenkama institucija, kurioje einate pareigas
- ⭐ **Siūlomi taisymai rodo, ką konkrečiai pakeis** — persidengiančios datos, neterminuoti laikotarpiai po kadencijos pabaigos, nuo kadencijos nutolusios datos ir neužimtos vietos surašomi kartu su naujomis datomis. Kelis taisymus galima pažymėti ir pritaikyti iš karto, prieš tai peržiūrėjus visą sąrašą
- ⭐ **Kadencijos** — bendros kadencijų datos nustatomos „Nustatymuose“, o institucijos išimtys – pačios institucijos redagavimo lange. Kadencijos pavadinimas sudaromas iš datų, atskirai jo rašyti nereikia
- ⭐ **Kelių laikotarpių žymėjimas ir sujungimas** — varnelės prie kiekvieno įrašo ir prie visos pareigybės. Pažymėjus kelis, galima vienu kartu nustatyti pradžios ar pabaigos datą; pažymėjus du to paties žmogaus tos pačios pareigybės laikotarpius – juos sujungti į vieną
- ✨ **Matyti, kada įrašas turi papildomos informacijos** — atskiras el. paštas, studijų programa, nuotrauka ar aprašymas pažymiami piktograma su paaiškinimu (įkelta nuotrauka ir parodoma), o prieš sujungiant įspėjama, kad dalis šios informacijos bus prarasta
- ✨ **Aiškesnė laiko juosta** — dabartiniai ir pasibaigę laikotarpiai skiriasi spalva, gretimos kadencijos – atspalviu, vardai yra nuorodos, o mastelis įsimenamas kitam kartui. Įrašus galima filtruoti pagal kadenciją — įrašas priskiriamas kiekvienai kadencijai, kurią apima, o pasirinktoji diagramoje paryškėja — ir pagal padalinį; pasibaigusius galima paslėpti, o pareigybes suskleisti, ir suskleista pareigybė rodo, kiek įrašų slepia bei bendrą jų laiko juostą
- ✨ **Matomos studijų programos** — vietoj vien piktogramos programos pavadinimas rodomas tekstu, o įrašus galima surikiuoti pagal jas
- ⭐ **Matoma laikotarpio trukmė** — prie kiekvieno įrašo rodoma, kiek jis truko (pvz. „2 m. 4 mėn.“); trumpiems laikotarpiams rodomi mėnesiai ar dienos
- ✨ **Institucijos pasirinkimas aiškesnis** — mygtukas rodo dabartinės institucijos pavadinimą, o meniu pirmiausia siūlo jūsų institucijas. Institucijos pavadinimas virš diagramos dabar yra nuoroda
- ⭐ **Puslapio vadovas** — pirmą kartą apsilankius parodoma, kaip skaityti ir keisti juostas; vėliau vadovą galima paleisti pagalbos mygtuku
- ✨ **Laikotarpį galima pašalinti iš pačios laiko juostos** — anksčiau tam reikėdavo atskiro puslapio
- 🔧 **Sutvarkyti pareigybės ir nario puslapių mygtukai** — „Valdyti“, „Priskirti narį“ ir „Redaguoti“ nebuvo rodomi niekam, nors teisių pakako

### Kadencijos, valdysena ir posėdžių juosta

- ⭐ **Kadencijos ribą galima susieti su posėdžiu** — institucijos formoje kadencijos pradžią ar pabaigą galima nurodyti pasirenkant posėdį (pvz. ataskaitinę-rinkiminę konferenciją). Galima rinktis bet kurį prieinamą posėdį, taip pat ir kitos institucijos (pvz. padalinio konferenciją) — prie ribos tada parodoma, kurios institucijos tai posėdis. Data imama iš posėdžio ir pasikeičia kartu su juo
- ⭐ **Grupės ar pastabos laukas prie studijų programos** — kuratoriams didelėse programose galima nurodyti, pvz., „1 grupė“. Rodoma viešuose kontaktuose šalia studijų programos
- ✨ **Institucijos puslapyje ir formoje matyti valdysenos sritis** — žymė parodo, ar tai VU SA darinys, ar išorinis organas; formoje paaiškinama, kodėl dalis laukų nerodoma
- ⭐ **Posėdžių juostoje rodomi visi posėdžiai** — VU SA vidaus dariniai nebeslepiami. Norint juos paslėpti, rodymo nustatymuose atsirado „Slėpti VU SA darinius“ (išjungta pagal nutylėjimą); žalia piktograma prie posėdžio reiškia paskelbtą kalendoriuje įrašą, gintarinė — juodraštį

### Posėdžiai kalendoriuje ir dokumentuose

- ⭐ **Posėdį galima paskelbti kalendoriuje** — posėdžio lange, veiksmų meniu, atsiranda „Paskelbti kalendoriuje“. Sukuriamas juodraštinis renginys su posėdžio data ir padaliniu arba susiejamas jau įvestas įrašas iš savaitės aplink posėdį. Paskelbus renginį, posėdžio darbotvarkė ir dokumentai tampa matomi viešai — įskaitant dar neįvykusį posėdį; pats posėdžio puslapis ir jo paieškos įrašas lieka privatūs, juos atveria tik institucijos tipo nustatymas
- ⭐ **Darbotvarkė ir dokumentai renginio puslapyje** — su posėdžiu susietas kalendoriaus įrašas rodo darbotvarkę, punktų laikus ir su posėdžiu susietus nutarimus bei protokolus, o iš jo galima pereiti į posėdžio puslapį
- ✨ **Kompaktiška vieša darbotvarkė** — vietoj didelių kortelių darbotvarkė rodoma kaip vientisas sąrašas: būsenos taškas, pavadinimas, laikas ir sprendimas vienoje eilutėje, o aprašymas bei pilna balsavimo informacija išskleidžiami paspaudus
- ✨ **Laiko laukeliai visada 24 val. formatu** — anksčiau naršyklė, nustatyta angliškai, rodydavo AM/PM. Laikas pasirenkamas iš valandų ir minučių sąrašo, o mygtukas su ✕ jį išvalo
- ✨ **Renginio laiką valdo posėdis** — su posėdžiu susieto kalendoriaus įrašo datos laukai užrakinti ir su paaiškinimu nukreipia į posėdį, kad laikas nebūtų vedamas dviejose vietose
- 🔧 **Pagrindinį balsavimą galima pašalinti** — anksčiau paskutinio balsavimo ištrinti neleisdavo be paaiškinimo. Pašalinus pagrindinį, kitas tampa pagrindiniu automatiškai
- ✨ **VU SA darinių posėdžiuose nebereikalaujama studentų balso, naudos ir išsakytos pozicijos** — VU SA Parlamento, Tarybos ar Revizijos komisijos posėdyje pildomas tik sprendimas, nes atstovai ir yra pati organizacija; šios skiltys viešai nerodomos. Anksčiau tokie posėdžiai visada likdavo „nebaigti“
- 🔧 **VU SA darinių balsavimo būsena nebeįstringa „Neaptartas“** — darbotvarkės punkto, kai sprendimas įrašytas ne bendru sutarimu (pvz. „Priimta“), būseną dabar rodo punkto redagavimo puslapyje, punktų naršyklėje ir kalendoriaus renginio darbotvarkėje, o posėdžio lange — „aptartų balsavimų“ skaičius įskaito tokius sprendimus
- 🔧 **Nerodomos tarnybinės būsenos** — „Nepažymėtas“ viešai neberodomas niekada, o „Neaptartas“ nerodomas dar neįvykusio posėdžio darbotvarkėje, kur jis nieko nepasako
- ⭐ **Dokumentai susiejami su posėdžiu** — VU SA darinių posėdžiuose atsiranda kortelė „Dokumentai“: nutarimus ar protokolus galima surasti paieška arba iš karto įkelti iš SharePoint, nereikia jų pirma registruoti dokumentų skiltyje. Ieškant matomi ne tik paties darinio, bet ir viso to paties padalinio registruoti dokumentai — pvz. VU SA Parlamento nutarimai dažnai registruoti centrinės VU SA institucijos vardu, o ne paties Parlamento
- ✨ **Kalendoriaus įrašas pasako, kad skelbia posėdį** — redaguojant tokį renginį rodomas pranešimas su nuoroda į posėdį ir paaiškinimu, ką reiškia jo paskelbimas
- ⭐ **Darbotvarkės punkto laikas nurodomas jau kuriant posėdį** — vedlio darbotvarkės žingsnyje kiekvienam klausimui iškart galima nurodyti pradžios ir pabaigos laiką. Laikas rodomas ir viešoje darbotvarkėje, tad ilgesnio posėdžio eigą galima paskelbti iš anksto ir žmonės gali ateiti į juos dominančią dalį
- ⭐ **Institucijos tipui nustatoma valdysenos sritis** — VU SA darinys, VU organas, nacionalinis ar tarptautinis organas. Reikšmė paveldima iš tėvinio tipo, tad naujam poskyriui jos nurodyti nereikia
- 🔧 **Posėdžių sąrašo filtras pagal pildymo būseną vėl veikia** — filtras kreipdavosi į laukus, kurie buvo perkelti į balsavimus, ir grąžindavo klaidą

### Nuotoliniai renginiai ir vertikalios kortelės

- ⭐ **Nuotolinio renginio žymėjimas** — renginio formoje galima pažymėti „Nuotolinis renginys“. Tada vietoj adreso ir žemėlapio rodoma nuoroda prisijungti, o vietos žemėlapyje ieškoti nebebandoma
- ✨ **Vertikalios renginio kortelės** — renginių sąraše ir renginio puslapio skiltyje „Kiti renginiai“ dabar rodomos vertikalios kortelės su nuotrauka viršuje, o ne siauros eilutės
- ✨ **„Kiti renginiai“ perkelti po aprašymu** — renginio puslapyje šis blokas dabar rodomas pagrindiniame stulpelyje po darbotvarke ir nuotraukomis, o ne šoninėje juostoje
- ⭐ **Ankstesnio/kito to paties padalinio posėdžio nuorodos** — su posėdžiu susieto renginio puslapyje rodomos nuorodos į ankstesnį ir kitą to paties padalinio paskelbtą posėdžio renginį
- ⭐ **Susitikimo kūrimo vedlyje galima iškart paskelbti kalendoriuje** — peržiūros žingsnyje pažymėjus varnelę, kartu su posėdžiu sukuriamas juodraštinis kalendoriaus įrašas
- ✨ **Naujo punkto pradžios laikas pasiūlomas automatiškai** — jei ankstesnis darbotvarkės punktas turi pabaigos laiką, o naujas punktas savo dar neturi, pradžios laikas užpildomas juo (tik vieną kartą, be nuolatinio sinchronizavimo)

### Tvarkaraščio turinio blokas

- ⭐ **Naujas turinio blokas „Tvarkaraštis“** — puslapiuose, naujienose ir pagrindiniame puslapyje dabar galima įterpti tvarkaraščio kortelę su laikais ir pavadinimais. Eilutes galima įvesti rankiniu būdu arba importuoti iš posėdžio darbotvarkės

### Renginio puslapio vaizdo stiliai

- ⭐ **Renkami renginio puslapio vaizdo stiliai** — kiekvienam renginiui galima pasirinkti vieną iš trijų stilių: didelę kortelę su nuotrauka fone, kortelę su nuotrauka šalia teksto arba minimalų vaizdą be nuotraukos. Stilius parenkamas renginio formoje
- ✨ **Ramesnis numatytasis renginio vaizdas** — herojus tapo kompaktiškas, antraštė gerokai mažesnė, o veiksmai (registracija, dalinimasis) išlaiko tą patį tinkamą išdėstymą visuose ekranuose. Pašalintas „išsiplėtęs“ pilno ekrano vaizdas, trukdęs puslapio eigai
- 🔧 **Sutvarkyti renginio datos tekstai** — ilgos lietuviškos datos nebe „nusikerta“ datos parinkimo lauke, o „Visos dienos renginys“ perjungimas turi nuolat matomą eilutę

### Vienodės administravimo kortelės

- ✨ **Nustatymų kortelės vienodo stiliaus su administravimu** — nustatymų pradžios puslapis dabar rodo tokias pat korteles kaip administravimo puslapis: piktograma, pavadinimas ir aprašymas, su pele pažymint apsišviečiančiu rėmeliu
- ✨ **Pašalinta išskirtinė „Problema“ kortelė** — administravimo puslapyje nebebus rodomas „Naujausi įrankiai“ skyrius su „Nauja“ žyme pažymėta problemos kortele; problemos lieka pasiekiamos „Atstovavimo“ skyriuje
- 🔧 **Atstovavimo nustatymų puslapis vėl išverstas** — dėl klaidos vertimų rinkmenose buvo matomos neišverstos rakto eilutės; dabar rodomi lietuviški ir angliški tekstai

## v1.26 — Paveikslėliai turinio redaktoriuje, failų tvarkyklė ir paieškos filtrai (2026-08-28) {#v1-26}

### Paveikslėliai turinio redaktoriuje

- 🔧 **Paveikslėlį vėl galima įkelti į turinio redaktorių** — pasirinkus nuotrauką ji nebuvo įterpiama (redaktorius nutrūkdavo su klaida), tad turinį su paveikslėliais buvo galima kurti tik nutempus failą į redaktorių
- ⭐ **Paveikslėlio dydį galima nusitempti pele** — pažymėjus paveikslėlį, jo dešiniajame krašte atsiranda rankenėlė; tempiant rodomas plotis pikseliais. Greitiems dydžiams lieka ir mygtukas su parinktimis (300 / 500 / 800 px, per visą plotį)
- 🔧 **Pasirinktas plotis ir lygiuotė matomi ir viešame puslapyje** — anksčiau nepriklausomai nuo pasirinkimo paveikslėlis buvo ištempiamas per visą plotį, o lygiavimas į kairę ar dešinę dingdavo
- ✨ **Trumpesnė paveikslėlio įkėlimo forma** — ilgas mėlynas paaiškinimas apie alternatyvų tekstą suskleistas po nuoroda „Kodėl tai privaloma?“, o šalia įvesties matomas simbolių skaitiklis (kaip ir redagavimo lange)
- ✨ **Įterpiant paveikslėlį galima pažymėti „dekoratyvinis“** — tokiu atveju alternatyvaus teksto reikalauti nebereikia (kaip ir redagavimo lange)
- ✨ **Paveikslėlio valdikliai atsiranda šalia paties paveikslėlio** — pažymėjus jį iškart virš jo pasirodo lygiuotės, dydžio, alt teksto ir pašalinimo mygtukai; anksčiau jie buvo toli viršuje, įrankių juostoje
- 🔧 **Pažymėjus paveikslėlį nebesiūloma paryškinti ar pabraukti** — vietoj teksto formatavimo burbulo dabar rodomas paveikslėlio meniu
- 🔧 **Nebesidubliuoja lygiuotės mygtukai** — pažymėjus paveikslėlį įrankių juostoje matėsi dvi lygiuotės eilutės, o veikė tik viena

### Failų tvarkyklė

- 🔧 **Failų tvarkyklė nebenulūžta atidarius `/mano/files`** — puslapis nutrūkdavo su klaida („allowedTypes.extensions is undefined“), o įkėlimo laukas užstrigdavo ties „Kraunama...“. Kartu vėl veikia leidžiamų formatų sąrašas, 50 MB ribos tikrinimas naršyklėje ir failo tipo filtras pasirinkimo lange
- 🔧 **Įkėlimo mygtukas nebesisuka be galo** — įkeliant failą per turinio redaktoriaus paveikslėlių langą failas patekdavo į serverį, bet mygtukas suktųsi amžinai, nes įkėlimą nutraukdavo kitas puslapio veiksmas (pvz. formos automatinis išsaugojimas). Dabar įkėlimas nepriklauso nuo puslapio navigacijos ir visada pasibaigia
- ✨ **Aplankai nebeužstoja failų** — anksčiau šakniniame kataloge esantys ~50 aplankų užimdavo visą pirmą puslapį, o failai prasidėdavo tik nuo antro. Dabar aplankai turi atskirą, suskleidžiamą juostą su savo filtru, o failų tinklelis prasideda iš karto
- ⭐ **Paieška visuose aplankuose** — pažymėjus „Visuose aplankuose“ ieškoma ir poaplankiuose, o prie kiekvieno rezultato rodoma, kuriame aplanke failas guli. Anksčiau rasti failą buvo įmanoma tik žinant, kuriame iš ~50 aplankų jis yra
- ✨ **Aplankai ir failai rikiuojami pagal pavadinimą** — anksčiau eilė priklausė nuo failų sistemos ir atrodė atsitiktinė
- ✨ **Aplankas atidaromas vienu paspaudimu** — anksčiau reikėjo dvigubo paspaudimo, o vienas paspaudimas nedarė nieko
- 🔧 **Į redaktorių nutempti ne paveikslėlių failai vėl atsisiunčiami** — jie buvo įrašomi ne į tą vietą, tad įterpta nuoroda grąžindavo klaidą 404
- ⭐ **Failų tvarkyklėje – paveikslėlio peržiūra užvedus pelę** — tinklelyje užvedus ant nuotraukos parodoma didesnė jos versija, tad nebereikia spėlioti pagal mažą kvadratėlį
- ✨ **Failų tinklelis kraunasi greičiau** — anksčiau kiekvienam langeliui buvo atsiunčiama viso dydžio nuotrauka (aplankas su 50 nuotraukų reiškė šimtus megabaitų); dabar serveris paruošia ir įsimena sumažintas kopijas, o už ekrano ribų esančios nuotraukos atsiunčiamos tik prislinkus
- ✨ **Failų tvarkyklė kalba angliškai, kai svetainė angliška** — mygtukai ir pranešimai buvo įrašyti tiesiai į kodą lietuviškai (vietomis angliškai)

### Paieškos filtrai siauresniuose ekranuose

- ✨ **Paieškos filtrai suskleidžiami visuose ekrano pločiuose** — 1024–1280 px pločio ekranuose dokumentų, posėdžių, kontaktų ir bendrosios paieškos puslapiuose filtrai anksčiau būdavo išskleisti virš rezultatų ir jų nebuvo galima suskleisti; dabar jie slepiasi po filtrų mygtuku, kaip ir telefonuose
- 🔧 **Sutvarkyta filtrų rodyklės lygiuotė** — filtro sekcijų atvėrimo rodyklė buvo pasislinkusi į viršų ir ne centre su etikete, ypač kai sekcija turi aprašymą; dabar rodyklė visuomet vertikaliai centruota

## v1.25 — Hero karuselė, angliškos nuorodos ir teisių sutvarkymas (2026-08-20) {#v1-25}

- ⭐ **Naujas hero karuselės blokas** — puslapio turinį galima pradėti besikeičiančiomis didelėmis nuotraukomis su antrašte, paantrašte, aprašymu ir mygtukais. Karuselė pritaikyta klaviatūrai ir ekrano skaitytuvams, o automatinis slinkimas stoja užvedus pele ir neveikia, kai įrenginys prašo mažiau judesio
- ⭐ **Angliška svetainės dalis turi angliškas nuorodas** — dokumentai, paieška, kontaktai, studentų atstovai ir posėdžiai pasiekiami adresais `/en/documents`, `/en/search`, `/en/contacts`, `/en/meetings` ir pan. Senos nuorodos automatiškai nukreipiamos, tad išsaugotos nuorodos veikia toliau
- ⭐ **Nauji svetainės nustatymai** — privatumo politikos puslapį galima nurodyti atskirai lietuvių ir anglų kalbai; slapukų juostos nuoroda seka naudotojo kalbą
- ✨ **Administravimo ir formų klaidų pranešimai išversti** — anksčiau dalis jų buvo rodomi tik viena kalba arba kaip techninis raktas (pvz. „messages.meeting.updated“)
- ✨ **Pašalinti „Mokymai“ ir „Narystės“** — šios sritys nuo 2024 m. pabaigos liko nebaigtos ir be duomenų, todėl išimtos iš administravimo meniu

## v1.24 — Ex-officio pareigos vėl priskiriamos (2026-08-11) {#v1-24}

- 🔧 **Ex-officio pareigos vėl priskiriamos**

## v1.23 — Patogesnės administravimo lentelės (2026-08-11) {#v1-23}

- ✨ **Mažiau paaiškinimų burbulų lentelėse** 
- ✨ **Peržiūros ir redagavimo mygtukai yra nuorodos** 
- ✨ **Institucijų sąraše rodomi naujausi posėdžiai** — vietoj seniausių
- ✨ **Paieška veikia rašant** — atskiro „Ieškoti“ mygtuko nebeliko visuose sąrašuose
- ✨ **Filtrai suskleisti po vienu mygtuku** 

## v1.22 —  Administravimo ir juodraščių rodymo atnaujinimai (2026-08-10) {#v1-22}

- 🔧 Patikimesnis naudotojų administravimas
- ⭐ **Įspėjimas apie galimą dublikatą** — kuriant naują naudotoją (formoje ir pareigybių vedlyje) parodoma, jei panašus profilis jau egzistuoja — net jei jis priklauso kitam padaliniui. Vedlyje galima iš karto pasirinkti esamą profilį vietoj naujo kūrimo
- ✨ **Pareigybės pavadinimas giminizuojamas aiškiau**
- 🔧 **Juodraščiai ir suplanuotos naujienos/puslapiai vėl matomi administravimo paieškoje** 
- 🔧 Suplanuotos naujienos ir puslapiai pasirodo viešoje paieškoje laiku

## v1.21 — Navigacijos redagavimas, ViSAK pokyčių grafikas ir RSS srautas (2026-08-04) {#v1-21}

### ViSAK

- ⭐ **Institucijų būklės pokyčiai laikui bėgant**
- ✨ **Pasirenkamas laikotarpis** — grafiką galima peržiūrėti 30, 90 arba 180 dienų laikotarpiui; užvedus pelę matoma tos dienos institucijų būklių suvestinė

### Administravimas

- ⭐ **Navigacijos redagavimas ir peržiūra atskirti** 
- ✨ **Tvarka išsaugoma automatiškai** 
- ⭐ **Kalbos perjungimas nepriklauso nuo administravimo kalbos** 
- ✨ **Sutvarkyta navigacijos elemento redagavimo forma** 
- ⭐ **Greitas nuorodos pasirinkimas** 
- 🔧 **Rodymo viešai valdiklis pradėjo veikti**

### Vieša navigacija

- ⭐ **Daugiau valdymo paveikslėlio fonui** — patamsinimo stiprumas, suliejimas, fokuso taškas ir gradiento kryptis dabar konfigruojami kiekvienam elementui
- ✨ **Geresnė mobili navigacija** 
- 🔧 **Pataisytas nuorodų atsivėrimas naujame lange** — kai kur šis nustatymas anksčiau tyliai neveikdavo

### Naujienų RSS srautas

- ⭐ **Pilnas naujienos turinys sraute** — RSS naujienų srautas dabar atneša visą straipsnio turinį (su paveikslėliais), o ne tik trumpą ištrauką, kad skaitytuvėse matytųsi visas tekstas
- ✨ **Paveikslėliai matomi skaitytuvėse** — viršelio nuotrauka dabar perduodama per `<enclosure>` ir Media RSS žymes, o visi nuorodai ir paveikslėliai sraute paverčiami absoliučiais adresais
- 🔧 **Sutvarkyta viršelio nuotraukos nuoroda** — anksčiau dėl klaidingo adreso konstravimo viršelio paveikslėlis sraute ne visai rodydavosi
- ✨ **Daugiau metaduomenų** — naujienų žymos dabar atvaizduojamos kaip `<category>`, pridėtas `<guid>`, autoriaus el. paštas ir nuorodos į kitos kalbos versiją

## v1.20 — Patobulinta pakeitimų istorija (2026-08-02) {#v1-20}

- ⭐ **Nauja pakeitimų istorijos skiltis** - posėdžių, institucijų, pareigų, problemų, rezervacijų, mokymų ir kito turinio puslapiuose dabar galima peržiūrėti visą pakeitimų istoriją, įskaitant susijusių įrašų pakeitimus (pvz., posėdžio istorijoje matomi ir jo darbotvarkės klausimų bei balsavimų pakeitimai)
- ✨ **Teksto pakeitimai rodomi kaip skirtumas** - naujienų, puslapių turinio blokų ir problemų aprašymo pakeitimai istorijoje dabar rodo, kurie žodžiai buvo pakeisti, o ne du identiškai atrodančius nutrauktus tekstus
- 🔧 **Kai kurie pakeitimai anksčiau nebuvo fiksuojami** - dabar visų palaikomų modelių pakeitimai patikimai įrašomi į istoriją
- ✨ **Aiškesnis pakeitimų atvaizdavimas** - datos, būsenos ir susiję įrašai (pvz., atsakingas asmuo) rodomi suprantamais pavadinimais, o ne neapdorotais duomenimis
- 🔧 **Pataisytas resursų valdymas ir redagavimas** - galima įkelti nuotraukas, redaguoti kitus laukelius, ištrinti resursus.
- 🔧 **Pabandyta pataisyti su dokumentų įkėlimu susietas problemas**

## v1.19 — Greitesnė ViSAK padalinių laiko juosta ir nauji turinio blokai (2026-07-28) {#v1-19}

### ViSAK padalinių laiko juosta

- ✨ **Laiko juosta kraunasi kelis kartus greičiau** — posėdžiai užkraunami tik matomam laikotarpiui ir slenkant

### Nauji turinio blokai ir puslapio nustatymai

- ⭐ **Trys nauji turinio blokai** — nuorodų sąrašas (naujienos, puslapiai arba rankiniu būdu įvestos nuorodos), renginių sąrašas (filtruojamas, grupuojamas pagal padalinį) ir asmens citata su nuotrauka bei pareigomis
- ⭐ **„Tarpas“ blokas** — leidžia valdyti vertikalų atstumą tarp bet kurių dviejų blokų, kai įprastas tarpo dydis netinka; penki dydžiai nuo labai mažo iki didžiulio
- ⭐ **„Sekcijos“ blokas** — sujungia po jo einančius blokus į vieną sekciją su bendra antrašte, fonu ir apvaliais kampais, iki kito sekcijos bloko
- ⭐ **Turinio lentelės (šoninio meniu) įjungimas ir išjungimas** puslapio nustatymuose
- ⭐ **Puslapio pavadinimo ir atnaujinimo laiko slėpimas** — kai antraštė jau pateikiama pačiame turinyje

### Patogesnis redagavimas

- ⭐ **Redagavimas ir peržiūra greta** — bet kuriam blokui galima atidaryti langą, kuriame redagavimas ir gyva peržiūra rodomi vienu metu; peržiūros plotis parenkamas iš bloko leistinų pločių ir išsisaugo
- ✨ **Naujas turinio blokų pasirinkimo langas** — blokai suskirstyti kategorijomis, galima ieškoti pagal pavadinimą, o pasirinkus tipą iš karto matoma jo gyva peržiūra; antraštės sekcijai rodomos visų variantų peržiūros
- ✨ **Blokus galima suskleisti ir išskleisti** — dideli blokai nebeužima viso ekrano, o vilkti juos tvarkant daug patogiau
- ✨ **Kiekvienam blokui galima pasirinkti plotį** — teksto, turinio, platus arba per visą pločio, priklausomai nuo bloko tipo
- ✨ **Patogesnis nuotraukų tinklelio ir galerijos redagavimas** — nuotraukos tvarkomos tiesiai tinklelyje, galima nustatyti fokuso tašką
- 🔧 **Nuotraukų pasirinkimo lange nebematyti PDF ir kitų netinkamų failų**

### Blokų išvaizda ir stiliai

- ✨ **Nauji antraštės sekcijos variantai** — be įprasto dviejų stulpelių atsirado centruotas, juostos ir panelės tipo, parenkami su schematiškomis peržiūromis; antraštės mygtukai gali turėti ikoną
- ⭐ **Antraštėms galima nustatyti dydį, spalvos akcentą, lygiuotę ir viršutinį tarpą** — nepriklausomai nuo H2/H3/H4 lygio
- ⭐ **Naujas taškinis „žymės“ elementas teksto redaktoriuje** — su fonu arba be jo, keturių spalvų, kaip narystės puslapio ženkleliai
- ✨ **Pavadinimas, paantraštė ir fonas galimi visuose sekcijų tipo blokuose** — akordeone, kortelių krūvoje, karuselėje, galerijoje, statistikoje ir turinio tinklelyje (įskaitant subtilų gradientą ir baltą foną)
- ✨ **Apvalūs kampai, fonas ir vidiniai tarpai — ir antraštės blokui**, įskaitant „juostos“ tipą
- ✨ **Perdaryta kortelės išvaizda** — be dekoratyvinės ikonos, spalva žymima subtilia juostele šone, o kortelės antraštė padidinta ir atitraukta nuo turinio
- ✨ **Tinklelio nuotraukoms galima nustatyti fokuso tašką, antvivo turinį ir dekoratyvius elementus**, o antvivo turinį — pritvirtinti prie nuotraukos kampo, kad nekyšotų už krašto
- ✨ **Nuorodų sąrašo „su nuotraukomis“ stiliuje nuotrauką galima pridėti ir rankiniu būdu įvestoms nuorodoms**
- ✨ **Nuorodų ir renginių sąrašų tinkleliai prisitaiko prie elementų skaičiaus** — esant 1 ar 2 elementams, tinklelis nebeištemptas per visą plotį
- ✨ **Turinio tinklelio stulpelių vertikali ir antraštės lygiuotė** — trumpesnis tekstas nebeištemptas per visą aukštį
- ✨ **Sumažinti numatytieji antraščių viršutiniai tarpai** — prireikus tarpą galima padidinti, sumažinti arba nuimti antraštės stiliaus parinkiklyje
- 🔧 **Antraščių dydžio, spalvos akcento ir viršutinio tarpo pakeitimai matomi redaguojant** — anksčiau jie buvo matomi tik peržiūros režime

### Dokumentų paieška

- 🔧 **Dokumentai, kurie iš tikrųjų yra nuorodos į kitas svetaines (pvz., veiklos ataskaitos), atsidaro tiesiai toje svetainėje** — anksčiau atsidarydavo tuščia „SharePoint“ peržiūra
- ✨ **Tokie įrašai pažymėti „Nuoroda“ ženkleliu**, mygtukas „Atsisiųsti“ jiems neberodomas, o kopijuojant nuorodą nukopijuojamas svetainės adresas

### Puslapio ir naujienos nuorodų valdymas

- ⭐ **Puslapių nuorodas dabar galima keisti ir po sukūrimo** — anksčiau nuoroda buvo sugeneruota vieną kartą ir nebepakeičiama

## v1.18 — Atnaujinti renginių ir stovyklų puslapiai (2026-07-27) {#v1-18}

- ⭐ **Renginio vietos žemėlapis** — jei renginiui nurodyta vieta, šalia jos rodomas žemėlapis su žyma; nuoroda į Google Maps išlieka
- ✨ **Pirmakursių stovyklų puslapis perdarytas** — stovyklos matomos iškart po trumpa įžanga, su datomis ir vietomis; jei padalinys organizuoja kelias stovyklas, matomos visos
- ✨ **Renginio laikas rodomas sklandžiai** — kelias dienas trunkantys renginiai užrašomi vienu intervalu (pvz., „2026 m. rugpjūčio 25–27 d.“), o visą dieną trunkantys neberodo vidurnakčio
- ✨ **Mažiau skubos ir pasikartojimų renginių puslapiuose** — panaikintos „Sekantis“, „Artėja“, „Netrukus“ žymos; likę tik faktai: „Vyksta dabar“ ir „Renginys įvyko“, o dalinimosi mygtukai nebedubliuojami
- 🔧 **Renginio aprašymas vėl rodomas tvarkingai** — antraštės, sąrašai ir pastraipos nebeliejasi į vieną tekstą
- 🔧 **Registracijos mygtukas veikia** — anksčiau nurodyta registracijos nuoroda renginio puslapyje nebuvo rodoma

## v1.17 — Ištrintų įrašų valdymas ir patogesni sąrašai (2026-07-27) {#v1-17}

- ⭐ **Ištrintus įrašus galima atkurti** — baneriai, kalendoriaus renginiai, greitosios nuorodos, navigacijos punktai, kategorijos, žymos, mokymai, studijų programos ir studijų rinkiniai nebeprarandami iškart
- ⭐ **Ištrynimas visam laikui** — atskiroje ištrintų įrašų skiltyje, tik patvirtinus įrašo pavadinimą; veiksmui reikia atskiros teisės, kurią rolėse galima suteikti nekeičiant įprastos trynimo teisės
- ⭐ **Narių ir studentų atstovų registracijos šoninėje juostoje** — abi formos pasiekiamos po „Svetainė“ punktu, o jas tvarkantys žmonės gali atidaryti formų sąrašą neturėdami formų redagavimo teisių
- ⭐ **Registracijų atsisiuntimas į Excel** — mygtukas vėl veikia, šalia matosi bendras registracijų skaičius ir paskutinės registracijos data
- ✨ **Perjungiklis tarp aktyvių ir ištrintų įrašų** — matosi, kurį vaizdą žiūri ir kiek įrašų ištrinta; lentelėje rodomas ir ištrynimo laikas
- ✨ **Veiksmai matomi iškart sąrašuose** — peržiūra, redagavimas ir atkūrimas nebeslepiami po „⋯“ mygtuku; ištrynimas lieka atskirame meniu, kad nebūtų paspaustas netyčia
- ✨ **Institucijų veiklos statusai ir priminimai** — aiškesnės žymos parodo, kada veikla atnaujinta ir kada artėja terminas pranešti; pranešimas apie neplanuojamą posėdį laikomas veiklos atnaujinimu
- ✨ **Padalinių būklės suvestinė ViSAK** — virš laiko juostos galima pasirinkti kelis padalinius, matyti jų institucijų būklės skaičius ir naudotis paieška bei puslapiavimu
- 🔧 **Negrįžtamas pareigybės ištrynimas nebemeta klaidos** — parodoma, kodėl pareigybės su narystės istorija ištrinti negalima; istorija išsaugoma
- 🔧 **Studentų atstovų registracijos atskirtos pagal padalinį** — koordinatoriams rodomos tik jų padalinio institucijoms pateiktos registracijos
- 🔧 **Formos laukelio aprašymas nebedingsta** — redaguojant formą su registracijomis aprašymai būdavo perrašomi laukelio tipu
- 🔧 **Veikia greiti veiksmai šoninėje juostoje** — „Naujas susitikimas“, „Nauja naujiena“ ir „Nauja rezervacija“ nebemeta klaidos
- 🔧 **Tikslesnis tuščias institucijos kontaktų vaizdas** — institucijoms be viešų kontaktų neberodomas klaidinantis pranešimas apie studentų atstovus
- 🔧 **Ištrynimas nebepraranda susijusių duomenų** — atkūrus posėdį grįžta ir jo darbotvarkė su balsavimais, atkūrus žymą — jos naujienos, o ištrynus navigacijos punktą jo vidiniai punktai nebepasimeta
- 🔧 **Ištrinti įrašai nebeblokuoja adresų, el. paštų ir turinio kalbos porų** — turinio kalbos pora atsilaisvina automatiškai, o dėl adreso ar el. pašto parodoma, kad juos naudoja ištrintas įrašas ir ką su tuo daryti
- 🔧 **Negrįžtamas ištrynimas nebemeta klaidų** — kai įrašo ištrinti negalima dėl susietų duomenų, veiksmas iš karto neaktyvus ir parodoma, kas jį saugo
- ✨ **Trynimo langai paaiškina pasekmes** — prie patvirtinimo parodoma, kas su įrašu nutiks, pvz. kad pareigybės narystės istorija išsaugoma
- 🔧 **Failų naudojimo tikrinimas mato ištrintus įrašus** — failas, naudojamas tik ištrintame įraše, nebežymimas kaip saugus trinti

## v1.16 — Nauja svetainės lankomumo analitika (2026-07-26) {#v1-16}

- ⭐ **Lankomumo statistika „Svetainės“ skiltyje** — pasirinkto padalinio puslapių peržiūros, lankytojai, peržiūrų grafikas ir populiariausi puslapiai matomi tiesiai „Mano“ sistemoje. Rodomi tik to padalinio svetainės duomenys, todėl atskiros analitikos paskyros nebereikia
- ⭐ **Peržiūros konkrečios naujienos ar puslapio redagavimo lange** — atidarius naujieną arba puslapį matosi, kiek kartų jis peržiūrėtas ir kiek turėjo lankytojų. Jei įrašas paskelbtas anksčiau, nei pradėti kaupti duomenys, apie tai įspėjama
- ✨ **Kaupiami svetainės paieškos žodžiai** — įrašoma, ko lankytojai ieško vusa.lt paieškoje ir kiek rezultatų rado. Taip matysime, kokio turinio žmonės ieško, bet neranda. Įrašomas tik paieškos tekstas ir rezultatų skaičius, be jokių lankytojo duomenų

## v1.15 — Atostogos neskaičiuojamos į susitikimų laikotarpius (2026-07-14) {#v1-15}

- ✨ **Atostogos neįskaičiuojamos į posėdžių periodiškumą** — vasaros, žiemos, sausio pabaigos ir Velykų atostogų dienos nebeskaičiuojamos vertinant, kiek laiko institucija neposėdžiavo. Todėl per atostogas nebekuriamos užduotys ir nebesiunčiami priminimai, o užduočių terminai nebenukrenta į atostogų laikotarpį
- 🔧 **Veikia „Institucijos, kurioms reikia dėmesio“** — pradiniame lange šis blokas dėl skaičiavimo klaidos niekada nerodydavo vėluojančių institucijų

## v1.14 — Paieška, diskusijos ir rezervacijos (2026-07-13) {#v1-14}

- ⭐ **Rezervacijų valdymo pultas** - visos administruojamos rezervacijos vienoje vietoje. Patvirtinti, išduoti ar pažymėti grąžintus daiktus galima tiesiai iš sąrašo, net kelias rezervacijas iš karto, o pasenusias rezervacijas galima uždaryti vienu veiksmu.
- ⭐ **Administravimo paieškos puslapis** - paieška turi atskirą puslapį, kuriame rodomos rastų įrašų peržiūros. Dalyje redagavimo formų atsirado naujas, patogesnis įrašų pasirinkimas su ta pačia paieška.
- ⭐ **Išteklių rezervacijų istorija peržiūrose** - ištekliaus paieškos peržiūroje ir išteklių parinkimo dialoge kuriant rezervaciją matomos aktyvios rezervacijos (su laikais ir būsena) bei iki trys ankstesnės rezervacijos.
- ⭐ **Diskusijos vietoje komentarų** - komentarai pertvarkyti į diskusijų skydelį, kurį dabar turi ne tik posėdžiai, bet ir darbotvarkės punktai bei institucijos. Diskusijose galima paskelbti ir apklausą
- ⭐ **Atskiras viešos paieškos puslapis** - vietoje modalinio lango paieška atidaroma savo puslapyje
- ⭐ **Mixcloud įrašai** - turinyje galima įterpti Mixcloud.
- ✨ **Padalinio filtras rezervacijos lange** - kai rezervacijoje yra kelių padalinių išteklių, juos galima filtruoti pagal padalinį
- ✨ **Patobulinta institucijų grafa** - taip pat, paieškos peržiūrose matomos ir su įrašu susijusios institucijos bei pareigybės
- ✨ **Įspėjimas apie savo prieigos praradimą** - keičiant pareigybės narius, sistema perspėja, jei tokiu būdu prarastumėte prieigą prie sistemos
- ✨ **Protingesnis numatytasis rikiavimas** sąrašų puslapiuose
- ✨ **Patikslinti vertimai** visoje platformoje
- ✨ **Aiškesnis slapukų sutikimo langas**
- ✨ **Lankstesnis išteklių užimtumas** - aktyvios, bet jau pasibaigusios rezervacijos nebeblokuoja laisvų vienetų, tačiau apie jas įspėjama, kad būtų galima užbaigti
- ✨ **Daug išvaizdos pataisymų** - sutvarkytos įvairios kortelės ir puslapiai
- 🔧 **Pataisytos formos** - tarp jų resurso formos „ar rezervuojamas" pasirinkimas
- 🔧 **Pataisytas resursų kategorijų sąrašo atidarymas**
- 🔧 **Puslapiavimas lentelėse** - puslapiavimo mygtukai nebuvo rodomi lentelėse, kurios duomenis filtruoja naršyklėje
- 🔧 **Vėl siunčiami pranešimų el. laiškai** — nuo balandžio pradžios pranešimų santraukos į el. paštą nebebuvo išsiunčiamos. Dabar jos veikia, o senos, nebeaktualios santraukos nebus siunčiamos. Nesėkmingo išsiuntimo atveju pranešimai nebedingsta — jie lieka eilėje kitam bandymui
- ⭐ **Bandomasis el. laiškas** — nustatymuose, prie pranešimų el. pašto adresų, atsirado mygtukas išsiųsti bandomąjį laišką ir iškart pamatyti, ar jis pasiekia pašto dėžutę
- ✨ **Ilgesni balsavimų pavadinimai** — balsavimo pavadinimas dabar gali būti iki 200 simbolių (buvo 125)
- 🔧 **Patikimesnis navigacijos ir naujienų išsaugojimas** — sutvarkyta validacija, kad išsaugant navigacijos skirtuką be pavadinimo arba kuriant naujieną be priskirto padalinio sistema mestų aiškią klaidą, o ne SQL išimtį

## v1.13 — Problemų registro patobulinimai (2026-06-12) {#v1-13}

- ⭐ **Padalinio filtras problemų sąraše** — problemas dabar galima filtruoti pagal padalinį, ne tik pagal institucijas
- ⭐ **Greitieji filtrai „Mano padalinio problemos" ir „Mano sukurtos problemos"** — vienu paspaudimu rodomos tik savo padalinio arba savo užregistruotos problemos
- ✨ **Paieška filtrų sąrašuose** — padalinių ir institucijų filtruose atsirado paieškos laukelis, padedantis greitai rasti reikiamą įrašą
- ✨ **Paaiškinimai problemos formoje** — prie kiekvieno formos lauko atsirado informacijos ženkliukas su paaiškinimu, ką reikia įrašyti, o kategorijų sąraše rodomi jų aprašymai
- 🔧 **Ilgų problemų pavadinimų atvaizdavimas** — ilgi pavadinimai sąraše keliami į kitą eilutę ir nebeužlenda ant kitų lentelės elementų

## v1.12 — Susitikimų sąsajos patobulinimas (2026-06-01) {#v1-12}

- ✨ Atnaujintas posėdžio puslapio vaizdas
- ⭐ **Atskiras darbotvarkės punkto puslapis** — paspaudus darbotvarkės punktą atidaromas atskiras jo redagavimo puslapis su balsavimais ir sprendimais
- ⭐ **Bendros atstovų pastabos realiu laiku** — darbotvarkės punkto puslapyje atsirado vidinės „Atstovų pastabos" sritis, kurioje keli atstovai gali rašyti vienu metu; pakeitimai ir kitų žmonių žymekliai matomi iškart, o pastabas galima atverti didesniame lange.
- ⭐ **Navigacija tarp darbotvarkės punktų** — punkto puslapio viršuje atsirado „ankstesnis / kitas" mygtukai ir „Punktas N / iš viso" sąrašas, leidžiantis greitai peršokti tarp viso posėdžio punktų ir matyti jų būsenas
- ✨ Pasirenkama, ar darbotvarkės punktas išsaugomas automatiškai, ar tik paspaudus „Išsaugoti"
- ✨ **Aiškesnis punkto redagavimas** — klausimo tipas ir balsavimai pertvarkyti, balsavimai sunumeruoti su „Pagrindinio" žyma, o laukai pažymėti, ar matomi viešai

### Kita 

- ✨ Sumažintas bazinis šrifto dydis, kad ekrane būtų matoma daugiau informacijos

## v1.11 — Pritaikoma šoninė juosta ir neseniai aplankyti puslapiai (2026-06-01) {#v1-11}

### Šoninė juosta

- ⭐ **Pritaikoma šoninė juosta** — paskyros meniu atsirado pasirinkimas „Pritaikyti šoninę juostą", kur galima paslėpti ar rodyti pasirinktas sekcijas (greitus veiksmus, sekamas institucijas, START FM, pagalbą, neseniai aplankytus) ir pakeisti jų tvarką. Logotipas, paskyros meniu ir pagrindinė navigacija visada matomi. Pasirinkimai išsaugomi paskyroje ir veikia visose naršyklėse
- ⭐ **Neseniai aplankyti puslapiai** — šoninėje juostoje ir komandų lange (Cmd/Ctrl+K, prieš paiešką) rodomi paskutiniai lankyti administravimo puslapiai
- ⭐ **Prisegti puslapiai** — administravimo puslapį gali prisegti (žvaigždute ties neseniai aplankytais arba komandų lange), kad jis liktų atskiroje „Prisegti" sekcijoje šoninėje juostoje; pasirinkimai išsaugomi paskyroje
- ✨ **Kompaktiškas vaizdas** — „Pritaikyti šoninę juostą" lange galima įjungti kompaktišką režimą, kuris sumažina tarpus šoninėje juostoje

### Kita

- ⭐ **Dokumentų peržiūra naršyklėje** — dokumentų nuorodose pridėtas `?web=1` parametras, kad jie atsidarytų tiesiai naršyklėje, o ne būtų bandoma atidaryti programėlėje
- 🔧 **Mobilios navigacijos uždarymas** — paspaudus nuorodą mobilioje versijoje, šoninis meniu automatiškai užsidaro
- 🔧 Failų trynimo pataisymas SharePoint aplinkoje
- 🔧 **Pataisyta filtrų išvalymo funkcija dokumentų paieškoje** — „Išvalyti filtrus" mygtukas dabar tinkamai išvalo visus filtrus ir parodo visus dokumentus
- ✨ **Supaprastinti datos intervalo filtrai** — pašalinti besidubliuojantys „3 mėn." ir „6 mėn." pasirinkimai; paliktas numatytasis „Neseniai" (3 mėn.), „1 metai", „Metų intervalas" ir „Pasirinkti datą"
- ✨ **Matomas paieškos mygtukas administravimo lentelėse** — visose turinio lentelėse šalia paieškos laukelio pridėtas mygtukas „Ieškoti"; taip pat pagerintas filtrų išdėstymas

> 🔗 [GitHub PR #569](https://github.com/vu-sa/vusa.lt/pull/569)

## v1.10 — Ex-officio pareigos ir atstovai iš kitų padalinių (2026-05-12) {#v1-10}

- ⭐ **Ex-officio pareigos** — pareigybės redagavimo lange galima nurodyti pareigas, kurios automatiškai suteikiamos kartu su šia (pvz. pirmininkavimas suteikia vietą dekanate). Susietų pareigybių datos sinchronizuojamos su pirmine, o pasibaigus pirminei — pažymima pabaiga
- ⭐ **Atstovai iš kitų padalinių** — pareigybei (kuri priklauso vienam padaliniui) galima leisti, kad narius į ją skirtų ir kiti padaliniai, kiekvienam nustatant kvotą. Tokios pareigybės matomos pareigybių sąraše (galima filtruoti) ir narių vedlyje; tų padalinių administratoriai gali tvarkyti tik savo padalinio atstovus ir tik neviršydami kvotos
- ✨ **Aktyvūs naudotojai narių sąraše** — pareigybės redagavimo formoje narių perdavimo sąraše pagal nutylėjimą rodomi tik per paskutinius 12 mėn. aktyvūs naudotojai (užima arba turėjo pareigybę, neseniai prisijungė arba yra naujas paskyra); visi kiti pasiekiami perjungus „Rodyti visus naudotojus"

> 🔗 [GitHub PR #568](https://github.com/vu-sa/vusa.lt/pull/568)

## v1.9 — Susitikimų ir administravimo patobulinimai (2026-05-06) {#v1-9}

- ⭐ **Bendri institucijų posėdžiai** — susitikimus dabar galima susieti su keliomis institucijomis, o ne tik su viena
- ✨ **Patobulintos turinio lentelės** — pagerinta datų rodymo, žymų sąraš7 formatavimas, sutrauktas tekstas ir nuorodos visoje administravimo panelėje
- ✨ **Greitųjų nuorodų formos optimizavimas** — patobulinta greitųjų nuorodų kūrimo ir valdymo sąsaja
- ✨ **Aiškesnis el. pašto valdymas naudotojų formose** — naudotojų formose aiškiau paaiškinami el. pašto laukai
- ✨ **Susitikimų rodymo patobulinimai** — atnaujintas susitikimų detalaus rodinio išdėstymas
- 🔧 **Pataisytas 23:59 rodymas susitikimuose** — susitikimai be konkrečios pabaigos laiko daugiau neberodo „23:59" el. laiškuose ir rodiniuose
- 🔧 **Pataisytos ir optimizuotos formos** — pagerintas didelių išskleidžiamųjų sąrašų veikimas keliose administravimo formose

> 🔗 [GitHub PR #566](https://github.com/vu-sa/vusa.lt/pull/566)

## v1.8 — Studijų komplektai (2026-05-05) {#v1-8}

- ⭐ **[Studijų komplektų puslapis](https://www.vusa.lt/ind-komplektai)** — viešas puslapis, kuriame galima naršyti studijų komplektus pagal fakultetą, su dalykų sąrašais ir dėstytojų atsiliepimais
- ✨ **Paieška ir filtravimas** — galima ieškoti pagal dalyko ar studijų komplekto pavadinimą, filtruoti pagal semestrą ir fakultetą
- ⭐ **Studijų komplektų valdymas administratoriams** — galima kurti ir valdyti studijų komplektus, dalykus ir atsiliepimus

> 🔗 [GitHub PR #565](https://github.com/vu-sa/vusa.lt/pull/565)

## v1.7 — Pranešimų patobulinimai (2026-04-06) {#v1-7}

- 🔧 **Pataisytas trigubas el. pašto pranešimų siuntimas** — kiekvienas pranešimas buvo tris kartus įtraukiamas į el. pašto santraukos eilę, dėl ko santraukos laiškuose buvo rodomas trigubai didesnis pranešimų skaičius.
- 🔧 **Pataisytas lietuviškas pranešimų tekstas** — kai kurių pranešimų pavadinime buvo rodomas neišverstas daugiskaitos formatas vietoj tinkamo lietuviško teksto.
- ✨ **Perskaitytų pranešimų sinchronizacija su el. pašto santrauka** — pažymėjus pranešimą kaip perskaitytą platformoje, jis nebepateks į būsimą el. pašto santrauką.

> 🔗 [GitHub PR #554](https://github.com/vu-sa/vusa.lt/pull/554)

## v1.6 — Įvairūs patobulinimai (2026-04-06) {#v1-6}

- 🔧 **Pataisytas puslapių veikimas su tuščiu turiniu** — puslapiuose su tuščiu turinio bloku daugiau nebus rodomas klaidos pranešimas.
- 🔧 **Pataisytos _Table of Contents_ nuorodų paspaudimas** — paspaudus ant antraščių rodyklėje, puslapyje bus parodoma atitinkama antraštė.
- 🔧 **Pašalintas problemų pavadinimo ilgio apribojimas**.
- 🔧 Sutvarkyta serverio klaida, pasitaikanti einant į **angliškus renginių puslapius be dedikuotų pavadinimų** per specifinę nuorodą.
- 🔧 **Failų įkėlimo klaidos taisymas** — failų įkėlimo komponentas naudojo neteisingą nuorodą leistiniems failų tipams gauti, dėl ko kildavo serverio klaida.
- 🔧 **Rezervacijų formos pataisymai**
  - Jeigu pradžios laikas nustatomas vėliau negu pabaigos laikas, gražiau pateikiamas įspėjimas.
  - Pakeitus datą daugiau nebepasirodo „neišsaugotų pakeitimų" perspėjimas.
  - Gražiau pateikti ir greičiau veikiantis resursų pasirinkimas.
  - Paspaudus „Pateikti" daugiau nebepasirodo „neišsaugotų pakeitimų" perspėjimas.

> 🔗 [GitHub PR #553](https://github.com/vu-sa/vusa.lt/pull/553)

## v1.5 — Kalendoriaus ir susitikimų patobulinimai (2026-04-03) {#v1-5}

- 🔧 **Praėję renginiai paslėpti viešame kalendoriuje** — mobilioje versijoje rodomi tik paspaudus „Rodyti ankstesnius"
- 🔧 **Šiandienos susitikimai rodomi skydeliuose** — pagrindiniame ir atstovavimo skydelyje dabar rodomi šiandienos susitikimai, net jie jų konkretus pradžios laikas jau praėjęs

> 🔗 [GitHub PR #550](https://github.com/vu-sa/vusa.lt/pull/550)

## v1.4 — Dokumentacijos atnaujinimas (2026-03-31) {#v1-4}

- ⭐ **Atnaujinimų puslapis** — dokumentacijoje sukurtas atnaujinimų puslapis, kuriame pristatomi platformos pakeitimai. Esant atnaujinimams, admin panelėje prie „Dokumentacija" nuorodos matysite indikatorių

> 🔗 [GitHub PR #546](https://github.com/vu-sa/vusa.lt/pull/546)

## v1.3 — Dokumentų patobulinimai (2026-03-23) {#v1-3}

- ⭐ **[Dokumentų](https://www.vusa.lt/dokumentai) veiksmai** — dokumentų sąraše dabar yra atidarymo, atsisiuntimo ir nuorodos kopijavimo mygtukai
- ✨ **Patikimesnis dokumentų įkėlimas** administratoriams

> 🔗 [GitHub PR #542](https://github.com/vu-sa/vusa.lt/pull/542)

## v1.2 — Teksto laukelis turinyje (2026-03-12) {#v1-2}

- ⭐ **Teksto laukelio blokas** — naujas turinio blokas, leidžiantis lankytojams pateikti atsakymus tiesiai puslapyje. Atsakymus galima peržiūrėti ir eksportuoti į Excel. Šiuo metu naudojamas [vusa.lt tvarumo skiltyje](https://vusa.lt/tvarumas/)

> 🔗 [GitHub PR #532](https://github.com/vu-sa/vusa.lt/pull/532)

## v1.1 — Problemų sekimas (2026-03-10) {#v1-1}

- ⭐ **Problemų valdymas** — nauja skiltis, kurioje galima registruoti, sekti ir valdyti problemas, susijusias su padaliniu
- ⭐ **Susiejimas su institucijomis** — problemas galima susieti su konkrečiomis institucijomis

> 🔗 [GitHub PR #531](https://github.com/vu-sa/vusa.lt/pull/531)

## v1.0 — Platformos modernizacija (2026-02-07) {#v1-0}

> 📰 Pilnas aprašymas: [mano.vusa.lt v1.0: Platformos modernizacija](/blog/2026-02-07-v1-modernization)

> 🔗 [GitHub PR #504](https://github.com/vu-sa/vusa.lt/pull/504)
