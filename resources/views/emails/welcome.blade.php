<x-mail::message>

{{-- <x-mail::header :url="config('app.url') . '/mano'">
    VU SA atstovavimo procesas naujinasi! ⭐️
</x-mail::header> --}}

Labas, {{ $user->name }}! 👋

VU Studentų atstovybė atstovavimo procesą kelia į [mano.vusa.lt](https://mano.vusa.lt) tinklapį! 

## Kaip prisijungti?

Visi VU studentų atstovai gali prisijungti prie [mano.vusa.lt](https://mano.vusa.lt), naudojant 
studentišką VU Microsoft paskyrą!

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

Iki susitikimų!

<hr>

<small>Gauni šį laišką, nes esi VU studentų atstovas (-ė) šiame organe: {{ $user?->duties?->first()?->institution?->name ?? 'Nenurodyta' }}. Jeigu įvyko klaida, parašyk mums el. paštu: [it@vusa.lt](mailto:it@vusa.lt)</small>

</x-mail::message>
