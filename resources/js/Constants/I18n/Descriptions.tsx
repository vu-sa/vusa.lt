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
      <>
        <p class="mb-4">
          Pakeitus rezervacijos laiką, pasirinkti ištekliai bus išvalyti.
        </p>
        <p>Rodomas
          išteklių kiekis <strong>automatiškai apskaičiuojamas nurodytam rezervacijos laikotarpiui.</strong>
        </p>
      </>
    ),
    en: (
      <>
        <p class="mb-4">
          Changing the reservation time will clear the selected resources.
        </p><p>
          The
          number of resources displayed is <strong>automatically calculated for the specified reservation period.</strong>
        </p>
      </>
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
