<?php

declare(strict_types=1);

namespace atk4;

use atk4\ui\Form;
use atk4\filestore\Model\File;

/** @var \atk4\ui\App $app */

include '../vendor/autoload.php';

$app = new \atk4\ui\App('centered', false, true);
$app->initLayout([\atk4\ui\Layout\Centered::class]); 
$app->db = $app->add(new \atk4\data\Persistence\Sql('mysql://root:root@localhost/atk4'));


$form = Form::addTo($app);

$control = $form->addControl('file', [\atk4\multiupload\MultiUpload::class,
    'empty'      => 'Upload multiple files',
    'isMultiple' => true,
  //  'dropdownOptions' => ['allowAdditions' => true]
]
    );
$control->setModel(new \atk4\filestore\Model\File($app->db));
/* 
 * Example for manual file ids and filenames
$control->setSource([['id' =>"token-5f2fec025c3bb", 'name' =>'test.php'], ['id' =>"token-5f2fec025c3bc", 'name' =>'test2.php']]);
$control->set("token-5f2fec025c3bb,token-5f2fec025c3bc");
*/


$control->onDelete(function ($fileId) {
    return new \atk4\ui\JsToast([
        'title' => 'Delete successfully',
        'message' => $fileId . ' has been removed',
        'class' => 'success',
    ]);
});

$control->onDownload(function ($fileId) {
    return new \atk4\ui\JsToast([
        'title' => 'Download successfully',
        'message' => $fileId . ' is being downloaded',
        'class' => 'success',
    ]);
});

$control->onUpload(function ($files) use ($form, $control) {
    if ($files === 'error') {
        return $form->error('file', 'Error uploading file.');
    }
    $control->setFileId('a_token'.rand(0,100));

    return new \atk4\ui\JsToast([
        'title' => 'Upload success',
        'message' => 'File '. $files['name'] . ' with token is uploaded!',
        'class' => 'success',
    ]);
});


$control2 = $form->addControl('file2', [\atk4\ui\Form\Control\Upload::class]
    );

$control2->set('a_new_token', 'an-img-file-name');
    
$control2->onDelete(function ($fileId) use ($img) {
        
        return new \atk4\ui\JsToast([
            'title' => 'Delete successfully',
            'message' => $fileId . ' has been removed',
            'class' => 'success',
        ]);
    });
        
        
        
        $control2->onUpload(function ($files) use ($form, $img) {
            if ($files === 'error') {
                return $form->error('img', 'Error uploading image.');
            }
            
            //Do file processing here...
            
            // This will get caught by JsCallback and show via modal.
            //new Blabla();
            
            // js Action can be return.
            //if using form, can return an error to form control directly.
            //return $form->error('file', 'Unable to upload file.');
            
            // can also return a notifier.
            return new \atk4\ui\JsToast([
                'title' => 'Upload success',
                'message' => 'Image is uploaded!',
                'class' => 'success',
            ]);
        });

$form->onSubmit(function (Form $form) {
    // implement submission here
    return $form->success('Thanks for submitting file');
});
