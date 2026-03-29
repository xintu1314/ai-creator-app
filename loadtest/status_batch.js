import http from 'k6/http';
import { check, sleep, fail } from 'k6';

function parseStages() {
  const raw = __ENV.APP_STAGES || '[{"duration":"30s","target":20},{"duration":"1m","target":50},{"duration":"30s","target":0}]';
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (err) {
    fail(`APP_STAGES 不是合法 JSON: ${err.message}`);
  }
}

function parseTaskIds() {
  const raw = (__ENV.TASK_IDS || '').trim();
  if (!raw) {
    fail('缺少 TASK_IDS 环境变量，请传入逗号分隔的 taskId 列表');
  }
  const ids = raw.split(',').map((item) => item.trim()).filter(Boolean);
  if (!ids.length) {
    fail('TASK_IDS 解析后为空');
  }
  return ids;
}

export const options = {
  stages: parseStages(),
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<800'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'max'],
};

const BASE_URL = (__ENV.BASE_URL || 'http://39.106.59.118:8081').replace(/\/+$/, '');
const COOKIE = (__ENV.COOKIE || '').trim();
const TASK_IDS = parseTaskIds();
const SLEEP_SECONDS = Math.max(0, Number(__ENV.SLEEP_SECONDS || 2));
const DEBUG_RESPONSE = (__ENV.DEBUG_RESPONSE || '0') === '1';

function buildHeaders() {
  if (!COOKIE) {
    fail('缺少 COOKIE 环境变量，请先从浏览器复制登录态 Cookie');
  }
  return {
    'Content-Type': 'application/json',
    'Cookie': COOKIE,
  };
}

export default function () {
  const res = http.post(
    `${BASE_URL}/api/generation/status_batch.php`,
    JSON.stringify({ taskIds: TASK_IDS }),
    { headers: buildHeaders() }
  );

  const contentType = (res.headers['Content-Type'] || '').toLowerCase();
  let body = null;
  if (contentType.includes('application/json')) {
    body = res.json();
  } else if (DEBUG_RESPONSE) {
    console.log(`non-json response status=${res.status} body=${String(res.body || '').slice(0, 500)}`);
  }

  check(res, {
    'status_batch status=200': (r) => r.status === 200,
    'status_batch response json': () => contentType.includes('application/json'),
    'status_batch success=true': () => !!body && body.success === true,
    'status_batch has items': () => !!body && !!body.data && Array.isArray(body.data.items),
  });

  sleep(SLEEP_SECONDS);
}
