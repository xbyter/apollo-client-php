<?php

namespace Xbyter\ApolloClient;


/**
 * 阿波罗客户端en
 *
 * @see https://www.apolloconfig.com/#/zh/usage/other-language-client-user-guide
 */
class ApolloClient
{
    private $config;

    public function __construct(ApolloConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $namespace 命名空间
     * @param string $ip 应用部署的机器ip, 这个参数是可选的，用来实现灰度发布。
     * @param string $releaseKey 上一次的releaseKey, 用来给服务端比较版本，如果版本比下来没有变化，则服务端直接返回304以节省流量和运算
     * @return ApolloConfigsResp|null 返回null代表304
     * @throws \ErrorException
     * @throws \JsonException
     */
    public function configs(string $namespace, string $ip = '', string $releaseKey = '')
    {
        $queryParams = [];
        $ip && $queryParams[] = "ip={$ip}";
        $releaseKey && $queryParams[] = "releaseKey={$releaseKey}";
        $url = "{$this->config->configServerUrl}/configs/{$this->config->appId}/{$this->config->cluster}/{$namespace}";
        $queryParams && $url .= "?" . implode('&', $queryParams);
        list($content, $httpCode) = $this->requestApi($url, $this->config->timeout);
        if ($httpCode === 304) {
            return null;
        }

        /** @var array $data */
        $data = json_decode($content, true, 512);
        $resp = new ApolloConfigsResp();
        $resp->appId = $data['appId'];
        $resp->cluster = $data['cluster'];
        $resp->namespaceName = $data['namespaceName'];
        $resp->configurations = $data['configurations'];
        $resp->releaseKey = $data['releaseKey'];
        return $resp;
    }


    /**
     * 通过带缓存的Http接口从Apollo读取配置
     * 由于缓存最多会有一秒的延时，所以如果需要配合配置推送通知实现实时更新配置的话，请参考通过不带缓存的Http接口从Apollo读取配置。
     *
     * @param string $namespace 命名空间
     * @param string $ip 应用部署的机器ip, 这个参数是可选的，用来实现灰度发布。
     * @return string
     * @throws \ErrorException
     */
    public function configfiles(string $namespace, string $ip = ''): string
    {
        $queryParams = [];
        $ip && $queryParams[] = "ip={$ip}";
        $url = "{$this->config->configServerUrl}/configfiles/json/{$this->config->appId}/{$this->config->cluster}/{$namespace}";
        $queryParams && $url .= "?" . implode('&', $queryParams);
        list($content) = $this->requestApi($url, $this->config->timeout);

        return $content;
    }

    /**
     * 适用于应用感知配置更新
     * 实现步骤：
     * 1. 请求远端服务，带上自己的应用信息以及notifications信息
     * 2. 服务端针对传过来的每一个namespace和对应的notificationId，检查notificationId是否是最新的
     * 3. 如果都是最新的，则保持住请求60秒，如果60秒内没有配置变化，则返回HttpStatus 304。如果60秒内有配置变化，则返回对应namespace的最新notificationId, HttpStatus 200。
     * 4. 如果传过来的notifications信息中发现有notificationId比服务端老，则直接返回对应namespace的最新notificationId, HttpStatus 200。
     * 5. 客户端拿到服务端返回后，判断返回的HttpStatus
     * 6. 如果返回的HttpStatus是304，说明配置没有变化，重新执行第1步
     * 7. 如果返回的HttpStauts是200，说明配置有变化，针对变化的namespace重新去服务端拉取配置，参见1.3 通过不带缓存的Http接口从Apollo读取配置。同时更新notifications map中的notificationId。重新执行第1步。
     *
     * @param \Xbyter\ApolloClient\ApolloNotificationsReq[] $notifications
     * @return \Xbyter\ApolloClient\ApolloNotificationsResp[]
     * @throws \ErrorException
     * @throws \JsonException
     */
    public function notifications(array $notifications): array
    {
        $url = "{$this->config->configServerUrl}/notifications/v2?appId={$this->config->appId}&cluster={$this->config->cluster}&notifications=" . urlencode(json_encode($notifications,1));
        list($content, $httpCode) = $this->requestApi($url, 65);//接口会保持60秒, 配置无更改会返回304
        if ($httpCode === 304) {
            return [];
        }

        /** @var array $data */
        $data = json_decode($content, true, 512);
        $list = [];
        foreach ($data as $item) {
            $apolloNotificationsResp = new ApolloNotificationsResp();
            $apolloNotificationsResp->namespaceName = $item['namespaceName'];
            $apolloNotificationsResp->notificationId = $item['notificationId'];
            $list[] = $apolloNotificationsResp;
        }

        return $list;
    }


    /**
     * 接口请求
     *
     * @param string $url
     * @param int $timeout
     * @return array{string, int}
     * @throws \ErrorException
     */
    private function requestApi(string $url, int $timeout): array
    {
        /** @var resource $ch */
        $ch = curl_init($url);
        try {
            $headers = $this->getHeaders($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            /** @var string $body */
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($ch);
            if ($curlErrno > 0) {
                $errorMsg = "CURL错误[{$curlErrno}]:" . curl_error($ch);
                throw new \ErrorException($errorMsg, 0, $curlErrno);
            }

            if ($httpCode >= 400) {
                throw new \ErrorException($body, $httpCode);
            }

            return [$body, $httpCode];
        } finally {
            curl_close($ch);
        }
    }

    /**
     * 获取头部信息
     * @param string $url
     * @return array
     */
    private function getHeaders(string $url): array
    {
        if (!$this->config->secret) {
            return [];
        }

        $headers = [];
        $timestamp = time() * 1000;
        $urlInfo = parse_url($url);
        if (!empty($urlInfo['path'])) {
            $pathWithQuery = $urlInfo['path'];
            if (!empty($urlInfo['query'])) {
                $pathWithQuery .= '?' . $urlInfo['query'];
            }
            $signature = ApolloSignature::generateSignature(
                $timestamp, $pathWithQuery, $this->config->secret
            );
            $headers[] = sprintf("Authorization: Apollo %s:%s", $this->config->appId, $signature);
            $headers[] = sprintf("Timestamp: %s", $timestamp);
        }
        return $headers;
    }
}
