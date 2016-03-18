<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use CoasterCms\Helpers\EloquentBuilder;

abstract class _BaseEloquent extends Eloquent
{
    private $_useMaster = [
        'admin_actions',
        'admin_controllers',
        'admin_logs',
        'admin_menu',
        'backups',
        'blocks',
        'block_category',
        'block_form_rules',
        'block_selectopts',
        'block_repeaters',
        'block_video_cache',
        'languages',
        'templates',
        'template_blocks',
        'themes',
        'theme_blocks',
        'users',
        'user_roles'
    ];

    public function _setPrefix()
    {
        $connections = app('db')->getconnections();
        $connection = $connections[config('database.default')];

        if (config('coaster::site.master_db_prefix') && (in_array($this->table, $this->_useMaster))) {
            $connection->setTablePrefix(config('coaster::site.master_db_prefix'));
        } else {
            $defaultPrefix = config('database.connections.'.config('database.default').'.prefix');
            $connection->setTablePrefix($defaultPrefix);
        }

    }

    public function newQuery()
    {
        $this->_setPrefix();
        return  parent::newQuery();
    }

    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }

    public function getDatabasePrefix()
    {
        if ($this->multiSiteUseMaster || in_array($this->table, $this->_useMaster)) {
            return config('coaster::site.master_db_prefix');
        } else {
            return config('database.connections.'.config('database.default').'.prefix');
        }
    }

}