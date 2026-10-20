@extends('layouts.app')
@section('title', 'محاسبه‌گر تعرفه و تناژ')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">محاسبه‌گر تعرفه و تناژ (بدون ثبت در سیستم)</h4>
        <a href="{{ route('cargo.index') }}" class="btn btn-outline-secondary btn-sm">بازگشت به عملیات بارگیری</a>
    </div>

    @include('partials.tariff-calculator', ['tariffs' => $tariffs, 'taxPercentage' => $taxPercentage])
@endsection
