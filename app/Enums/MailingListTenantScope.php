<?php

namespace App\Enums;

enum MailingListTenantScope: string
{
    case All = 'all';
    case Main = 'main';
    case Padaliniai = 'padaliniai';
    case Selected = 'selected';
}
