<?php
/**
 * 原生命令同步阿波罗配置（不用artisan，防止配置出错导致执行artisan报错）
 * 建议使用supervisor守护
 */

use Xbyter\ApolloClient\ApolloClient;
use Xbyter\ApolloClient\ApolloConfig;
use Xbyter\ApolloClient\ApolloConfigSync;
use Xbyter\ApolloClient\Handlers\ApolloEnvHandler;
use Xbyter\ApolloClient\Handlers\ApolloArtisanConfigCacheHandler;

define('BASE_PATH', dirname(__DIR__) . '/'); //项目根目录

include BASE_PATH . 'vendor/autoload.php';
//执行Laravel相关命令需要引入bootstrap/app.php, 比如使用ApolloArtisanConfigCacheHandler来将配置缓存
include BASE_PATH.'/bootstrap/app.php';

//系统.env配置，阿波罗的配置会同步到该文件
$sysEnvPath = BASE_PATH . '.env';
//阿波罗配置文件位置，需要放在本地，也可以直接走环境变量$_ENV（需要设置php.ini：variables_order = "EGPCS"）
$apolloEnvFile = '.apollo.env';

//解析.env文件
$dotenv = Dotenv\Dotenv::create(__DIR__, $apolloEnvFile);
$dotenv->load();

//阿波罗配置
$apolloConfig = new ApolloConfig();
$apolloConfig->configServerUrl = $_ENV['APOLLO_CONFIG_SERVER_URL'];
$apolloConfig->appId = $_ENV['APOLLO_APP_ID'];
$apolloConfig->cluster = $_ENV['APOLLO_CLUSTER'] ?? 'default';
$apolloConfig->secret = $_ENV['APOLLO_SECRET'] ?? '';

//开始同步配置到.env
$timeout = (int)($argv[1] ?? 0);//定时任务跑一般设置为60，supervisor跑可不设置
$apolloClient = new ApolloClient($apolloConfig);
$handler = new ApolloEnvHandler($sysEnvPath);
$sync = new ApolloConfigSync($apolloClient);
$sync->addHandler($_ENV['APOLLO_NAMESPACE'] ?? 'application', $handler);

//如果需要执行Laravel的artisan config:cache命令，则建议加下下面Handler（需要在开头引入Laravel的bootstrap/app.php）。否则会导致写文件的一瞬间，.env文件会被先清空读不到内容。
$configCacheHandler = new ApolloArtisanConfigCacheHandler($sysEnvPath);
$sync->addHandler($_ENV['APOLLO_NAMESPACE'] ?? 'application', $configCacheHandler);


//用force方法强制同步配置一次
$sync->force();
//或者常驻执行
$sync->run($_SERVER['SERVER_ADDR'], $timeout);
