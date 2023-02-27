<?php

namespace EasyAdmin\upload\driver;

use EasyAdmin\upload\FileBase;
use EasyAdmin\upload\trigger\SaveDb;

/**
 * 本地上传
 * Class Local
 * @package EasyAdmin\upload\driver
 */
class Local extends FileBase
{

    /**
     * 重写上传方法
     * @return array|void
     */
    public function save()
    {
        parent::save();

        $save_db = [
            'upload_type'   => $this->uploadType,
            'original_name' => $this->file->getOriginalName(),
            'mime_type'     => $this->file->getOriginalMime(),
            'file_ext'      => strtolower($this->file->getOriginalExtension()),
            'url'           => $this->completeFileUrl,
            'create_time'   => time(),
        ];
        SaveDb::trigger($this->tableName, $save_db);

        $rs = [
            'url'           => $save_db['url'],
            'msg'           => '上传成功',
            'save'          => true,
            'original_name' => $save_db['original_name'],
            
        ];

        // 判断是否下发文件数据
        // ($save_db['file_ext'] == 'json') && $rs['file_data'] = json_decode(file_get_contents($save_db['url']), true);
        ($save_db['file_ext'] == 'json') && $rs['file_data'] = file_get_contents($save_db['url']);

        return $rs;
    }

}