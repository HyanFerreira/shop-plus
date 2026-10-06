<x-guest-layout>
    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <section class="flex w-full max-w-[715px] overflow-hidden rounded-2xl bg-white shadow-[0_18px_55px_rgb(15_23_42_/_0.12)]">
            <x-auth-benefits-panel />
            <div class="flex-1 p-7 sm:p-9">
                <h1 class="text-2xl font-black tracking-tight text-slate-900">Bem-vindo de volta</h1>
                <p class="mt-1 text-sm text-slate-500">Faça login para continuar</p>

                <x-validation-errors class="mt-5" />
                @session('status') <x-alert variant="success" class="mt-5">{{ $value }}</x-alert> @endsession

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                    @csrf
                    <div><x-label for="email" value="E-mail" /><x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="seu@email.com" /></div>
                    <div><x-label for="password" value="Senha" /><x-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" /><div class="mt-2 flex items-center justify-between"><label for="remember_me" class="flex items-center gap-2 text-sm text-slate-700"><x-checkbox id="remember_me" name="remember" /> Lembrar de mim</label><a href="{{ route('password.request') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Esqueceu sua senha?</a></div></div>
                    <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Entrar</button>
                </form>
                <p class="mt-7 text-center text-sm text-slate-500">Não tem uma conta? <a href="{{ route('register') }}" class="font-bold text-blue-600 hover:text-blue-800">Cadastre-se</a></p>
            </div>
        </section>
    </main>
</x-guest-layout>
