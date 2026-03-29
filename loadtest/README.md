# k6 压测脚本

## 先安装 k6

Mac:

```bash
brew install k6
```

## 需要准备的内容

- `COOKIE`：浏览器登录后的完整 Cookie
- `BASE_URL`：线上地址，默认是 `http://39.106.59.118:8081`
- `TASK_IDS`：压 `status_batch.php` 时要传，逗号分隔

## 1. 压 create.php

默认是低强度真实生成压测：

```bash
COOKIE='你的完整Cookie' \
BASE_URL='http://39.106.59.118:8081' \
k6 run loadtest/create.js
```

常用可调参数：

```bash
COOKIE='你的完整Cookie' \
BASE_URL='http://39.106.59.118:8081' \
TYPE='image' \
MODEL='banana' \
PROMPT='一个极简风格的现代客厅，柔和自然光，高级感' \
ASPECT_RATIO='3:4' \
QUALITY='2k' \
COUNT='1' \
SLEEP_SECONDS='5' \
APP_STAGES='[{"duration":"30s","target":10},{"duration":"1m","target":20},{"duration":"30s","target":0}]' \
k6 run loadtest/create.js
```

如果是视频：

```bash
COOKIE='你的完整Cookie' \
TYPE='video' \
MODEL='doubao-video' \
ASPECT_RATIO='16:9' \
QUALITY='standard' \
DURATION='5' \
k6 run loadtest/create.js
```

## 2. 压 status_batch.php

先准备几个真实存在的 `taskId`：

```bash
COOKIE='你的完整Cookie' \
TASK_IDS='task_xxx,task_yyy,task_zzz' \
BASE_URL='http://39.106.59.118:8081' \
k6 run loadtest/status_batch.js
```

更高一点的轮询压测：

```bash
COOKIE='你的完整Cookie' \
TASK_IDS='task_xxx,task_yyy,task_zzz,task_aaa,task_bbb' \
SLEEP_SECONDS='2' \
APP_STAGES='[{"duration":"30s","target":20},{"duration":"1m","target":50},{"duration":"30s","target":0}]' \
k6 run loadtest/status_batch.js
```

## 建议压测顺序

1. 先压 `status_batch.php`
2. 再低强度压 `create.php`
3. 再做 20 并发真实生成
4. 稳定后提到 50
5. 最后再接近 100

## 建议同时盯的服务器指标

```bash
supervisorctl status
redis-cli ping
redis-cli llen ai_creator:queue:generation:submit
redis-cli zcard ai_creator:queue:generation:poll_schedule
```

## 注意

- `create.php` 会真实扣积分、真实调用第三方模型，压测前请先控制并发和时长。
- 如果需要看非 JSON 错误响应，可加 `DEBUG_RESPONSE=1`。
