<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use app\common\service\OperateLogService;
use Closure;
use think\Request;
use think\Response;

class OperateLog
{
    /**
     * 记录后台写操作日志。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        if ($this->shouldRecord($request)) {
            (new OperateLogService())->record(
                $request,
                $response->getCode(),
                microtime(true) - $start,
                (string) $response->getContent()
            );
        }

        return $response;
    }

    /**
     * 判断当前请求是否需要记录操作日志。
     */
    private function shouldRecord(Request $request): bool
    {
        return !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }
}
