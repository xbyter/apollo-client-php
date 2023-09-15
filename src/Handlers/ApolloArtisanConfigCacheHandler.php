<?php

namespace Xbyter\ApolloClient\Handlers;

use Xbyter\ApolloClient\ApolloConfigsResp;

/**
 * 执行 Laravel Artisan config::cache命令
 * 需要引入 '/bootstrap/app.php';
 */
class ApolloArtisanConfigCacheHandler implements HandlerInterface
{
    public function handle(ApolloConfigsResp $apolloConfigsResp)
    {
        app(\Illuminate\Contracts\Console\Kernel::class)->call("config:cache");
    }
}
