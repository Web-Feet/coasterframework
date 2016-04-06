<?php namespace CoasterCms\Libraries\Builder;

class AssetBuilder
{

    private static $styles;
    private static $scripts;

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
        self::$styles[$group] = $styleClass;
        self::$scripts[$group] = $scriptsClass;

        foreach ($assets as $asset) {
            if (pathinfo($asset, PATHINFO_EXTENSION) == 'css') {
                self::$styles[$group]->assets[] = $asset;
            } else {
                self::$scripts[$group]->assets[] = $asset;
            }
        }

    }

    public static function add($group, $assets = [])
    {
        if (!is_array($assets)) {
            $assets = [$assets];
        }

        if (empty(self::$styles[$group])) {
            $styleClass = new \stdClass;
            $styleClass->active = true;
            $styleClass->assets = [];
            self::$styles[$group] = $styleClass;
        }
        if (empty(self::$scripts[$group])) {
            $scriptsClass = new \stdClass;
            $scriptsClass->active = true;
            $scriptsClass->assets = [];
            self::$scripts[$group] = $scriptsClass;
        }

        foreach ($assets as $asset) {
            if (pathinfo($asset, PATHINFO_EXTENSION) == 'css') {
                self::$styles[$group]->assets[] = $asset;
            } else {
                self::$scripts[$group]->assets[] = $asset;
            }
        }
    }

    public static function remove($group, $assets = [])
    {
        if (!is_array($assets)) {
            $assets = [$assets];
        }

        if (!empty(self::$styles[$group])) {
            foreach ($assets as $asset) {
                if (($assetPos = array_search($asset, self::$styles[$group]->assets)) !== false) {
                    unset(self::$styles[$group]->assets[$assetPos]);
                }
            }
        }
        if (!empty(self::$scripts[$group])) {
            foreach ($assets as $asset) {
                if (($assetPos = array_search($asset, self::$scripts[$group]->assets)) !== false) {
                    unset(self::$scripts[$group]->assets[$assetPos]);
                }
            }
        }
    }

    public static function setStatus($group, $active = true)
    {
        if (isset(self::$styles[$group])) {
            self::$styles[$group]->active = $active;
        }
        if (isset(self::$scripts[$group])) {
            self::$scripts[$group]->active = $active;
        }
    }

    public static function styles()
    {
        $usedAssets = [];
        if (!empty(self::$styles)) {
            uasort(self::$styles, function ($a, $b) {
                if ($a->order == $b->order) {
                    return 1;
                }
                return ($a->order > $b->order) ? 1 : -1;
            });
            foreach (self::$styles as $group) {
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

    public static function scripts()
    {
        $usedAssets = [];
        if (!empty(self::$scripts)) {
            uasort(self::$scripts, function ($a, $b) {
                if ($a->order == $b->order) {
                    return 1;
                }
                return ($a->order > $b->order) ? 1 : -1;
            });
            foreach (self::$scripts as $group) {
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