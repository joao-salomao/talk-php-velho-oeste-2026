<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as PassportClient;

/**
 * Cliente OAuth "tudo liberado" pro demo: pula a tela de consentimento
 * pra qualquer client/usuário. Não use em produção.
 */
class OauthClient extends PassportClient
{
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return true;
    }
}
