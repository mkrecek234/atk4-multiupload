<?php

declare(strict_types=1);

namespace Atk4\Multiupload;

use Atk4\Ui\Form;

/** @var \Atk4\Ui\App $app */

include '../vendor/autoload.php';

class App extends \Atk4\Ui\App {
    public $filesystem;
}



$app = new \Atk4\Multiupload\App(['title' => 'Filestore Demo']);
$app->initLayout([\Atk4\Ui\Layout\Centered::class]);

class PersistenceSql extends \Atk4\Data\Persistence\Sql
{
    use \atk4\core\AppScopeTrait;
}

// change this as needed
$app->db = new \Atk4\Multiupload\PersistenceSql('mysql://root:root@localhost/atk4');
$app->db->setApp($app);

$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter(__DIR__.'/localfiles');
$app->filesystem = new \League\Flysystem\Filesystem($adapter);



class Friend extends \Atk4\Data\Model {
        
    public $table = 'friend';
    public $filesystem;
    
    protected function init() : void {
        
        parent::init();
        
        $this->addField('name'); // friend's name
        $this->addField('file', [\Atk4\Multiupload\Field\FileField::class, 'flysystem' => $this->getPersistence()->getApp()->filesystem]); // storing file here
        
    }
}

$form = Form::addTo($app);
$model = new Friend($app->db, ['filesystem' => $app->filesystem]);
$entity = $model->tryLoad(1);
if (!$entity) { $entity = $model->createEntity(); }
$form->setModel($entity);
$form->onSubmit(function($form)  {
    $form->model->save(); 
});


$gr = $app->add([\Atk4\Ui\Grid::class, 'menu'=>false, 'paginator'=>false]);
$gr->setModel(new \Atk4\Filestore\Model\File($app->db));
//$app->Js(true, new \Atk4\Ui\jsExpression('setInterval(function() { []; }, 5000)', [$gr->jsReload()]));

//$app->add(['ui'=>'divider']);

\Atk4\Ui\Crud::addTo($app)->setModel(new Friend($app->db));



