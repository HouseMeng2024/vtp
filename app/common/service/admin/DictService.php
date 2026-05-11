<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\DictData;
use app\common\model\DictType;
use RuntimeException;

class DictService
{
    /**
     * 获取字典类型分页列表。
     */
    public function typePage(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));

        $query = DictType::where([]);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('name', '%' . $keyword . '%')
                    ->whereOr('type', 'like', '%' . $keyword . '%');
            });
        }

        return $query
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();
    }

    /**
     * 创建字典类型。
     */
    public function createType(array $data): array
    {
        $payload = $this->filterTypePayload($data);

        if (DictType::where('type', $payload['type'])->find()) {
            throw new RuntimeException('字典标识已存在');
        }

        return DictType::create($payload)->toArray();
    }

    /**
     * 更新字典类型。
     */
    public function updateType(int $id, array $data): array
    {
        $type = $this->findType($id);
        $payload = $this->filterTypePayload($data);

        if (DictType::where('type', $payload['type'])->where('id', '<>', $id)->find()) {
            throw new RuntimeException('字典标识已存在');
        }

        $type->save($payload);

        return $type->toArray();
    }

    /**
     * 修改字典类型状态。
     */
    public function changeTypeStatus(int $id, int $status): array
    {
        $type = $this->findType($id);
        $type->save(['status' => $status === 1 ? 1 : 0]);

        return $type->toArray();
    }

    /**
     * 删除字典类型和字典项。
     */
    public function deleteType(int $id): void
    {
        $type = $this->findType($id);
        $type->delete();
        DictData::where('type_id', $id)->delete();
    }

    /**
     * 获取字典项分页列表。
     */
    public function dataPage(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $typeId = (int) ($filters['type_id'] ?? 0);
        $keyword = trim((string) ($filters['keyword'] ?? ''));

        $query = DictData::where([]);

        if ($typeId > 0) {
            $query->where('type_id', $typeId);
        }

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('label', '%' . $keyword . '%')
                    ->whereOr('value', 'like', '%' . $keyword . '%');
            });
        }

        return $query
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();
    }

    /**
     * 根据字典标识获取启用的字典选项。
     */
    public function options(string $type): array
    {
        $dictType = DictType::where('type', $type)->where('status', 1)->find();

        if (!$dictType) {
            return [];
        }

        return DictData::where('type_id', (int) $dictType->id)
            ->where('status', 1)
            ->field('label,value,tag_type,sort')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取启用的字典类型选项。
     */
    public function typeOptions(): array
    {
        return DictType::where('status', 1)
            ->field('id,name,type')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 创建字典项。
     */
    public function createData(array $data): array
    {
        $payload = $this->filterDataPayload($data);

        if (DictData::where('type_id', $payload['type_id'])->where('value', $payload['value'])->find()) {
            throw new RuntimeException('字典值已存在');
        }

        return DictData::create($payload)->toArray();
    }

    /**
     * 更新字典项。
     */
    public function updateData(int $id, array $data): array
    {
        $dictData = $this->findData($id);
        $payload = $this->filterDataPayload($data);

        if (DictData::where('type_id', $payload['type_id'])
            ->where('value', $payload['value'])
            ->where('id', '<>', $id)
            ->find()) {
            throw new RuntimeException('字典值已存在');
        }

        $dictData->save($payload);

        return $dictData->toArray();
    }

    /**
     * 修改字典项状态。
     */
    public function changeDataStatus(int $id, int $status): array
    {
        $dictData = $this->findData($id);
        $dictData->save(['status' => $status === 1 ? 1 : 0]);

        return $dictData->toArray();
    }

    /**
     * 删除字典项。
     */
    public function deleteData(int $id): void
    {
        $this->findData($id)->delete();
    }

    /**
     * 查找字典类型。
     */
    private function findType(int $id): DictType
    {
        $type = DictType::find($id);

        if (!$type) {
            throw new RuntimeException('字典类型不存在');
        }

        return $type;
    }

    /**
     * 查找字典项。
     */
    private function findData(int $id): DictData
    {
        $data = DictData::find($id);

        if (!$data) {
            throw new RuntimeException('字典项不存在');
        }

        return $data;
    }

    /**
     * 过滤字典类型表单数据。
     */
    private function filterTypePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = trim((string) ($data['type'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('请输入字典名称');
        }

        if ($type === '') {
            throw new RuntimeException('请输入字典标识');
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $type)) {
            throw new RuntimeException('字典标识只能使用小写字母、数字、下划线，并以字母开头');
        }

        return [
            'name'   => $name,
            'type'   => $type,
            'sort'   => max(0, (int) ($data['sort'] ?? 100)),
            'status' => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
            'remark' => trim((string) ($data['remark'] ?? '')),
        ];
    }

    /**
     * 过滤字典项表单数据。
     */
    private function filterDataPayload(array $data): array
    {
        $typeId = (int) ($data['type_id'] ?? 0);
        $label = trim((string) ($data['label'] ?? ''));
        $value = trim((string) ($data['value'] ?? ''));

        $this->findType($typeId);

        if ($label === '') {
            throw new RuntimeException('请输入字典标签');
        }

        if ($value === '') {
            throw new RuntimeException('请输入字典值');
        }

        return [
            'type_id'  => $typeId,
            'label'    => $label,
            'value'    => $value,
            'tag_type' => trim((string) ($data['tag_type'] ?? '')),
            'sort'     => max(0, (int) ($data['sort'] ?? 100)),
            'status'   => (int) ($data['status'] ?? 1) === 1 ? 1 : 0,
            'remark'   => trim((string) ($data['remark'] ?? '')),
        ];
    }
}
