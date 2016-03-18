<?php namespace CoasterCms\Helpers;

class EloquentBuilder extends \Illuminate\Database\Eloquent\Builder
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

    /**
     * Get the underlying query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function getQuery()
    {
        if (config('coaster::site.master_db_prefix') && (in_array($this->query->from, $this->_useMaster))) {
            $this->query->getConnection()->setTablePrefix(config('coaster::site.master_db_prefix'));
        } else {
            $defaultPrefix = config('database.connections.'.config('database.default').'.prefix');
            $this->query->getConnection()->setTablePrefix($defaultPrefix);
        }
        return $this->query;
    }
}