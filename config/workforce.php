<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Open access (temporary)
    |--------------------------------------------------------------------------
    |
    | While the permission rules are still being decided, the whole app is open:
    | no sign-in is needed and every page, button and private detail is visible.
    |
    | Set WORKFORCE_OPEN_ACCESS=false in .env to switch the sign-in and the
    | HR / Head / Employee rules back on (they are all still in the code;
    | see docs/auth.md).
    |
    */

    'open_access' => (bool) env('WORKFORCE_OPEN_ACCESS', true),

];
