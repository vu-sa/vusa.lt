import { defineComponent } from "vue";

export const BannerDescription = defineComponent({
  render() {
    return (
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
  },
});
