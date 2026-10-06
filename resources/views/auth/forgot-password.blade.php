<x-guest-layout>
    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <section class="w-full max-w-[530px] rounded-2xl bg-white p-8 shadow-[0_18px_55px_rgb(15_23_42_/_0.10)] sm:p-10">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-800"><x-icon name="arrow-left" class="size-4" />Voltar</a>
            <div class="mx-auto mt-7 flex size-28 items-center justify-center rounded-3xl bg-gradient-to-br from-blue-50 to-indigo-100 text-blue-700"><x-icon name="lock-closed" class="size-14" stroke-width="1.5" /></div>
            <h1 class="mt-7 text-3xl font-black tracking-tight text-slate-900">Recuperar senha</h1>
            <p class="mt-2 text-base leading-6 text-slate-500">Digite seu e-mail para receber o link de redefinição de senha.</p>

            @session('status') <x-alert variant="success" class="mt-5">{{ $value }}</x-alert> @endsession
            <x-validation-errors class="mt-5" />

            <form method="POST" action="{{ route('password.email') }}" class="mt-6">
                @csrf
                <x-label for="email" value="E-mail" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="seu@email.com" />
                <button type="submit" class="mt-5 flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Enviar link</button>
            </form>
            <p class="mt-7 text-center text-sm text-slate-500">Lembrou da senha? <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-800">Fazer login</a></p>
        </section>
    </main>
</x-guest-layout>
