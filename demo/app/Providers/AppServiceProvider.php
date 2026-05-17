<?php

namespace App\Providers;

use App\Agents\SupportAgent;
use App\Models\OauthClient;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // OAuth "tudo liberado" pro demo:
        //  - authorizationView() registra um binding no container — sem isso,
        //    o AuthorizationController::authorize falha em DI antes do método
        //    rodar, mesmo que o consent fosse pulado.
        //  - useClientModel(OauthClient) faz Passport pular a tela de
        //    consentimento, então a view nem chega a ser renderizada.
        Passport::authorizationView('mcp::authorize');
        Passport::useClientModel(OauthClient::class);
    }
}
