@extends('front.layout.layout')

@section('title', 'Payment Failed')

@section('robots', 'noindex, nofollow')

@section('main-content')

    <header class="hero d-flex align-items-center justify-content-center text-center"
        style="
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
              url('https://images.ctfassets.net/px6a31ta05xu/wp-media-78750/418b7767647f5cf9cffc5d76dd304d04/CAP-US-Header-10-CRM-Features-and-Why-You-Need-Them-1200x400-DLVR_US_1200x400_DLVR.png') no-repeat center center;
  background-size: cover;
  min-height: 280px;">
        <div class="container text-white">
            <h1 class="text-white">❌ Payment Cancelled</h1>
        </div>
    </header>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm p-4">
                    <div class="container py-5 text-center">
                        <p>Your payment was cancelled. No charges have been made.</p>
                        <p>If this was a mistake, you can retry the subscription anytime.</p>
                        <hr>
                        <div class="btnBox text-center py-3">
                            <a href="{{ url('/plans') }}" class="btn btn-primary w-50">Choose a Plan Again</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
