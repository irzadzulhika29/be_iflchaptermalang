@extends('layouts.template')

@section('content')
<div class="content max-w-2xl">
    <div class="flex items-center flex-col gap-6 text-center">
        <img class="w-12 h-12 sm:w-20 sm:h-20 relative animate-bounce" src={{ $isErrorImg ? asset('/assets/image/error.webp') : asset('/assets/image/check.webp') }} alt="icon">
        <h1 class="font-semibold text-lg tracking-normal sm:tracking-wide sm:text-2xl">{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href={{ $directLink }} class="underline text-[#0096c7]">{{ $titleLogin }}</a>
    </div>
</div>
@endsection