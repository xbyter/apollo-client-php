<?php

namespace Xbyter\ApolloClient;

/**
 * 阿波罗通知请求
 * @see https://www.apolloconfig.com/#/zh/usage/other-language-client-user-guide
 */
class ApolloNotificationsReq
{
    /** @var string Namespace的名字 */
    public  $namespaceName;

    /** @var int 通知ID，用于判断是否是最新的配置 */
    public  $notificationId;
}
