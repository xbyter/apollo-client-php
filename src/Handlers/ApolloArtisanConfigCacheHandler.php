<?php

namespace Xbyter\ApolloClient\Handlers;

use Xbyter\ApolloClient\ApolloConfigsResp;

/**
 * 执行 Laravel Artisan config::cache命令
 */
class ApolloArtisanConfigCacheHandler implements HandlerInterface
{
    /** @var string 项目路径 */
    private $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath;
    }

    public function handle(ApolloConfigsResp $apolloConfigsResp)
    {
        //更新config缓存
        if ($this->basePath) {
            include $this->basePath . '/bootstrap/app.php';
        }
        app(\Illuminate\Contracts\Console\Kernel::class)->call("config:cache");
    }
}
