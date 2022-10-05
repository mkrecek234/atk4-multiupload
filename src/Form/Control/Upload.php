<?php

declare(strict_types=1);

namespace Atk4\Multiupload\Form\Control;

use Atk4\Filestore\Field\FileField;
use League\MimeTypeDetection\FinfoMimeTypeDetector;

class Upload extends \Atk4\Multiupload\MultiUpload 
{

    public ?\Atk4\Data\Model $model = null; // File model
    
    /** @var EntityFieldPair<\Atk4\Filestore\Model\File, File> */
    public $entityField;
    

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
    

    protected function uploaded($file)
    {
        // provision a new file for specified flysystem
        $model = $this->entityField->getField()->fileModel;
        $entity = $model->newFile();
        
        // add (or upload) the file
        $stream = fopen($file['tmp_name'], 'r+');
        $this->entityField->getField()->flysystem->writeStream($entity->get('location'), $stream, ['visibility' => 'public']);
        if (is_resource($stream)) {
            fclose($stream);
        }
        
        $detector = new \League\MimeTypeDetection\FinfoMimeTypeDetector();
        
        $mimeType = $detector->detectMimeTypeFromFile($file['tmp_name']);
        // get meta from browser
        $entity->set('meta_mime_type', $file['type']); //$mimeType caused issue doubling identifier name on Excel docs....
        
        // store meta-information
        $imageSizeArr = getimagesize($file['tmp_name']);
        $entity->set('meta_is_image', $imageSizeArr !== false);
        if ($imageSizeArr !== false) {
            $entity->set('meta_image_width', $imageSizeArr[0]);
            $entity->set('meta_image_height', $imageSizeArr[1]);
        }
        
        $entity->set('meta_md5', md5_file($file['tmp_name']));
        $entity->set('meta_filename', $file['name']);
        $entity->set('meta_size', $file['size']);
        
        $entity->save();
        $this->setFileId($entity->get('token'));
        
        $js =  new \Atk4\Ui\JsNotify(['options' => ['content' => $entity->get('meta_filename').' uploaded!', 'color' => 'green']]);
        return $js;
    }

    public  function deleted($token)
    {  
        $model = $this->model;
        $entity = $model->tryLoadBy('token', $token);
        
        $js =  new \Atk4\Ui\JsNotify(['options' => ['content' => $entity->get('meta_filename').' has been removed!', 'color' => 'green']]);
        if ($entity->isLoaded() && $entity->get('status') === 'draft') {
            $entity->delete();
        }

        return $js;
    }
    
    public  function downloaded($token)
    {   
        $model = $this->model;
        $entity = $model->tryLoadBy('token', $token);
  
        $js = [ new \Atk4\Ui\JsNotify(['options' => ['content' => $entity->get('meta_filename').' is being downloaded!', 'color' => 'green']]),
                ];

        return $js;
        
        }
}

