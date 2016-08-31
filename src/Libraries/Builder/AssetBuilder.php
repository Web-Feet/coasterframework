<?php namespace CoasterCms\Libraries\Builder;

class AssetBuilder
{

    /**
     * @var array
     */
    protected static $_styles;

    /**
     * @var array
     */
    protected static $_scripts;

    /**
     * @param string $group
     * @param string|array $assets
     * @param bool $active
     * @param int $order
     */
    public static function set($group, $assets, $active = true, $order = 1)
    {
        if (!is_array($assets)) {
            $assets = [$assets];
        }

        $styleClass = new \stdClass;
        $styleClass->active = $active;
        $styleClass->order = $order;
        $styleClass->assets = [];
        $scriptsClass = new \stdClass;
        $scriptsClass->active = $active;
        $scriptsClass->order = $order;
        $scriptsClass->assets = [];
        self::$_styles[$group] = $styleClass;
        self::$_scripts[$group] = $scriptsClass;

        foreach ($assets as $asset) {
            if (pathinfo($asset, PATHINFO_EXTENSION) == 'css') {
                self::$_styles[$group]->assets[] = $asset;
            } else {
                self::$_scripts[$group]->assets[] = $asset;
            }
        }

    }

    /**
     * @param string $group
     * @param array $assets
     */
    public static function add($group, $assets = [])
    {
        if (!is_array($assets)) {
            $assets = [$assets];
        }

        if (empty(self::$_styles[$group])) {
            $styleClass = new \stdClass;
            $styleClass->active = true;
            $styleClass->assets = [];
            self::$_styles[$group] = $styleClass;
        }
        if (empty(self::$_scripts[$group])) {
            $scriptsClass = new \stdClass;
            $scriptsClass->active = true;
            $scriptsClass->assets = [];
            self::$_scripts[$group] = $scriptsClass;
        }

        foreach ($assets as $asset) {
            if (pathinfo($asset, PATHINFO_EXTENSION) == 'css') {
                self::$_styles[$group]->assets[] = $asset;
            } else {
                self::$_scripts[$group]->assets[] = $asset;
            }
        }
    }

    /**
     * @param string $group
     * @param array $assets
     */
    public static function remove($group, $assets = [])
    {
        if (!is_array($assets)) {
            $assets = [$assets];
        }

        if (!empty(self::$_styles[$group])) {
            foreach ($assets as $asset) {
                if (($assetPos = array_search($asset, self::$_styles[$group]->assets)) !== false) {
                    unset(self::$_styles[$group]->assets[$assetPos]);
                }
            }
        }
        if (!empty(self::$_scripts[$group])) {
            foreach ($assets as $asset) {
                if (($assetPos = array_search($asset, self::$_scripts[$group]->assets)) !== false) {
                    unset(self::$_scripts[$group]->assets[$assetPos]);
                }
            }
        }
    }

    /**
     * @param string $group
     * @param bool $active
     */
    public static function setStatus($group, $active = true)
    {
        if (isset(self::$_styles[$group])) {
            self::$_styles[$group]->active = $active;
        }
        if (isset(self::$_scripts[$group])) {
            self::$_scripts[$group]->active = $active;
        }
    }

    /**
     * @return string
     */
    public static function styles()
    {
        $usedAssets = [];
        if (!empty(self::$_styles)) {
            uasort(self::$_styles, function ($a, $b) {
                if ($a->order == $b->order) {
                    return 1;
                }
                return ($a->order > $b->order) ? 1 : -1;
            });
            foreach (self::$_styles as $group) {
                if ($group->active) {
                    foreach ($group->assets as $asset) {
                        $usedAssets[] = $asset;
                    }
                }
            }
        }
        $return = '';
        foreach (array_unique($usedAssets) as $usedAsset) {
            $return .= \HTML::style(config('coaster::admin.public') . $usedAsset);
        }
        return $return;
    }

    /**
     * @return string
     */
    public static function scripts()
    {
        $usedAssets = [];
        if (!empty(self::$_scripts)) {
            uasort(self::$_scripts, function ($a, $b) {
                if ($a->order == $b->order) {
                    return 1;
                }
                return ($a->order > $b->order) ? 1 : -1;
            });
            foreach (self::$_scripts as $group) {
                if ($group->active) {
                    foreach ($group->assets as $asset) {
                        $usedAssets[] = $asset;
                    }
                }
            }
        }
        $return = '';
        foreach (array_unique($usedAssets) as $usedAsset) {
            $return .= \HTML::script(config('coaster::admin.public') . $usedAsset);
        }
        return $return;
    }

}
