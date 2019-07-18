<?php namespace CoasterCms\Console\Assets;

use CoasterCms\Helpers\Cms\File\File;

class JQueryUI extends AbstractAsset
{

    public static $name = 'jquery-ui';

    public static $version = '1.11.4';

    public static $description = 'jQuery-ui';

    public function run()
    {
        $this->downloadZip(
            'https://download.jqueryui.com/download',
            ['jquery-ui-1.11.4.custom' => ''],
            'POST',
            [
                'theme' => 'ffDefault=Trebuchet%20MS%2CTahoma%2CVerdana%2CArial%2Csans-serif&fsDefault=1.1em&fwDefault=bold&cornerRadius=2px&bgColorHeader=%23eb5b4f&bgTextureHeader=flat&borderColorHeader=%23eb5b4f&fcHeader=%23fff&iconColorHeader=%23ffffff&bgColorContent=%23fff&bgTextureContent=highlight_soft&borderColorContent=%23dddddd&fcContent=%23333333&iconColorContent=%23222222&bgColorDefault=%23fff&bgTextureDefault=glass&borderColorDefault=%23ccc&fcDefault=%23333&iconColorDefault=%23333&bgColorHover=%2300184a&bgTextureHover=inset_soft&borderColorHover=%2300184a&fcHover=%23fff&iconColorHover=%23fff&bgColorActive=%23ffffff&bgTextureActive=glass&borderColorActive=%23eb5b4f&fcActive=%23eb5b4f&iconColorActive=%23eb5b4f&bgColorHighlight=%2300184a&bgTextureHighlight=highlight_soft&borderColorHighlight=%2300184a&fcHighlight=%23fff&iconColorHighlight=%23fff&bgColorError=%23b81900&bgTextureError=diagonals_thick&borderColorError=%23cd0a0a&fcError=%23ffffff&iconColorError=%23ffd27a&bgColorOverlay=%23eb5b4f&bgTextureOverlay=flat&bgImgOpacityOverlay=0&opacityOverlay=80&bgColorShadow=%23000000&bgTextureShadow=flat&bgImgOpacityShadow=10&opacityShadow=1&thicknessShadow=20px&offsetTopShadow=5px&offsetLeftShadow=5px&cornerRadiusShadow=5px&bgImgOpacityHeader=35&bgImgOpacityContent=0&bgImgOpacityDefault=0&bgImgOpacityHover=20&bgImgOpacityActive=65&bgImgOpacityHighlight=20&bgImgOpacityError=18',
                'core' => 'on',
                'widget' => 'on',
                'mouse' => 'on',
                'position' => 'on',
                'draggable' => 'on',
                'droppable' => 'on',
                'resizable' => 'on',
                'selectable' => 'on',
                'sortable' => 'on',
                'accordion' => 'on',
                'autocomplete' => 'on',
                'button' => 'on',
                'datepicker' => 'on',
                'dialog' => 'on',
                'menu' => 'on',
                'progressbar' => 'on',
                'selectmenu' => 'on',
                'slider' => 'on',
                'spinner' => 'on',
                'effect' => 'on',
                'effect-blind' => 'on',
                'effect-bounce' => 'on',
                'effect-clip' => 'on',
                'effect-drop' => 'on',
                'effect-explode' => 'on',
                'effect-fade' => 'on',
                'effect-fold' => 'on',
                'effect-highlight' => 'on',
                'effect-puff' => 'on',
                'effect-pulsate' => 'on',
                'effect-scale' => 'on',
                'effect-shake' => 'on',
                'effect-size' => 'on',
                'effect-slide' => 'on',
                'effect-transfer' => 'on',
                'version' => '1.11.4'
            ]
        );
        $this->downloadZip(
            'https://github.com/trentrichardson/jQuery-Timepicker-Addon/archive/v1.6.3.zip',
            ['jQuery-Timepicker-Addon-1.6.3/dist/jquery-ui-timepicker-addon.js' => 'jquery-ui-timepicker-addon.js']
        );
        $this->downloadFile('https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.2/jquery.ui.touch-punch.min.js');
        File::replaceString($this->_baseFolder . '/jquery-ui-timepicker-addon.js', 'formattedDateTime += this._defaults.separator + this.formattedTime + this._defaults.timeSuffix;', 'formattedDateTime = this.formattedTime + this._defaults.timeSuffix + this._defaults.separator + formattedDateTime;');
        File::replaceString($this->_baseFolder . '/jquery-ui-timepicker-addon.js', 'dateString: allParts.splice(0, allPartsLen - timePartsLen)', 'dateString: allParts.slice(timePartsLen)');
        File::replaceString($this->_baseFolder . '/jquery-ui-timepicker-addon.js', 'timeString: allParts.splice(0, timePartsLen)', 'timeString: allParts.slice(0, timePartsLen)');
    }

}
