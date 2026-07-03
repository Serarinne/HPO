@extends('errors.layout')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('message')
You have sent too many requests in a given amount of time. Please try again later.
@endsection