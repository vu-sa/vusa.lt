<?php

return [
    /*
     * Lithuanian numeral agreement: the noun form depends on the last digit(s) of the
     * count, not merely on whether it is one or many. Ranges follow the same shape as
     * studySets.credits.
     */
    'camp_count' => '{1} stovykla|[2,9] stovyklos|[10,20] stovyklų|{21} stovykla|[22,29] stovyklos|[30,30] stovyklų|{31} stovykla|[32,39] stovyklos|[40,*] stovyklų',
    'unit_count' => '{1} padalinys|[2,9] padaliniai|[10,20] padalinių|{21} padalinys|[22,29] padaliniai|[30,30] padalinių|{31} padalinys|[32,39] padaliniai|[40,*] padalinių',
];
