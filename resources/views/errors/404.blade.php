@extends('layouts.user.master')

@section('title'){{ __('Puslapis nerastas') }}@endsection

@section('meta')
    <meta name="robots" content="noindex, follow">
    <meta property="og:url" content="{{"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']}}"/>
    <meta property="og:type" content="website"/>
    <meta property="og:title" content="{{ __('VU SA') }} | {{ __('Puslapis nerastas') }}"/>
    <meta property="og:description" content=""/>
    <meta property="og:image" content="/images/icons/logos/vusa.lin.hor.png"/>
@endsection

@section('content')
    <div class="container" id="infoPage">
        <br/>
        <br/>
        <div class="pageTitle">{{ __('Puslapis nerastas') }}</div>
    </div>
@endsection