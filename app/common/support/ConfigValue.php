<?php
declare (strict_types = 1);

namespace app\common\support;

use app\common\model\SystemConfig;
use app\common\model\SystemConfigGroup;
use app\common\model\SystemConfigTab;
use think\facade\Cache;
use think\facade\Config;

/**
 * 系统配置读取工具。
 */
class ConfigValue
{
    private const CACHE_KEY = 'system_config_values';

    /**
     * 按配置分组获取原始配置值。
     */
    public static function getInGroups(string $key, array $groupKeys, mixed $default = ''): mixed
    {
        $allowedGroups = array_flip($groupKeys);

        foreach (self::rows() as $config) {
            if ((string) $config['key'] !== $key || !isset($allowedGroups[(string) $config['group_key']])) {
                continue;
            }

            return self::cast((string) $config['value'], (string) $config['type']);
        }

        return $default;
    }

    /**
     * 获取指定配置分组下的全部配置。
     */
    public static function allInGroups(array $groupKeys): array
    {
        $allowedGroups = array_flip($groupKeys);
        $result = [];

        foreach (self::rows() as $config) {
            if (!isset($allowedGroups[(string) $config['group_key']])) {
                continue;
            }

            $result[(string) $config['key']] = self::cast((string) $config['value'], (string) $config['type']);
        }

        return $result;
    }

    /**
     * 按配置分组注入到 ThinkPHP 配置容器。
     */
    public static function loadGroupsToConfig(string $name, array $groupKeys): void
    {
        Config::set(self::allInGroups($groupKeys), $name);
    }

    /**
     * 清理配置缓存。
     */
    public static function clear(): void
    {
        Cache::delete(self::CACHE_KEY);
    }

    /**
     * 获取全部启用配置。
     */
    private static function rows(): array
    {
        return Cache::remember(self::CACHE_KEY, function () {
            $groupMap = SystemConfigGroup::where('status', 1)->column('key', 'id');
            $tabIds = SystemConfigTab::where('status', 1)->column('id');
            $configs = SystemConfig::where('status', 1)
                ->whereIn('tab_id', $tabIds ?: [0])
                ->field('key,value,type,group_id')
                ->order('group_id', 'asc')
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            $rows = [];

            foreach ($configs as $config) {
                $groupKey = (string) ($groupMap[(int) $config['group_id']] ?? '');
                if ($groupKey === '') {
                    continue;
                }

                $config['group_key'] = $groupKey;
                $rows[] = $config;
            }

            return $rows;
        });
    }

    /**
     * 按配置类型转换值。
     */
    private static function cast(string $value, string $type): mixed
    {
        if (in_array($type, ['number', 'slider', 'rate'], true)) {
            return (int) $value;
        }

        if ($type === 'switch') {
            return $value === '1';
        }

        if (in_array($type, ['checkbox', 'select_multiple', 'daterange', 'datetimerange', 'timerange', 'images', 'files'], true)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $value;
    }
}
