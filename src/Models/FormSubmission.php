<?php namespace CoasterCms\Models;

use Eloquent;

class FormSubmission extends Eloquent
{
    protected $table = 'form_submissions';

    public function uploadFiles($files)
    {
        if (!$this->form_block_id) {
            throw new \Exception('form block id must be set before saving files');
        }
        $formData = $this->content ? unserialize($this->content) : [];
        foreach ($files as $field => $requestFile) {
            /** @var \Illuminate\Http\UploadedFile $requestFile */
            $uploadFolder = '/uploads/system/forms/' . $this->form_block_id;
            $fullUploadPath = public_path() . $uploadFolder;
            if (!file_exists($fullUploadPath)) {
                mkdir($fullUploadPath, 0755, true);
            }
            $uniqueFilename = $field . ' ' . $this->id . ' ' . $requestFile->getClientOriginalName();
            $requestFile->move($uploadFolder, $uniqueFilename);
            $formData[$field] = \HTML::link($uploadFolder . '/' . $uniqueFilename, $uniqueFilename);
        }
        $this->content = serialize($formData);
    }

}