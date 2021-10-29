<?php

// vim:ts=4:sw=4:et:fdm=marker:fdl=0

namespace Atk4\Multiupload\Field;

class File extends \Atk4\Data\FieldSql
{
    use \Atk4\Core\InitializerTrait {
        init as _init;
    }


    public $ui = ['form' => [\Atk4\Multiupload\Form\Control\Upload::class]];

    /**
     * Set a custom model for File
     */
    public $model;

    /**
     * Will contain path of the file while it's stored locally
     *
     * @var string
     */
    public $localField;

    public $flysystem;

    public $normalizedField;

    public $referenceLink;

    public $fieldFilename;
    public $fieldURL;

    public function __construct(\League\Flysystem\Filesystem $flysystem)
    {
        parent::__construct([]);
        $this->flysystem = $flysystem;
    }
    
    public function normalize($value)
    {
        return parent::normalize($value);
    }
    
    protected function init(): void
    {
        $this->_init();
        
        if (!$this->model) {
            $this->model = new \Atk4\Filestore\Model\File($this->getOwner()->persistence);
            $this->model->flysystem = $this->flysystem;
        }
        
        $this->normalizedField = preg_replace('/_id$/', '', $this->short_name);
        $this->referenceLink = $this->getOwner()->addRef($this->short_name, ['model' => function($m) {
            $archive = new $this->model($this->model->persistence);
            
            // only show records of currently loaded record
            if ($m->loaded()) {
                $archive->addCondition($archive->expr("FIND_IN_SET(token,'".($m->get($this->short_name) ?? 'notavailable')."')>0"));
            } elseif (array_key_exists('mid', $_REQUEST)) {
                // Very bad workaround as the parent model id cannot be found in the variables - $m is not loaded for VirtualPage modals yet, but it is in the $_REQUEST.
                
                $mcloned = (clone $this->owner);
                $mcloned->load($_REQUEST['mid']);
                
                if ($mcloned->get($this->short_name)) { $archive->addCondition($archive->expr("FIND_IN_SET(token,'".($mcloned->get($this->short_name) ?? 'notavailable')."')>0"));
                } else {
                    $archive->setLimit(20);
                }
            } else {
                $archive->setLimit(20);
            }
            
            return $archive;
        }])->link;
        
        //$this->importFields();

        $this->getOwner()->onHook(\Atk4\Data\Model::HOOK_BEFORE_SAVE, function($m) {
            if ($m->isDirty($this->short_name)) {
                $oldtokens = $m->dirty[$this->short_name];
                $newtokens = $m->get($this->short_name);

                // remove old file, we don't need it
                if($oldtokens) {
                    foreach (explode(',', $oldtokens) as $oldtoken) {
                        if (!in_array($oldtoken, explode(',', $newtokens))) {
                           $this->model->loadBy('token', $oldtoken)->delete();
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
            $this->getOwner()->onHook(\Atk4\Data\Model::HOOK_BEFORE_DELETE, function($m) {
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

    /**
     * Idea: update model to reflect current tokens, but not called at init...
     *
     public function set($value): self
     {
     $this->owner->set($this->short_name, $value);
     $this->model->addCondition($this->model->expr("FIND_IN_SET(token,'".($value) ?? 'notavailable')."')>0"));
     
     return $this;
     }
     */
}
