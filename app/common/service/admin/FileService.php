<?php
declare (strict_types = 1);

namespace app\common\service\admin;

use app\common\model\UploadFile;
use app\common\support\ConfigValue;
use RuntimeException;
use think\facade\Filesystem;
use think\file\UploadedFile;

class FileService
{
    /**
     * 获取上传文件分页列表。
     */
    public function page(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $extension = strtolower(trim((string) ($filters['extension'] ?? '')));
        $category = strtolower(trim((string) ($filters['category'] ?? '')));
        $scene = $this->normalizeScene((string) ($filters['scene'] ?? ''));

        $query = UploadFile::where([]);
        $this->applyDataScope($query, $filters);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->whereLike('original_name', '%' . $keyword . '%')
                    ->whereOr('path', 'like', '%' . $keyword . '%');
            });
        }

        if ($extension !== '') {
            $query->where('extension', $extension);
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($scene !== '') {
            $query->where('scene', $scene);
        }

        $result = $query
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page'      => $page,
            ])
            ->toArray();

        $result['data'] = array_map(fn (array $row) => $this->formatFile($row), $result['data'] ?? []);

        return $result;
    }

    /**
     * 保存上传文件并记录文件信息。
     */
    public function upload(?UploadedFile $file, int $uploaderId = 0, ?array $allowedExtensions = null, string $scene = 'default'): array
    {
        if (!$file) {
            throw new RuntimeException(\think\facade\Lang::get('admin.select_upload_file'));
        }

        $extension = strtolower($file->getOriginalExtension());
        $allowedExtensions = $allowedExtensions ?: $this->allowedExtensions();

        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException(\think\facade\Lang::get('admin.file_type_not_allowed'));
        }

        $maxSize = $this->maxSizeMb() * 1024 * 1024;

        if ($file->getSize() > $maxSize) {
            throw new RuntimeException(\think\facade\Lang::get('admin.file_size_exceeded'));
        }

        $sha1 = sha1_file($file->getRealPath()) ?: '';
        $scene = $this->normalizeScene($scene) ?: 'default';
        $exists = $sha1 === '' ? null : UploadFile::where('sha1', $sha1)
            ->where('extension', $extension)
            ->find();

        if ($exists) {
            $disk = (string) $exists->disk;
            $path = (string) $exists->path;
            $url = (string) $exists->url;
        } else {
            $disk = 'public';
            $path = Filesystem::disk($disk)->putFile('uploads', $file);

            if (!$path) {
                throw new RuntimeException(\think\facade\Lang::get('admin.save_file_failed'));
            }

            $url = Filesystem::disk($disk)->url($path);
        }

        $record = UploadFile::create([
            'disk'          => $disk,
            'path'          => $path,
            'url'           => $url,
            'original_name' => $file->getOriginalName(),
            'mime_type'     => $file->getOriginalMime(),
            'extension'     => $extension,
            'category'      => $this->categoryByExtension($extension),
            'scene'         => $scene,
            'size'          => $file->getSize(),
            'sha1'          => $sha1,
            'uploader_id'   => $uploaderId,
        ]);

        return $this->formatFile($record->toArray());
    }

    /**
     * 删除上传文件记录。
     */
    public function delete(int $id, int $operatorId = 0, string $dataScope = 'all'): void
    {
        $file = $this->findFile($id, $operatorId, $dataScope);
        $disk = (string) $file->disk;
        $path = (string) $file->path;
        $referenceCount = UploadFile::where('disk', $disk)
            ->where('path', $path)
            ->where('id', '<>', $id)
            ->count();

        $file->delete();

        if ($referenceCount <= 0) {
            try {
                Filesystem::disk($disk)->delete($path);
            } catch (\Throwable) {
            }
        }
    }

    /**
     * 获取删除前的物理文件引用信息。
     */
    public function deleteInfo(int $id, int $operatorId = 0, string $dataScope = 'all'): array
    {
        $file = $this->findFile($id, $operatorId, $dataScope);
        $referenceCount = UploadFile::where('disk', (string) $file->disk)
            ->where('path', (string) $file->path)
            ->where('id', '<>', $id)
            ->count();

        return [
            'reference_count'     => (int) $referenceCount,
            'will_delete_physical'=> $referenceCount <= 0 ? 1 : 0,
        ];
    }

    /**
     * 批量删除上传文件记录和物理文件。
     */
    public function batchDelete(array $ids, int $operatorId = 0, string $dataScope = 'all'): void
    {
        $ids = $this->filterIds($ids);

        if (!$ids) {
            throw new RuntimeException(\think\facade\Lang::get('admin.select_files'));
        }

        foreach ($ids as $id) {
            $this->delete($id, $operatorId, $dataScope);
        }
    }

    /**
     * 重命名上传文件原始名称。
     */
    public function rename(int $id, string $name, int $operatorId = 0, string $dataScope = 'all'): array
    {
        $name = trim($name);
        $file = $this->findFile($id, $operatorId, $dataScope);

        if ($name === '') {
            throw new RuntimeException(\think\facade\Lang::get('admin.file_name_required'));
        }

        $file->save(['original_name' => $name]);

        return $this->formatFile($file->toArray());
    }

    /**
     * 获取允许上传的扩展名。
     */
    private function allowedExtensions(): array
    {
        $value = (string) ConfigValue::getInGroups('upload_ext', ['system'], 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx');

        return array_values(array_filter(array_map(
            fn (string $extension) => strtolower(trim($extension)),
            explode(',', $value)
        )));
    }

    /**
     * 获取上传大小限制。
     */
    private function maxSizeMb(): int
    {
        return max(1, (int) ConfigValue::getInGroups('upload_max_size', ['system'], 10));
    }

    /**
     * 过滤批量操作 ID。
     */
    private function filterIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        return array_values(array_filter($ids, fn (int $id) => $id > 0));
    }

    /**
     * 根据数据权限上下文限制文件查询范围。
     */
    private function applyDataScope(mixed $query, array $filters): void
    {
        if (($filters['_data_scope'] ?? 'all') === 'self') {
            $query->where('uploader_id', (int) ($filters['_admin_id'] ?? 0));
        }
    }

    /**
     * 查找文件并校验当前管理员是否可操作。
     */
    private function findFile(int $id, int $operatorId = 0, string $dataScope = 'all'): UploadFile
    {
        $query = UploadFile::where('id', $id);

        if ($dataScope === 'self') {
            $query->where('uploader_id', $operatorId);
        }

        $file = $query->find();

        if (!$file) {
            throw new RuntimeException(\think\facade\Lang::get('admin.file_not_found'));
        }

        return $file;
    }

    /**
     * 根据扩展名自动识别文件分类。
     */
    private function categoryByExtension(string $extension): string
    {
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'image';
        }

        if (in_array($extension, ['doc', 'docx', 'pdf', 'txt', 'md'], true)) {
            return 'document';
        }

        if (in_array($extension, ['xls', 'xlsx', 'csv'], true)) {
            return 'sheet';
        }

        if (in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'], true)) {
            return 'archive';
        }

        return 'other';
    }

    /**
     * 规范化上传场景标识，保留业务可扩展字符串。
     */
    private function normalizeScene(string $scene): string
    {
        $scene = strtolower(trim($scene));

        if ($scene === '') {
            return '';
        }

        return preg_match('/^[a-z][a-z0-9_:-]{0,49}$/', $scene) ? $scene : 'default';
    }

    /**
     * 组装前端需要的文件结构。
     */
    private function formatFile(array $file): array
    {
        if (empty($file['category'])) {
            $file['category'] = $this->categoryByExtension(strtolower((string) ($file['extension'] ?? '')));
        }

        if (empty($file['scene'])) {
            $file['scene'] = 'default';
        }

        return $file;
    }
}
