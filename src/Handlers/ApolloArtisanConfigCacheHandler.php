<?php

namespace Xbyter\ApolloClient\Handlers;

use Xbyter\ApolloClient\ApolloConfigsResp;

/**
 * 执行 Laravel Artisan config::cache命令
 * 需要引入 '/bootstrap/app.php';
 */
class ApolloArtisanConfigCacheHandler implements HandlerInterface
{
    /** @var string 存储路径 */
    private  $storePath;

    public function __construct(string $storePath = '')
    {
        $this->storePath = $storePath;
    }

    public function handle(ApolloConfigsResp $apolloConfigsResp)
    {
        //强制重新加载.env文件配置
        if ($this->storePath) {
            $dotenv = \Dotenv\Dotenv::create(dirname($this->storePath), basename($this->storePath));
            $dotenv->overload();
        }

        app(\Illuminate\Contracts\Console\Kernel::class)->call("config:cache");
    }
}
