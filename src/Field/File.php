<?php


namespace atk4\multiupload\Field;

class File extends \atk4\data\FieldSql
{
    use \atk4\core\InitializerTrait {
        init as _init;
    }


    public $ui = ['form' => [\atk4\multiupload\Form\Control\Upload::class]];

    /**
     * Set a custom model for File
     */
    public $model = null;

    /**
     * Will contain path of the file while it's stored locally
     *
     * @var string
     */
    public $localField = null;

    public $flysystem = null;

    public $normalizedField = null;

    public $fieldFilename;
    public $fieldURL;
    
    public $parent_id = null;

    public function init(): void
    {
        $this->_init();

        if (!$this->model) {
            $this->model = new \atk4\filestore\Model\File($this->owner->persistence);
            $this->model->flysystem = $this->flysystem;
        }

        $this->normalizedField = preg_replace('/_id$/', '', $this->short_name);
        
        $this->reference = $this->owner->addRef($this->short_name, function($m, $c, $d) {
            $archive = $this->model->newInstance();
            
            // only show records of currently loaded record
            if ($m->loaded()) {
                $archive->addCondition($archive->expr("FIND_IN_SET(token,'".($m->get($this->short_name) ?? 0)."')>0"));
            } elseif ($_REQUEST['mid']) { 
                // Very bad workaround as the parent model id cannot be found in the variables - $m is not loaded for VirtualPage modals yet, but it is in the $_REQUEST.
                 
                $mcloned = (clone $this->owner);
                $mcloned->load($_REQUEST['mid']);

                if ($mcloned->get($this->short_name)) { $archive->addCondition($archive->expr("FIND_IN_SET(token,'".($mcloned->get($this->short_name) ?? 0)."')>0")); 
                } else {
                    $archive->addCondition('id', -1);
                }
           }
            return $archive;
        });
        
      //  $this->importFields();

        $this->owner->onHook(\atk4\data\Model::HOOK_BEFORE_SAVE, function($m) {
            if ($m->isDirty($this->short_name)) {
                $oldtokens = $m->dirty[$this->short_name];
                $newtokens = $m->get($this->short_name);

                // remove old file, we don't need it
                if($oldtokens) {
                    foreach (explode(',', $oldtokens) as $oldtoken) {
                        if (!in_array($oldtoken, explode(',', $newtokens))) {
                          $m->refModel($this->short_name)->loadBy('token', $oldtoken)->delete();
                        }
                    }
                }

                // mark new file as linked
                if($newtokens) {
                    foreach ((array) explode(',', $newtokens) as $newtoken) {
                    $m->refModel($this->short_name)->loadBy('token', $newtoken)->save(['status'=>'linked']);
                    }
                }
            }
        });
            $this->owner->onHook(\atk4\data\Model::HOOK_BEFORE_DELETE, function($m) {
            $tokens = $m->get($this->short_name);
            if ($tokens) {
                foreach (explode(',', $tokens) as $token) {
                $m->refModel($this->short_name)->loadBy('token', $token)->delete();
                }
            }
        });
    }

    function importFields()
    {
        //$this->reference->addField($this->normalizedField.'_token', 'token');
        $this->fieldURL = $this->model->addExpression($this->normalizedField.'_url', 'group_concat(url)');
        $this->fieldFilename = $this->model->addExpression($this->normalizedField.'_filename', 'group_concat(meta_filename)');
    }

    function __construct(\League\Flysystem\Filesystem $flysystem) {
        $this->flysystem = $flysystem;
    }

    public function normalize($value)
    {
        return parent::normalize($value);
    }
}
