<x-guest-layout>
    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <section class="flex w-full max-w-[760px] overflow-hidden rounded-2xl bg-white shadow-[0_18px_55px_rgb(15_23_42_/_0.12)]">
            <x-auth-benefits-panel />
            <div class="flex-1 p-7 sm:p-9">
                <h1 class="text-2xl font-black tracking-tight text-slate-900">Crie sua conta</h1>
                <p class="mt-1 text-sm text-slate-500">Cadastre-se para começar a comprar.</p>

                <x-validation-errors class="mt-5" />

                <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-3.5">
                    @csrf
                    <div><x-label for="name" value="Nome" /><x-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Seu nome" /></div>
                    <div><x-label for="email" value="E-mail" /><x-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="seu@email.com" /></div>
                    <div><x-label for="password" value="Senha" /><x-input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Crie uma senha forte" /></div>
                    <div><x-label for="password_confirmation" value="Confirmar senha" /><x-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repita sua senha" /></div>

                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                        <label for="terms" class="flex gap-2 text-xs leading-5 text-slate-600"><x-checkbox name="terms" id="terms" required /><span>{!! __('I agree to the :terms_of_service and :privacy_policy', ['terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="font-semibold text-blue-600 hover:text-blue-800">'.__('Terms of Service').'</a>', 'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="font-semibold text-blue-600 hover:text-blue-800">'.__('Privacy Policy').'</a>']) !!}</span></label>
                    @endif

                    <button type="submit" class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Criar conta</button>
                </form>
                <p class="mt-6 text-center text-sm text-slate-500">Já tem uma conta? <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-800">Fazer login</a></p>
            </div>
        </section>
    </main>
</x-guest-layout>
