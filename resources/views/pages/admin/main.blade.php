@extends('layouts.admin.master')

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#"><i class="fas fa-tachometer-alt"></i>Home</a></li>
                            {!! $currentRoute == 'admin' ? '<li class="breadcrumb-item active">Dashboard</li>' : '' !!}
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
