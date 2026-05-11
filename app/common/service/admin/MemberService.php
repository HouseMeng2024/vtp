<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\Member;
use RuntimeException;

class MemberService
{
    /**
     * 获取会员分页列表。
     */
    public function page(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $status = $filters['status'] ?? '';

        $query = Member::where([]);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('username', '%' . $keyword . '%')
                    ->whereOr('nickname', 'like', '%' . $keyword . '%')
                    ->whereOr('mobile', 'like', '%' . $keyword . '%')
                    ->whereOr('email', 'like', '%' . $keyword . '%');
            });
        }

        if ($status !== '' && $status !== null) {
            $query->where('status', (int) $status);
        }

        return $query
            ->field('id,username,nickname,avatar,mobile,email,gender,birthday,status,register_ip,register_time,last_login_ip,last_login_time,remark,create_time')
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();
    }

    /**
     * 创建会员。
     */
    public function create(array $data): array
    {
        $payload = $this->filterPayload($data, true);
        $password = (string) ($data['password'] ?? '');

        if ($password === '') {
            throw new RuntimeException('请输入密码');
        }

        if (strlen($password) < 6) {
            throw new RuntimeException('密码至少 6 位');
        }

        $payload['password'] = password_hash($password, PASSWORD_BCRYPT);
        $payload['register_ip'] = trim((string) ($data['register_ip'] ?? ''));
        $payload['register_time'] = date('Y-m-d H:i:s');

        $this->assertUnique($payload);

        return Member::create($payload)->hidden(['password'])->toArray();
    }

    /**
     * 获取会员详情。
     */
    public function detail(int $id): array
    {
        return $this->findMember($id)->hidden(['password'])->toArray();
    }

    /**
     * 更新会员资料。
     */
    public function update(int $id, array $data): array
    {
        $member = $this->findMember($id);
        $data['username'] = $member->username;
        $payload = $this->filterPayload($data, false);
        $this->assertUnique($payload, $id);
        $member->save($payload);

        return $this->findMember($id)->hidden(['password'])->toArray();
    }

    /**
     * 修改会员启用状态。
     */
    public function changeStatus(int $id, int $status): array
    {
        $member = $this->findMember($id);
        $member->save(['status' => $status === 1 ? 1 : 0]);

        return $member->hidden(['password'])->toArray();
    }

    /**
     * 批量修改会员启用状态。
     */
    public function batchChangeStatus(array $ids, int $status): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException('请选择会员');
        }

        Member::whereIn('id', $ids)->update([
            'status' => $status === 1 ? 1 : 0,
        ]);
    }

    /**
     * 重置会员密码。
     */
    public function resetPassword(int $id, string $password): void
    {
        if ($password === '') {
            throw new RuntimeException('请输入新密码');
        }

        if (strlen($password) < 6) {
            throw new RuntimeException('新密码至少 6 位');
        }

        $this->findMember($id)->save([
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    /**
     * 删除会员。
     */
    public function delete(int $id): void
    {
        $this->findMember($id)->delete();
    }

    /**
     * 批量删除会员。
     */
    public function batchDelete(array $ids): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException('请选择会员');
        }

        foreach ($ids as $id) {
            $this->findMember($id)->delete();
        }
    }

    /**
     * 查找会员，不存在时抛出业务异常。
     */
    private function findMember(int $id): Member
    {
        $member = Member::find($id);

        if (!$member) {
            throw new RuntimeException('会员不存在');
        }

        return $member;
    }

    /**
     * 过滤并校验会员表单数据。
     */
    private function filterPayload(array $data, bool $isCreate): array
    {
        $username = trim((string) ($data['username'] ?? ''));
        $nickname = trim((string) ($data['nickname'] ?? ''));
        $mobile = trim((string) ($data['mobile'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $birthday = trim((string) ($data['birthday'] ?? ''));

        if ($username === '') {
            throw new RuntimeException('请输入账号');
        }

        if ($mobile !== '' && !preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            throw new RuntimeException('手机号格式不正确');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('邮箱格式不正确');
        }

        if ($nickname === '') {
            $nickname = $username;
        }

        $payload = [
            'nickname' => $nickname,
            'avatar'   => trim((string) ($data['avatar'] ?? '')),
            'mobile'   => $mobile,
            'email'    => $email,
            'gender'   => in_array((int) ($data['gender'] ?? 0), [0, 1, 2], true) ? (int) ($data['gender'] ?? 0) : 0,
            'birthday' => $birthday !== '' ? $birthday : null,
            'status'   => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
            'remark'   => trim((string) ($data['remark'] ?? '')),
        ];

        if ($isCreate) {
            $payload['username'] = $username;
        }

        return $payload;
    }

    /**
     * 校验账号、手机号、邮箱唯一性，空值不参与校验。
     */
    private function assertUnique(array $payload, int $ignoreId = 0): void
    {
        foreach (['username' => '账号', 'mobile' => '手机号', 'email' => '邮箱'] as $field => $label) {
            if (($payload[$field] ?? '') === '') {
                continue;
            }

            $query = Member::where($field, $payload[$field]);

            if ($ignoreId > 0) {
                $query->where('id', '<>', $ignoreId);
            }

            if ($query->find()) {
                throw new RuntimeException($label . '已存在');
            }
        }
    }

    /**
     * 过滤批量操作 ID。
     */
    private function filterIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        return array_values(array_filter($ids, fn (int $id) => $id > 0));
    }
}
