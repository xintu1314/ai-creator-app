# 生成队列化上线说明

## 新增组件

- Redis：任务提交队列、轮询调度队列、媒体同步队列。
- Worker：
  - `api/workers/submit_worker.php`
  - `api/workers/poll_worker.php`
  - `api/workers/oss_worker.php`
- Supervisor 配置：
  - `deploy/generation-workers.supervisor.conf`

## 配置项

### 1. Redis

- 复制 `api/config/queue.local.example.php` 为 `api/config/queue.local.php`
- 至少配置：
  - `redis.host`
  - `redis.port`
  - `redis.password`
  - `redis.database`
  - `redis.prefix`

### 2. Provider key 池

- 在 `api/config/ai.local.php` 中可继续保留原有 `api_key`
- 如需扩 key，新增：
  - `wuyinkeji.api_keys`
  - `grsai.api_keys`
  - `doubao.api_keys`
- 并可单独调整：
  - `submit_concurrency`

## 初始化步骤

1. 执行数据库迁移：`php api/db/init.php`
2. 确认 Redis 可连通
3. 单次验证 Worker：
   - `php api/workers/submit_worker.php --once`
   - `php api/workers/poll_worker.php --once`
   - `php api/workers/oss_worker.php --once`
4. 再接入 supervisor 常驻

## Supervisor 启动

1. 复制 `deploy/generation-workers.supervisor.conf` 到 supervisor 配置目录
2. 确保 `deploy/run_generation_worker.sh` 具备执行权限
3. 执行：
   - `supervisorctl reread`
   - `supervisorctl update`
   - `supervisorctl status`

## 观测点

- Redis：
  - `LLEN ai_creator:queue:generation:submit`
  - `ZRANGEBYSCORE ai_creator:queue:generation:poll_schedule -inf +inf LIMIT 0 10`
- 应用日志：
  - `api/logs/generation-worker-*.log`
  - `api/logs/wuyin-*.log`
  - `api/logs/grsai-*.log`
  - `api/logs/doubao-*.log`
- Web 接口：
  - `create.php` 应稳定在快速返回
  - `status.php`/`status_batch.php` 仅查本地库

## 压测建议

### 第一轮

- 目标：验证 `200 在线 / 100 同时生成`
- 关注：
  - `create.php` P95
  - `status_batch.php` QPS
  - Redis 队列峰值长度
  - Worker 单分钟吞吐
  - Provider 提交/查询失败率

### 调参顺序

1. 先调 `submit_concurrency`
2. 再调 Worker 进程数
3. 再看是否需要继续扩 API key
4. 最后再考虑拆更多机器

## 回滚方案

- 停掉三类 Worker
- 将前端轮询恢复到单接口前，先保留 `status_batch.php` 不影响兼容
- 若需完全回滚为同步链路，再恢复旧版 `create.php` / `status.php`
