<?php
declare (strict_types = 1);

namespace app\common\middleware;

use Closure;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Lang;
use think\Request;
use think\Response;

class Locale
{
    /**
     * 按 ThinkPHP 语言配置识别当前请求语言。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lang = $this->detect($request);

        Lang::switchLangSet($lang);

        if ((bool) Config::get('lang.use_cookie', true)) {
            Cookie::set((string) Config::get('lang.cookie_var', 'think_lang'), $lang);
        }

        return $next($request);
    }

    /**
     * 从 query、header、cookie 和浏览器语言中解析可用语言。
     */
    private function detect(Request $request): string
    {
        $default = (string) Config::get('lang.default_lang', 'en-us');
        $allowList = (array) Config::get('lang.allow_lang_list', []);
        $candidates = [
            (string) $request->param((string) Config::get('lang.detect_var', 'lang'), ''),
            (string) $request->header((string) Config::get('lang.header_var', 'think-lang'), ''),
            (string) Cookie::get((string) Config::get('lang.cookie_var', 'think_lang'), ''),
            $this->browserLang($request),
            $default,
        ];

        foreach ($candidates as $candidate) {
            $lang = $this->normalize($candidate);
            if ($lang !== '' && (empty($allowList) || in_array($lang, $allowList, true))) {
                return $lang;
            }
        }

        return $default;
    }

    /**
     * 获取浏览器 Accept-Language 的首选语言。
     */
    private function browserLang(Request $request): string
    {
        if (!(bool) Config::get('lang.auto_detect_browser', true)) {
            return '';
        }

        $header = (string) $request->header('accept-language', '');
        if ($header === '') {
            return '';
        }

        return explode(',', $header)[0] ?? '';
    }

    /**
     * 归一化语言标识并应用 ThinkPHP accept_language 映射。
     */
    private function normalize(string $lang): string
    {
        $lang = strtolower(str_replace('_', '-', trim($lang)));
        if ($lang === '') {
            return '';
        }

        $map = (array) Config::get('lang.accept_language', []);

        return (string) ($map[$lang] ?? $lang);
    }
}
