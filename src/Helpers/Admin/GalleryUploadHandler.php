<?php namespace CoasterCms\Helpers\Admin;

require base_path('vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php');

class GalleryUploadHandler extends \UploadHandler
{
    public $name;

    public function __construct($options = null, $initialize = true, $error_messages = null)
    {
        parent::__construct($options, $initialize, $error_messages);
        $this->error_messages['accept_file_types'] = 'Filetype not allowed (allowed extensions: .gif, .jpg, .jpeg, .png)';
    }

    protected function get_file_objects($iteration_method = 'get_file_object')
    {
        if (isset($this->options['selected_data'])) {
            $files = array();
            foreach ($this->options['selected_data'] as $image => $image_data) {
                array_push($files, $image);
            }
        } else {
            $upload_dir = $this->get_upload_path();
            if (!is_dir($upload_dir)) {
                return array();
            } else {
                $files = scandir($upload_dir);
            }
        }
        return array_values(array_filter(array_map(
            array($this, $iteration_method),
            $files
        )));
    }

    protected function get_file_object($file_name)
    {
        if (!($file = parent::get_file_object($file_name))) {
            $file = new \stdClass();
            $file->name = $file_name;
            $file->size = 0;
            $file->url = $this->get_download_url($file->name);
            $this->set_additional_file_properties($file);
        }
        return $file;
    }

    protected function get_unique_filename($file_path, $name, $size, $type, $error, $index, $content_range)
    {
        $name = str_replace(array('(', ')', ','), '', str_replace(' ', '_', $name));
        return parent::get_unique_filename($file_path, $name, $size, $type, $error, $index, $content_range);
    }

    protected function upcount_name_callback($matches)
    {
        $index = isset($matches[1]) ? ((int)$matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';
        return '_' . $index . $ext;
    }

    protected function upcount_name($name)
    {
        return preg_replace_callback(
            '/(?:(?:_([\d]+))?(\.[^.]+))?$/',
            array($this, 'upcount_name_callback'),
            $name,
            1
        );
    }

    protected function handle_form_data($file, $index)
    {
        $this->name = $file->name;
    }

    public function generate_response($content, $print_response = true)
    {
        if (!empty($content['files'])) {
            foreach ($content['files'] as $key => $content_image_data) {
                if (!empty($this->options['selected_data'][$content_image_data->name])) {
                    foreach (get_object_vars($this->options['selected_data'][$content_image_data->name]) as $var => $data) {
                        $content['files'][$key]->$var = $data;
                    }
                }
            }
            usort($content['files'], ['self', 'order_items']);
        }
        return parent::generate_response($content, $print_response);
    }

    public static function order_items($a, $b)
    {
        if (!isset($a->order) || !isset($b->order) || $a->order == $b->order) {
            return 0;
        }
        return ($a->order < $b->order) ? -1 : 1;
    }

}
