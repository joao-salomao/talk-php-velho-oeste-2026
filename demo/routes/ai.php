<?php

use App\Mcp\Servers\SupportServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('support', SupportServer::class);
Mcp::web('/mcp/support', SupportServer::class);

// Discovery endpoints (.well-known/oauth-*) + DCR (/oauth/register)
// que o Claude exige no fluxo de Custom Connector — mesmo que a
// nossa Tool não cheque o token. Requer Passport.
Mcp::oauthRoutes();
