<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\Server\AppResource as BaseAppResource;

/**
 * Base AppResource pro projeto. O Laravel MCP injeta `ui.domain` derivado
 * do APP_URL por padrão, mas o Claude valida o formato e só aceita o
 * sandbox `<hash>.claudemcpcontent.com` que ele próprio gera. Removemos
 * o campo pra deixar o host decidir.
 */
abstract class AppResource extends BaseAppResource
{
    /**
     * @return array<string, mixed>
     */
    public function resolvedAppMeta(): array
    {
        $meta = $this->appMeta()->toArray();
        unset($meta['domain']);

        return $meta;
    }
}
