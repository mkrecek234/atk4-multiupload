<?php

declare(strict_types=1);

namespace atk4\multiupload;

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
$app->db = $app->add(new \atk4\multiupload\PersistenceSql('mysql://root:root@localhost/atk4'));

$adapter = new \League\Flysystem\Adapter\Local(__DIR__.'/localfiles');
$app->filesystem = new \League\Flysystem\Filesystem($adapter);



class Friend extends \Atk4\Data\Model {
    
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

$gr = $app->add([\Atk4\Ui\Grid::class, 'menu'=>false, 'paginator'=>false]);
$gr->setModel(new \Atk4\Filestore\Model\File($app->db));
//$app->Js(true, new \Atk4\Ui\jsExpression('setInterval(function() { []; }, 5000)', [$gr->jsReload()]));

//$app->add(['ui'=>'divider']);

\Atk4\Ui\Crud::addTo($app)->setModel(new Friend($app->db));


