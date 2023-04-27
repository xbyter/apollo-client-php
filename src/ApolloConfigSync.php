<?php

namespace Xbyter\ApolloClient;

use Xbyter\ApolloClient\Handlers\HandlerInterface;

/**
 * 阿波罗配置同步
 *
 * @see https://www.apolloconfig.com/#/zh/usage/other-language-client-user-guide
 */
class ApolloConfigSync
{
    private  $apolloClient;

    /** @var array<string, int> 命名空间跟通知ID的映射，用于获取最新的notificationId给notifications接口 */
    public  $namespaceNotificationIdMap = [];

    /** @var array<string, \Xbyter\ApolloClient\Handlers\HandlerInterface[]> 命名空间配置处理方式，如保存Env */
    public  $handlers = [];

    public function __construct(ApolloClient $apolloClient)
    {
        $this->apolloClient = $apolloClient;
    }

    /**
     * 增加配置处理方式
     * @param string $namespaceName
     * @param \Xbyter\ApolloClient\Handlers\HandlerInterface $handler
     * @return $this
     */
    public function addHandler(string $namespaceName, HandlerInterface $handler): self
    {
        $this->handlers[$namespaceName][] = $handler;
        return $this;
    }

    /**
     * 开始同步配置
     * @param string $ip 应用部署的机器ip, 用来实现灰度发布
     */
    public function run(string $ip = '', int $timeout = 0)
    {
        $namespaceNames = array_keys($this->handlers);
        $startTime = time();
        //@phpstan-ignore-next-line
        while (true) {
            $this->exitIfTimeout($startTime, $timeout);
            $notifications = $this->buildNotificationsParams($namespaceNames);
            $notificationsRespList = $this->apolloClient->notifications($notifications);
            foreach ($notificationsRespList as $notificationsResp) {
                $this->namespaceNotificationIdMap[$notificationsResp->namespaceName] = $notificationsResp->notificationId;

                //获取配置并执行配置处理Handler
                $configsResp = $this->apolloClient->configs($notificationsResp->namespaceName, $ip);
                if ($configsResp && isset($this->handlers[$notificationsResp->namespaceName])) {
                    foreach ($this->handlers[$notificationsResp->namespaceName] as $handler) {
                        $handler->handle($configsResp);
                    }
                }
            }
        }
    }

    private function exitIfTimeout(int $startTime,int $timeout)
    {
        if($timeout>0 && (time()-$startTime > $timeout)){
            exit(1); // @phpstan-ignore-line
        }
    }

    /**
     * 构建通知参数
     *
     * @param string[] $namespaceNames
     * @return \Xbyter\ApolloClient\ApolloNotificationsReq[]
     */
    private function buildNotificationsParams(array $namespaceNames): array
    {
        $notifications = [];
        foreach ($namespaceNames as $namespaceName) {
            $notificationReq = new ApolloNotificationsReq();
            $notificationReq->namespaceName = $namespaceName;
            $notificationReq->notificationId = $this->namespaceNotificationIdMap[$namespaceName] ?? 0;
            $notifications[] = $notificationReq;
        }
        return $notifications;
    }

    /**
     * 强制同步配置
     * @param string $ip 应用部署的机器ip, 用来实现灰度发布
     * @throws \ErrorException
     * @throws \JsonException
     */
    public function force(string $ip = '')
    {
        foreach ($this->handlers as $namespaceName => $handlers) {
            $configsResp = $this->apolloClient->configs($namespaceName, $ip);
            if (!$configsResp){
                return;
            }
            foreach ($handlers as $handler) {
                $handler->handle($configsResp);
            }
        }
    }
}
