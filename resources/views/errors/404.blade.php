@extends('frontend.master')

@section('title', '- Page Not Found')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="error-page text-center" style="padding: 80px 0;">
            <h1 style="font-size: 120px; font-weight: bold; color: #e74c3c; margin-bottom: 20px;">404</h1>
            <h2 style="font-size: 36px; margin-bottom: 20px;">Page Not Found</h2>
            <p style="font-size: 18px; color: #777; margin-bottom: 30px;">
                Sorry, the page you are looking for could not be found.
            </p>
            <a href="{{ route('home') }}" class="btn btn-primary btn-lg">
                <i class="fa fa-home"></i> Go Back to Home
            </a>
        </div>
    </div>
</div>

<style>
    .error-page {
        min-height: 400px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .error-page h1 {
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
</style>
@endsection
