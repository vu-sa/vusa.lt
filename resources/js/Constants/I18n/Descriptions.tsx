export const RESOURCE_CATEGORY_DESCRIPTIONS = {
  main_info: {
    lt: (
      <>
        <p>
          Kategorijos padeda suskirstyti išteklius pagal jų paskirtį.
        </p>
        <p class="mt-2">
          Kategorijos gali būti priskirtos ištekliui, kad būtų lengviau
          jas surasti.
        </p>
      </>
    ),
    en: (
      <>
        <p>
          Categories help to divide resources according to their purpose.
          Categories are added when creating a resource type.
        </p>
        <p class="mt-2">
          Categories can be assigned to a resource to make it easier to find.
        </p>
      </>
    ),
  }
}

export const RESERVATION_DESCRIPTIONS = {
  main_info: {
    lt: (
      <>
        <p>
          Daiktų rezervacijos laikas yra nuo jų <strong>atsiėmimo laiko</strong>{" "}
          iki <strong> grąžinimo laiko</strong>.
        </p>
        <p class="mt-2">
          Skolinimosi laikas galioja visiems rezervuojamiems daiktams. Norint
          individualiai pakeisti skolintino daikto laiką, pridėk daiktą jau
          sukūrus rezervaciją.
        </p>
      </>
    ),
    en: (
      <>
        <p>
          The reservation time is from the time of <strong>pick up</strong> to{" "}
          <strong>return</strong>.
        </p>
        <p class="mt-2">
          The borrowing time applies to all items reserved. To individually
          change the time of the borrowed item, add the item after creating the
          reservation.
        </p>
      </>
    ),
  },
  resources: {
    lt: (
      <p class="mb-4">
        Pakeitus rezervacijos laiką, pasirinkti ištekliai bus išvalyti. Rodomas
        išteklių kiekis nurodytu rezervacijos laikotarpiu.
      </p>
    ),
    en: (
      <p class="mb-4">
        Changing the reservation time will clear the selected resources. The
        amount of resources available during the specified reservation period is
        displayed.
      </p>
    ),
  },
  help: {
    lt: (
      <>
        <p>
          Kiekviena rezervacija gali turėti 6 skirtingus statusus: sukurta,
          rezervuota, paskolinta, grąžinta, atmesta, atšaukta.
        </p>

        <p class="mt-4">
          Sukūrus išteklio rezervacijos užklausą, ją galima atmesti.
          Rezervacijos kūrėjai taip pat gali atšaukti išteklio rezervaciją, iki
          daikto pasiskolinimo.
        </p>
      </>
    ),
    en: (
      <>
        <p>
          Each reservation can have 6 different statuses: created, reserved,
          lent, returned, rejected, cancelled.
        </p>
        <p class="mt-4">
          After creating a resource reservation request, it can be rejected.
          Reservation creators can also cancel the resource reservation until
          the item is borrowed.
        </p>
      </>
    ),
  },
};

export const RESOURCE_DESCRIPTIONS = {
  media: {
    lt: (
      <p>
        Rekomenduojama, kad kiekvienas išteklius turėtų nuotraukų. Jas gali
        matyti ir rezervaciją kuriantys asmenys.
      </p>
    ),
    en: (
      <p>
        It is recommended that each resource have photos. They can be seen by
        people creating a reservation.
      </p>
    ),
  },
};
