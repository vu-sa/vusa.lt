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
