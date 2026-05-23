<?php
declare (strict_types = 1);

namespace app\common\cache;

use think\facade\Cache;

/**
 * 前台内容缓存。
 */
class IndexContentCache
{
    private const PREFIX = 'index_content:';
    private const INDEX_KEY = self::PREFIX . 'keys';
    private const DEFAULT_TTL = 600;

    /**
     * 读取缓存，不存在时执行回调并写入缓存。
     */
    public static function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL): mixed
    {
        $cacheKey = self::formatKey($key);
        $value = Cache::remember($cacheKey, $callback, $ttl);

        self::rememberKey($cacheKey);

        return $value;
    }

    /**
     * 清理单个缓存。
     */
    public static function clear(string $key): void
    {
        $cacheKey = self::formatKey($key);
        Cache::delete($cacheKey);
        self::forgetKey($cacheKey);
    }

    /**
     * 按前缀清理缓存。
     */
    public static function clearByPrefix(string $prefix): void
    {
        $cachePrefix = self::formatKey($prefix);

        foreach (self::keys() as $cacheKey) {
            if (!str_starts_with($cacheKey, $cachePrefix)) {
                continue;
            }

            Cache::delete($cacheKey);
            self::forgetKey($cacheKey);
        }
    }

    /**
     * 清理全部前台内容缓存。
     */
    public static function clearAll(): void
    {
        foreach (self::keys() as $cacheKey) {
            Cache::delete($cacheKey);
        }

        Cache::delete(self::INDEX_KEY);
    }

    /**
     * 清理导航缓存。
     */
    public static function clearNavigation(?string $group = null): void
    {
        if ($group === null || $group === '') {
            self::clearByPrefix('navigation:');
            return;
        }

        self::clear('navigation:' . $group);
    }

    /**
     * 清理分类缓存。
     */
    public static function clearCategory(?string $type = null): void
    {
        if ($type === null || $type === '') {
            self::clearByPrefix('category:');
            return;
        }

        self::clear('category:' . $type);
    }

    /**
     * 清理幻灯缓存。
     */
    public static function clearBanner(?string $position = null): void
    {
        if ($position === null || $position === '') {
            self::clearByPrefix('banner:');
            return;
        }

        self::clear('banner:' . $position);
    }

    /**
     * 清理内容列表缓存。
     */
    public static function clearContentList(?string $type = null): void
    {
        if ($type === null || $type === '') {
            self::clearByPrefix('list:');
            return;
        }

        self::clearByPrefix('list:' . $type . ':');
    }

    /**
     * 获取当前记录的前台内容缓存键数量。
     */
    public static function count(): int
    {
        return count(self::keys());
    }

    /**
     * 格式化缓存键。
     */
    private static function formatKey(string $key): string
    {
        return str_starts_with($key, self::PREFIX) ? $key : self::PREFIX . ltrim($key, ':');
    }

    /**
     * 记录缓存键，用于兼容不支持前缀删除的缓存驱动。
     */
    private static function rememberKey(string $cacheKey): void
    {
        if ($cacheKey === self::INDEX_KEY) {
            return;
        }

        $keys = self::keys();
        $keys[] = $cacheKey;
        Cache::set(self::INDEX_KEY, array_values(array_unique($keys)));
    }

    /**
     * 移除缓存键记录。
     */
    private static function forgetKey(string $cacheKey): void
    {
        $keys = array_values(array_filter(
            self::keys(),
            fn (string $item) => $item !== $cacheKey
        ));

        if ($keys) {
            Cache::set(self::INDEX_KEY, $keys);
            return;
        }

        Cache::delete(self::INDEX_KEY);
    }

    /**
     * 获取已记录的缓存键。
     */
    private static function keys(): array
    {
        $keys = Cache::get(self::INDEX_KEY, []);

        return is_array($keys) ? $keys : [];
    }
}
