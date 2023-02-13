<x-mail::message>

<small>🇬🇧 English below</small>

{{-- <x-mail::header :url="config('app.url') . '/mano'">
    VU SA atstovavimo procesas naujinasi! ⭐️
</x-mail::header> --}}

Labas, {{ $user->name }}! 👋

VU Studentų atstovybė atstovavimo procesą kelia į [mano.vusa.lt](https://mano.vusa.lt) tinklapį – kad TU galėtum 
paprasčiau, lengviau ir greičiau įgyvendinti savo tikslus! 🚀

## Kaip prisijungti?

Prie [mano.vusa.lt](https://mano.vusa.lt) prisijunk naudojant studentišką VU Microsoft paskyrą!

{{-- image --}}
<img src="{{config('app.url') . "/images/admin/login.jpg" }}" alt="mano.vusa.lt" width="100%" />

<x-mail::button :url="config('app.url') . '/mano'">
    Prisijunk!
</x-mail::button>

## Kaip naudotis (ir ką daryti)?

Geriausias būdas išmokti – pabandyti! Tikimės, kad per ateinančią savaitę atliksi šiuos veiksmus ✅:

1. Įkelsi vykusius (ar greitu metu vyksiančius) posėdžius ir jų ataskaitas, protokolus;
2. Kitaip išbandysi platformą ir paliksi grįžtamąjį ryšį (pastebėjimai, idėjos)! 📝

<img src="{{config('app.url') . "/images/admin/platform1.png"}}" alt="mano.vusa.lt" width="100%" />

Grįžtamajame ryšyje taip pat parašyk, kokio platesnio susipažinimo su platforma norėtum (susitikimas, vaizdo įrašas ir pan.), jeigu manai, kad tokio reikėtų! 😊

Taip pat, gali klausti ir savo atstovų koordinatoriaus (-ės)! 🤝

Iki susitikimų!

<hr>

Hello, {{ $user->name }}! 👋

VU Studentų atstovybė is moving the representation process to [mano.vusa.lt](https://www.vusa.lt/login?lang=en) – so that YOU could easily, quickly and easily achieve your goals! 🚀

## How to log in?

To [mano.vusa.lt](https://www.vusa.lt/login?lang=en) log in using your VU Microsoft account!

{{-- image --}}
<img src="{{config('app.url') . "/images/admin/login.jpg" }}" alt="mano.vusa.lt" width="100%" />

<x-mail::button :url="config('app.url') . '/mano'">
    Log in!
</x-mail::button>

## How to use (and what to do)?

The best way to learn – to try! We hope that in the coming week you will perform these actions ✅:

1. Upload meetings and their reports, protocols;
2. Try the platform in a different way and leave feedback (comments, ideas)! 📝

<img src="{{config('app.url') . "/images/admin/platform1.png"}}" alt="mano.vusa.lt" width="100%" />

In the feedback, also write what kind of wider acquaintance with the platform you would like (meeting, video recording, etc.), if you think that such a meeting is needed! 😊

For more answers, you can also ask your representative coordinator! 🤝

See you!

<hr>

<small>Gauni šį laišką, nes esi VU studentų atstovas (-ė) šiame organe: {{ $user?->duties?->first()?->institution?->name ?? 'Nenurodyta' }}. Jeigu įvyko klaida, parašyk mums el. paštu: [it@vusa.lt](mailto:it@vusa.lt).</small>

<small>You are receiving this email because you are a VU student representative in this institution: {{ $user?->duties?->first()?->institution?->name ?? 'Not specified' }}. If there was an error, please write to us by email: [it@vusa.lt](mailto:it@vusa.lt).</small>

</x-mail::message>
