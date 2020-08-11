<?php

declare(strict_types=1);

namespace atk4\multiupload;

use atk4\ui\Form;

/** @var \atk4\ui\App $app */

include '../vendor/autoload.php';

$app = new \atk4\ui\App('centered', false, true);
$app->initLayout([\atk4\ui\Layout\Centered::class]);

class PersistenceSql extends \atk4\data\Persistence\Sql
{
    use \atk4\core\AppScopeTrait;
}

// change this as needed
$app->db = $app->add(new \atk4\multiupload\PersistenceSql('mysql://root:root@localhost/atk4'));

$adapter = new \League\Flysystem\Adapter\Local(__DIR__.'/localfiles');
$app->filesystem = new \League\Flysystem\Filesystem($adapter);



class Friend extends \atk4\data\Model {
    
    use \atk4\core\AppScopeTrait;
    
    public $table = 'friend';
    
    function init() : void {
        parent::init();
        
        $this->addField('name'); // friend's name
        $this->addField('file', new \atk4\multiupload\Field\File($this->app->filesystem)); // storing file here
        
    }
}

$form = Form::addTo($app);

$form->setModel(new Friend($app->db));
$form->model->tryLoad(7);

$gr = $app->add([\atk4\ui\Grid::class, 'menu'=>false, 'paginator'=>false]);
$gr->setModel(new \atk4\filestore\Model\File($app->db));
//$app->Js(true, new \atk4\ui\jsExpression('setInterval(function() { []; }, 5000)', [$gr->jsReload()]));

//$app->add(['ui'=>'divider']);

\atk4\ui\Crud::addTo($app)->setModel(new Friend($app->db));


