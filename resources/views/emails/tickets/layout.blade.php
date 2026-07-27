@extends('emails.layouts.ledrix')

@section('title', $title ?? 'Notification')

@section('content')
    {!! $body !!}
@endsection
