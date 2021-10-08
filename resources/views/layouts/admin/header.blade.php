<nav class="main-header navbar navbar-expand navbar-white navbar-light" role="navigation">
    <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
    </ul>

    <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="text-white">{{ $sessionInfo->realname }}</div>
                </button>
            
                <div class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownMenuButton">
                    <a href="/admin/vartotojai/{{ $sessionInfo->username }}/keistislaptazodi"
                        class="dropdown-item">Pasikeisti slaptažodį</a>

                <a class="dropdown-item" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Atsijungti
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </ul>
    </div>
</nav>
