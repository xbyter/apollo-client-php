<?php

namespace Xbyter\ApolloClient\Handlers;

use Xbyter\ApolloClient\ApolloConfigsResp;

/**
 * 阿波罗.env配置同步处理
 *
 * @see https://www.apolloconfig.com/#/zh/usage/other-language-client-user-guide
 */
class ApolloEnvHandler implements HandlerInterface
{
    /** @var string 存储路径 */
    private  $storePath;

    public function __construct(string $storePath)
    {
        if (!is_dir(dirname($storePath))) {
            throw new \ErrorException("未找到存储路径");
        }
        $this->storePath = $storePath;
    }

    public function handle(ApolloConfigsResp $apolloConfigsResp)
    {
        $contents = [];
        foreach ($apolloConfigsResp->configurations as $key => $value) {
            $contents[] = "{$key}={$value}";
        }

        file_put_contents($this->storePath, implode("\n", $contents));
    }
}
