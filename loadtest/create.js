import http from 'k6/http';
import { check, sleep, fail } from 'k6';

function parseStages() {
  const raw = __ENV.APP_STAGES || '[{"duration":"30s","target":10},{"duration":"1m","target":20},{"duration":"30s","target":0}]';
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (err) {
    fail(`APP_STAGES 不是合法 JSON: ${err.message}`);
  }
}

export const options = {
  stages: parseStages(),
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<1500'],
  },
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'max'],
};

const BASE_URL = (__ENV.BASE_URL || 'http://39.106.59.118:8081').replace(/\/+$/, '');
const COOKIE = (__ENV.COOKIE || '').trim();
const TYPE = (__ENV.TYPE || 'image').trim();
const MODEL = (__ENV.MODEL || 'banana').trim();
const PROMPT = (__ENV.PROMPT || '一个极简风格的现代客厅，柔和自然光，高级感').trim();
const ASPECT_RATIO = (__ENV.ASPECT_RATIO || (TYPE === 'video' ? '16:9' : '3:4')).trim();
const QUALITY = (__ENV.QUALITY || (TYPE === 'video' ? 'standard' : '2k')).trim();
const COUNT = Math.max(1, Math.min(4, Number(__ENV.COUNT || (TYPE === 'video' ? 1 : 1))));
const DURATION = Math.max(1, Math.min(30, Number(__ENV.DURATION || 5)));
const SLEEP_SECONDS = Math.max(0, Number(__ENV.SLEEP_SECONDS || 5));
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

function buildPayload() {
  const payload = {
    type: TYPE,
    model: MODEL,
    prompt: PROMPT,
    aspectRatio: ASPECT_RATIO,
    quality: QUALITY,
    count: COUNT,
  };

  if (TYPE === 'video') {
    payload.duration = DURATION;
  }

  return payload;
}

export default function () {
  const res = http.post(
    `${BASE_URL}/api/generation/create.php`,
    JSON.stringify(buildPayload()),
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
    'create status=200': (r) => r.status === 200,
    'create response json': () => contentType.includes('application/json'),
    'create success=true': () => !!body && body.success === true,
    'create has taskId': () => !!body && !!body.data && typeof body.data.taskId === 'string' && body.data.taskId.length > 0,
  });

  sleep(SLEEP_SECONDS);
}
