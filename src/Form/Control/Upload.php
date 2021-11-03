<?php
namespace Atk4\Multiupload\Form\Control;

class Upload extends \Atk4\Multiupload\MultiUpload 
{

    public $model = null; // File model
    

  protected function init(): void {
        parent::init();

        $this->onUpload(\Closure::fromCallable([$this, 'uploaded']));
        $this->onDelete(\Closure::fromCallable([$this, 'deleted']));
        $this->onDownload(\Closure::fromCallable([$this, 'downloaded']));
        
        $this->renderRowFunction = function(\Atk4\Filestore\Model\File $row) {

            return [
                'value' => $row->get('token'),
                'title' => $row->get('meta_filename')
            ];
        };

    }
    

    public function uploaded($file)
    {
        // provision a new file for specified flysystem
        $f = $this->field->model;
        $entity = $f->newFile($this->field->flysystem);

        // add (or upload) the file
        $stream = fopen($file['tmp_name'], 'r+');
        $this->field->flysystem->writeStream($entity->get('location'), $stream, ['visibility'=>'public']);
        if (is_resource($stream)) {
            fclose($stream);
        }

        // get meta from browser
        $entity->set('meta_mime_type', $file['type']);

        // store meta-information
        $is = getimagesize($file['tmp_name']);

        $entity->set('meta_is_image', (bool) $is);
        if ($is){
            $entity->set('meta_mime_type', $is['mime']);
            $entity->set('meta_image_width', $is[0]);
            $entity->set('meta_image_height', $is[1]);
            //$m['extension'] = $is['mime'];
        }
        $entity->set('meta_md5',md5_file($file['tmp_name']));
        $entity->set('meta_filename', $file['name']);
        $entity->set('meta_size', $file['size']);


        $entity->save();
        $this->setFileId($entity->get('token'));
        
        $js =  new \Atk4\Ui\JsNotify(['content' => $entity->get('meta_filename').' uploaded!', 'color' => 'green']); 
        return $js;
    }

    public  function deleted($token)
    {  
        $f = $this->field->model;
        $entity = $f->tryLoadBy('token', $token);

        $js =  new \Atk4\Ui\JsNotify(['content' => $entity->get('meta_filename').' has been removed!', 'color' => 'green']);
        if ($entity->get('status') == 'draft') {
            $entity->delete();
        }

        return $js;
    }
    
    public  function downloaded($token)
    {   $f = $this->field->model;
        $entity = $f->tryLoadBy('token', $token);
  
        $js = [ new \Atk4\Ui\JsNotify(['content' => $entity->get('meta_filename').' is being downloaded!', 'color' => 'green']),
                ];

        return $js;
        
        }
}

