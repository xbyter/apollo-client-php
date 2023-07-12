# 基于携程Apollo的PHP客户端

## 说明
基于携程Apollo的PHP客户端，不依赖第三方扩展或框架。可用于Laravel, ThinkPHP, YII, Symfony, Swoole等框架。默认支持.env文件的配置同步，如需支持其他格式的配置同步可增加新的Handler处理器，新的Handler处理器需实现`Xbyter\ApolloClient\Handlers\HandlerInterface`接口。详见`Hander处理器`

## 安装
```
composer require xbyter/apollo-client
```

## 使用
### 代码示例apollo_config_sync.php
```php
use Xbyter\ApolloClient\ApolloClient;
use Xbyter\ApolloClient\ApolloConfig;
use Xbyter\ApolloClient\ApolloConfigSync;
use Xbyter\ApolloClient\Handlers\ApolloEnvHandler;

define('BASE_PATH', dirname(__DIR__) . '/'); //项目根目录

include BASE_PATH . 'vendor/autoload.php';

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
$apolloConfig->cluster = $_ENV['APOLLO_CLUSTER'];
$apolloConfig->secret = $_ENV['APOLLO_SECRET'];


//开始同步配置到.env
$timeout = (int)($argv[1] ?? 0);//定时任务跑一般设置为60，supervisor跑可不设置
$apolloClient = new ApolloClient($apolloConfig);
$handler = new ApolloEnvHandler($sysEnvPath);
$sync = new ApolloConfigSync($apolloClient);
$sync->addHandler($_ENV['APOLLO_NAMESPACE'], $handler);

//用force方法强制同步配置一次
$sync->force();
//或者常驻执行
$sync->run($_SERVER['SERVER_ADDR'], $timeout);

```


### 配置示例（.apollo.env）
```
//不限制配置读取方式，依据具体代码实现
APOLLO_CONFIG_SERVER_URL=阿波罗配置同步地址
APOLLO_APP_ID=app
APOLLO_CLUSTER=default
APOLLO_NAMESPACE=application
APOLLO_SECRET=
```

### 使用定时任务同步配置（可实时同步）
```
//以下命令每分钟执行一次，每次会保持住60s进程，在此期间会实时监控配置变更
* * * * * php apollo_config_sync.php 60
```

### 使用Supervisor同步配置
```
[program:apollo]
process_name=%(program_name)s_%(process_num)02d
command=php /home/www/app.com/apollo_config_sync.php
autostart=true
autorestart=true
user=www
numprocs=1
redirect_stderr=true
stopwaitsecs=60
stdout_logfile=/home/www/app.com/apollo.log
```

## Hander处理器（可实现多个namespace或多种配置方式同步）
代码默认实现了.env文件的配置同步，如需其他格式的配置同步可增加新的Handler处理器，新的Handler处理器需实现`Xbyter\ApolloClient\Handlers\HandlerInterface`接口
```php
$apolloClient = new ApolloClient($apolloConfig);
//.env处理器
$handler = new ApolloEnvHandler($sysEnvPath);
$sync = new ApolloConfigSync($apolloClient);
$sync->addHandler($_ENV['APOLLO_NAMESPACE'], $handler);
$sync->addHandler('阿波罗命名空间namespace1', 新的处理器1);//每个namespace都可以有不同/相同的处理方式
$sync->addHandler('阿波罗命名空间namespace1', 新的处理器2);
$sync->addHandler('阿波罗命名空间namespace2', 新的处理器3);
```
