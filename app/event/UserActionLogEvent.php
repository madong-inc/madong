<?php

namespace app\event;

use app\services\system\SystemLoginLogService;
use app\services\system\SystemMenuService;
use app\services\system\SystemOperateLogService;
use support\Container;

class UserActionLogEvent
{
    /**
     * 登录日志
     *
     * @param $item
     */
    public function logLogin($item): void
    {
        $token                = $item['token'];
        $key                  = 'token_' . $item['type'] . '_' . md5($token['token'] ?? '');
        $ip                   = request()->getRealIp();
        $http_user_agent      = request()->header('user-agent');
        $data['user_name']    = $item['username'];
        $data['ip']           = $ip;
        $data['ip_location']  = '未知';
        $data['os']           = self::getOs($http_user_agent);
        $data['browser']      = self::getBrowser($http_user_agent);
        $data['status']       = $item['status'];
        $data['message']      = $item['message'];
        $data['login_time']   = time();
        $data['key']          = $key;
        $data['expires_time'] = $token['params']['exp'] ?? time();
        $service              = new SystemLoginLogService();
        $service->save($data);
    }

    /**
     * 记录操作日志
     */
    public function logAction($response): bool
    {
        $data    = [
            'name'        => $this->getName(),
            'app'         => request()->app,
            'ip'          => request()->getRealIp(),
            'ip_location' => '未知',
            'browser'     => $this->getBrowser(request()->header('user-agent')),
            'os'          => $this->getOs(request()->header('user-agent')),
            'url'         => trim(request()->path()),
            'class_name'  => request()->controller,
            'action'      => request()->action,
            'method'      => request()->method(),
            'param'       => $this->filterParams(request()->all()),
            'result'      => $response->rawBody(),
            'create_time' => time(),
            'user_name'   => $info['user_name'] ?? '',
        ];
        $service = new SystemOperateLogService();
        $service->save($data);
        return true;
    }

    protected function getName(): string
    {
        $path = request()->route->getPath();
        if (preg_match("/\{[^}]+\}/", $path)) {
            $path = rtrim(preg_replace("/\{[^}]+\}/", '', $path), '/');
        }
        $systemMenuService = Container::make(SystemMenuService::class);
        $menu              = $systemMenuService->get(['path' => $path]);
        if (!empty($menu)) {
            return $menu->getData('title');
        } else {
            return '未知';
        }
    }

    /**
     * 过滤字段
     */
    protected function filterParams($params): string
    {
        $blackList = ['password', 'oldPassword', 'newPassword', 'content'];
        foreach ($params as $key => $value) {
            if (in_array($key, $blackList)) {
                $params[$key] = '******';
            }
        }
        return json_encode($params, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 浏览器名称
     *
     * @param $user_agent
     *
     * @return string
     */
    protected function getBrowser($user_agent): string
    {
        $br = 'Unknown';
        if (preg_match('/MSIE/i', $user_agent)) {
            $br = 'MSIE';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $br = 'Firefox';
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            $br = 'Chrome';
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $br = 'Safari';
        } elseif (preg_match('/Opera/i', $user_agent)) {
            $br = 'Opera';
        } else {
            $br = 'Other';
        }
        return $br;
    }

    /**
     * 系统名称
     *
     * @param $user_agent
     *
     * @return string
     */
    protected function getOs($user_agent): string
    {
        $os = 'Unknown';
        if (preg_match('/win/i', $user_agent)) {
            $os = 'Windows';
        } elseif (preg_match('/mac/i', $user_agent)) {
            $os = 'Mac';
        } elseif (preg_match('/linux/i', $user_agent)) {
            $os = 'Linux';
        } else {
            $os = 'Other';
        }
        return $os;
    }
}
