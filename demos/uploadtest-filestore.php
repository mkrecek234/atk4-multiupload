<?php

declare(strict_types=1);

namespace Atk4\Multiupload;

use Atk4\Ui\Form;

/** @var \Atk4\Ui\App $app */

include '../vendor/autoload.php';

$app = new \Atk4\Ui\App('centered', false, true);
$app->initLayout([\Atk4\Ui\Layout\Centered::class]);

class PersistenceSql extends \Atk4\Data\Persistence\Sql
{
    use \atk4\core\AppScopeTrait;
}

// change this as needed
$app->db = new \atk4\multiupload\PersistenceSql('mysql://root:root@localhost/atk4');
$app->db->setApp($app);

$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter(__DIR__.'/localfiles');
$app->filesystem = new \League\Flysystem\Filesystem($adapter);



class Friend extends \Atk4\Data\Model {
        
    public $table = 'friend';
    
    protected function init() : void {
        parent::init();
        
        $this->addField('name'); // friend's name
        $this->addField('file', new \Atk4\Multiupload\Field\File($this->persistence->getApp()->filesystem)); // storing file here
        
    }
}

$form = Form::addTo($app);
$model = new Friend($app->db);
$entity = $model->tryLoad(7);
$form->setModel($entity);


$gr = $app->add([\Atk4\Ui\Grid::class, 'menu'=>false, 'paginator'=>false]);
$gr->setModel(new \Atk4\Filestore\Model\File($app->db));
//$app->Js(true, new \Atk4\Ui\jsExpression('setInterval(function() { []; }, 5000)', [$gr->jsReload()]));

//$app->add(['ui'=>'divider']);

\Atk4\Ui\Crud::addTo($app)->setModel(new Friend($app->db));


