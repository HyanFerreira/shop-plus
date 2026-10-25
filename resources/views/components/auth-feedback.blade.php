@if ($errors->any())
    <x-alert variant="danger" class="auth-feedback">
        <p class="font-semibold">Não foi possível continuar</p>
        <ul class="mt-2 list-inside list-disc">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </x-alert>
@endif
@session('status')<x-alert variant="success" class="auth-feedback">{{ $value }}</x-alert>@endsession
