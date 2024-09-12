@extends('errors::minimal')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Server Error'))
@section('extended-message', __('Klaida buvo užfiksuota, einame jos tvarkyti! Jeigu problema išlieka, prašome susisiekti su VU SA IT administratoriumi'))
