<?php

arch()
    ->expect('App')
    /* ->toUseStrictTypes() */
    ->not->toUse(['die', 'dd', 'dump']);

/* arch() */
/*    ->expect('App\Http') */
/*    ->toOnlyBeUsedIn('App\Http'); */

arch()->preset()->php();
/* arch()->preset()->security()->ignoring('md5'); */
