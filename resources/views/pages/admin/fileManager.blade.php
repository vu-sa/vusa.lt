@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <h1> Failų tvarkyklė </h1>
            <ol class="breadcrumb">
                {!! $currentRoute == 'admin/failai'
                ? '<li><a><i class="fas fa-tachometer-alt"></i> Home</a></li>
                <li>Failų tvarkyklė</li>'
                : '' !!}
            </ol>
        </section>
        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    @if ($alias->alias == 'cb' || $alias->alias == 'admin' || $alias->alias == 'DAG')
                        <iframe src="/filemanager" frameborder="0"
                            style="width: 100%; height: 800px; overflow: hidden; border: none;"></iframe>
                    @else
                        <iframe src="/filemanager?type=padaliniai" frameborder="0"
                            style="width: 100%; height: 800px; overflow: hidden; border: none;"></iframe>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection

@section('meta')
    <script type="text/javascript">
        goTo(getQueryVariable('defaultPath'));

    </script>
@endsection
