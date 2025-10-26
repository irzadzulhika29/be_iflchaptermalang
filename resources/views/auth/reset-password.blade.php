@extends('layouts.template')

@section('content')
<div class="content max-w-md">
    @if (session()->has('error'))
        <div class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <div class="flex">
                <h1 class="font-medium text-sm">{{ session('error') }}</h1>
            </div>
        </div>
    @endif
    <h2 class="mb-1 text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">
        Change Password
    </h2>
    <form class="mt-4 space-y-4 lg:mt-5 md:space-y-5" action="{{ route('password.update') }}" method="POST">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        
        <div class="relative">
            <label for="password" class="block mb-2 text-sm font-medium text-gray-900">New Password</label>
            <input type="password" name="password" id="password" placeholder="enter your new password" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-[#0096c7] focus:border-[#0096c7] block w-full outline-none p-2.5" required>
        </div>
        
        <div class="relative">
            <label for="confirm-password" class="block mb-2 text-sm font-medium text-gray-900">Confirm Password</label>
            <input type="password" name="password_confirmation" id="confirm-password" placeholder="confirm password" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-[#0096c7] focus:border-[#0096c7] block w-full outline-none p-2.5" required>
        </div>
        
        <button type="submit" class="w-full text-white bg-[#0096c7] hover:bg-[#0077B6] font-medium rounded-lg text-sm px-5 py-2.5 text-center duration-300">Reset password</button>
    </form>
</div>
@endsection