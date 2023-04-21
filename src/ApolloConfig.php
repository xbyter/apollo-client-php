<?php

namespace Xbyter\ApolloClient;

/**
 * 阿波罗配置
 * @see https://www.apolloconfig.com/#/zh/usage/other-language-client-user-guide
 */
class ApolloConfig
{
    /** @var string Apollo配置服务的地址 */
    public  $configServerUrl;

    /** @var string 应用的appId	 */
    public  $appId;

    /** @var string 集群名 */
    public  $cluster = 'default';

    /** @var string Apollo从1.6.0版本开始增加访问密钥机制 */
    public  $secret = '';

    /** @var int 超时时间 */
    public  $timeout = 10;
}
