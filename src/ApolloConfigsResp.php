<?php

namespace Xbyter\ApolloClient;

/**
 * 阿波罗配置响应
 * @see https://www.apolloconfig.com/#/zh/usage/other-language-client-user-guide
 */
class ApolloConfigsResp
{
    /** @var string 应用的appId	 */
    public  $appId;

    /** @var string 集群名 */
    public  $cluster;

    /** @var string Namespace的名字 */
    public  $namespaceName;

    /** @var array<string,string> 配置列表，key=>value形式 */
    public  $configurations;

    /** @var string 上一次的releaseKey, 如果配置没有变化（传入的releaseKey和服务端的相等），则返回HttpStatus 304，response body为空。 */
    public  $releaseKey;
}
