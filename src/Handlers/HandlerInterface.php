<?php

namespace Xbyter\ApolloClient\Handlers;

use Xbyter\ApolloClient\ApolloConfigsResp;

/**
 * 阿波罗配置同步处理
 *
 * @see https://www.apolloconfig.com/#/zh/usage/other-language-client-user-guide
 */
interface HandlerInterface
{
    public function handle(ApolloConfigsResp $apolloConfigsResp);
}
