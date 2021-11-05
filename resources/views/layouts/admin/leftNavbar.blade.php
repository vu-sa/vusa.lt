<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/admin" class="brand-link" style="text-align: center">
        <span class="logo-mini"><b>VU SA</b></span>
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar text-sm flex-column" data-widget="treeview" role="menu">
                <li class="nav-item {{ $currentRoute == 'admin' ? 'active' : '' }}">
                    <a href="/admin" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Pradinis</p>
                    </a>
                </li>
                @can('handle', App\Models\MainPage::class)
                <li class="nav-item {{ strpos($currentRoute, 'admin/pagrindinis') !== false ? 'active' : '' }}">
                    <a href="/admin/pagrindinis" class="nav-link">
                        <i class="nav-icon fas fa-newspaper"></i>
                        <p>Pradinis puslapis</p>
                    </a>
                </li>
                @endcan
                @can('handle', App\Models\Contact::class)
                    <li
                        class="nav-item has-treeview {{ strpos($currentRoute, 'admin/kontaktai/') !== false ? 'active' : '' }}">
                        <a href="#" class="nav-link"> <i class="nav-icon fa fa-users"></i>
                            <p>Kontaktai<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('handleCB', App\Models\Contact::class)
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/aprasymas') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/aprasymas" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Aprašymai</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Centrinis biuras') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/centrinis-biuras" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Centrinis biuras</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Centrinis biuras EN') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/central-office" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Centrinis biuras EN</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Parlamentas') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/parlamentas" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Parlamentas</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Parlamento darbo grupės') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/parlamento-darbo-grupes" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Parlamento darbo grupės</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Padaliniai') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/padaliniai" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Padaliniai</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Taryba') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/taryba" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Taryba</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Revizija') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/Revizija" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Revizija</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Institucinio stiprinimo fondas') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/stiprinimas" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Institucinio stiprinimo fondas</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Studentų atstovai LT') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/studentu-atstovai-lt" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Studentų atstovai LT</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Studentų atstovai EN') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/studentu-atstovai-en" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Studentų atstovai EN</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                            {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Programos, klubai ir projektai') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/programos-klubai-projektai" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Programos,
                                        klubai ir projektai</p>
                                </a>
                            </li>
                        @endcan
                        @can('handlePadaliniai', App\Models\Contact::class)
                            <li
                                class="nav-item
                    {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/aprasymas-padalinys') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/aprasymas-padalinys" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Aprašymas</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                    {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/padalinio-biuras') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/padalinio-biuras" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Koordinatoriai</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                    {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/padalinio-biuras-en') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/padalinio-biuras-en" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Koordinatoriai EN</p>
                                </a>
                            </li>
                            {{-- <li
                                class="nav-item
                    {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/{name}/Centrinis biuras EN') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/padalinio-studentu-atstovai" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Studentų atstovai</p>
                                </a>
                            </li> --}}
                            <li
                                class="nav-item
                    {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/padalinio-kuratoriai') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/padalinio-kuratoriai" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Kuratoriai</p>
                                </a>
                            </li>
                            <li
                                class="nav-item
                    {{ strpos($currentRoute . '/' . $name, 'admin/kontaktai/padalinio-kuratoriai-en') !== false ? 'active' : '' }}">
                                <a href="/admin/kontaktai/padalinio-kuratoriai-en" class="nav-link"><i
                                        class="far fa-circle fa-sm"></i>
                                    <p>Kuratoriai EN</p>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
                @endcan

                @can('handle', App\Models\Navigation::class)
                    <li
                        class="nav-item has-treeview {{ strpos($currentRoute, 'admin/navigacija') !== false ? 'active' : '' }}">
                        <a href="#" class="nav-link"> <i class="nav-icon fas fa-bars"></i>
                            <p>Navigacija <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li
                                class="nav-item {{ strpos($currentRoute, 'admin/navigacijaLT') !== false ? 'active' : '' }}">
                                <a href="/admin/navigacijaLT" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Navigacija LT</p>
                                </a>
                            </li>
                            <li class="nav-item {{ $currentRoute == 'admin/navigacijaEN' ? 'active' : '' }}">
                                <a href="/admin/navigacijaEN" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Navigacija EN</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                @can('handle', App\Models\Page::class)
                    <li
                        class="nav-item has-treeview {{ strpos($currentRoute, 'admin/naujienos') !== false ? 'active' : '' }}">
                        <a href="#" class="nav-link"> <i class="nav-icon fas fa-newspaper"></i>
                            <p>Naujienos<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li
                                class="nav-item {{ strpos($currentRoute, 'admin/naujienosLT') !== false ? 'active' : '' }}">
                                <a href="/admin/naujienosLT" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Naujienos LT</p>
                                </a>
                            </li>
                            @can('handle', App\Models\Page::class)
                                <li
                                    class="nav-item {{ strpos($currentRoute, 'admin/naujienosEN') !== false ? 'active' : '' }}">
                                    <a href="/admin/naujienosEN" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                        <p>Naujienos EN</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('handle', App\Models\Page::class)
                    <li class="nav-item has-treeview
                    {{ strpos($currentRoute, 'admin/puslapiai') !== false ? 'active' : '' }}">
                        <a href="#" class="nav-link"> <i class="nav-icon far fa-newspaper"></i>
                            <p>Puslapiai<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li
                                class="nav-item {{ strpos($currentRoute, 'admin/puslapiaiLT') !== false ? 'active' : '' }}">
                                <a href="/admin/puslapiaiLT" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                    <p>Puslapiai LT</p>
                                </a>
                            </li>
                            @can('handle', App\Models\Page::class)
                                <li
                                    class="nav-item {{ strpos($currentRoute, 'admin/puslapiaiEN') !== false ? 'active' : '' }}">
                                    <a href="/admin/puslapiaiEN" class="nav-link"><i class="far fa-circle fa-sm"></i>
                                        <p>Puslapiai EN</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('handle', App\Models\Agenda::class)
                    <li class="nav-item {{ strpos($currentRoute, 'admin/darbotvarke') !== false ? 'active' : '' }}">
                        <a href="/admin/darbotvarke" class="nav-link">
                            <i class="nav-icon far fa-calendar-alt"></i>
                            <p>Darbotvarkė</p>
                        </a>
                    </li>

                    <li class="nav-item {{ strpos($currentRoute, 'admin/kalendorius') !== false ? 'active' : '' }}">
                        <a href="/admin/kalendorius" class="nav-link">
                            <i class="nav-icon far fa-calendar"></i>
                            <p>Kalendorius</p>
                        </a>
                    </li>
                @endcan

                @can('handleUsers', App\Models\User::class)
                    <li
                        class="nav-item has-treeview {{ strpos($currentRoute, 'admin/vartotojai') !== false || strpos($currentRoute, 'admin/grupes') !== false ? 'active' : '' }}">
                        <a href="#" class="nav-link"><i class="nav-icon fas fa-user"></i>
                            <p>Vartotojų, grupių valdymas<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li
                                class="nav-item {{ strpos($currentRoute, 'admin/vartotojai') !== false ? 'active' : '' }}">
                                <a href="/admin/vartotojai" class="nav-link"><i class="fas fa-user"></i>
                                    <p>Vartotojų
                                        valdymas</p>
                                </a>
                            </li>
                            <li class="nav-item {{ strpos($currentRoute, 'admin/grupes') !== false ? 'active' : '' }}">
                                <a href="/admin/grupes" class="nav-link"><i class="fas fa-users"></i>
                                    <p>Grupių
                                        valdymas</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                @can('handleFiles', App\Models\User::class)
                    <li class="nav-item {{ strpos($currentRoute, 'admin/failai') !== false ? 'active' : '' }}">
                        <a href="/admin/failai" class="nav-link">
                            <i class="nav-icon far fa-folder-open"></i>
                            <p>Failų tvarkyklė</p>
                        </a>
                    </li>
                @endcan

                @can('handle', App\Models\Padalinys::class)
                    <li class="nav-item {{ strpos($currentRoute, 'admin/padaliniai') !== false ? 'active' : '' }}">
                        <a href="/admin/padaliniai" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Padalinių valdymas</p>
                        </a>
                    </li>
                @endcan

                @can('handle', App\Models\Banner::class)
                    <li class="nav-item {{ strpos($currentRoute, 'admin/reklama') !== false ? 'active' : '' }}">
                        <a href="/admin/reklama" class="nav-link">
                            <i class="nav-icon fas fa-ad"></i>
                            <p>Reklamos baneriai</p>
                        </a>
                    </li>
                @endcan
                @can('handle', App\Models\Saziningai::class)
                    <li
                        class="nav-item has-treeview {{ strpos($currentRoute, 'admin/saziningai') !== false ? 'active' : '' }}">
                        <a href="#" class="nav-link"><i class="nav-icon fas fa-book-reader"></i>
                            <p>Sąžiningai<i class="right fas fa-angle-left"></i></p>
                        </a>

                        <ul class="nav nav-treeview">
                            <li
                                class="nav-item {{ strpos($currentRoute, 'admin/saziningai') !== false ? 'active' : '' }}">
                                <a href="/admin/saziningai?page=1" class="nav-link"><i class="fa fa-book"></i>
                                    <p>Atsiskaitymų informacija</p>
                                </a>
                            </li>
                            <li
                                class="nav-item {{ strpos($currentRoute, 'admin/saziningai-uzsiregistrave') !== false ? 'active' : '' }}">
                                <a href="/admin/saziningai-uzsiregistrave?page=1" class="nav-link"><i
                                        class="fas fa-users"></i>
                                    <p>Užsiregistravusių stebėtojų informacija</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                <li class="nav-item {{ strpos($currentRoute, 'admin/atnaujinimai') !== false ? 'active' : '' }}">
                    <a href="/admin/atnaujinimai" class="nav-link">
                        <i class="nav-icon far fa-newspaper"></i>
                        <p>vusa.lt atnaujinimai
                            <span class="right badge badge-danger">
                                10-20
                            </span>
                        </p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>
