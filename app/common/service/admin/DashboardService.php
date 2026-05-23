<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\UploadFile;
use app\common\model\AdminLoginLog;
use app\common\model\AdminMenu;
use app\common\model\AdminOperateLog;
use app\common\model\AdminRole;
use app\common\model\AdminUser;

class DashboardService
{
    /**
     * 获取控制台统计数据。
     */
    public function summary(): array
    {
        return [
            'cards'          => $this->cards(),
            'login_trend'    => $this->loginTrend(),
            'file_types'     => $this->fileTypes(),
            'recent_logins'  => $this->recentLogins(),
            'recent_operates'=> $this->recentOperates(),
            'server'         => [
                'app'  => 'admin',
                'time' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * 获取核心数量卡片。
     */
    private function cards(): array
    {
        $todayStart = date('Y-m-d 00:00:00');

        return [
            'users'          => AdminUser::where([])->count(),
            'roles'          => AdminRole::where([])->count(),
            'menus'          => AdminMenu::where([])->count(),
            'files'          => UploadFile::where([])->count(),
            'today_logins'   => AdminLoginLog::where('create_time', '>=', $todayStart)->count(),
            'today_operates' => AdminOperateLog::where('create_time', '>=', $todayStart)->count(),
        ];
    }

    /**
     * 获取近 7 天登录趋势。
     */
    private function loginTrend(): array
    {
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $trend[$date] = 0;
        }

        $rows = AdminLoginLog::where('create_time', '>=', array_key_first($trend) . ' 00:00:00')
            ->fieldRaw('DATE(create_time) AS date, COUNT(*) AS total')
            ->group('date')
            ->select()
            ->toArray();

        foreach ($rows as $row) {
            $date = (string) $row['date'];

            if (isset($trend[$date])) {
                $trend[$date] = (int) $row['total'];
            }
        }

        return array_map(
            fn (string $date, int $total) => ['date' => $date, 'total' => $total],
            array_keys($trend),
            array_values($trend)
        );
    }

    /**
     * 获取文件类型统计。
     */
    private function fileTypes(): array
    {
        return UploadFile::where([])
            ->fieldRaw('extension, COUNT(*) AS total')
            ->group('extension')
            ->orderRaw('total DESC')
            ->limit(8)
            ->select()
            ->toArray();
    }

    /**
     * 获取最近登录日志。
     */
    private function recentLogins(): array
    {
        return AdminLoginLog::where([])
            ->field('id,username,ip,status,message,create_time')
            ->order('id', 'desc')
            ->limit(6)
            ->select()
            ->toArray();
    }

    /**
     * 获取最近操作日志。
     */
    private function recentOperates(): array
    {
        return AdminOperateLog::where([])
            ->field('id,username,method,path,status_code,duration_ms,create_time')
            ->order('id', 'desc')
            ->limit(6)
            ->select()
            ->toArray();
    }
}
