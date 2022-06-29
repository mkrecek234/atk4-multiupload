<?php

declare(strict_types=1);

// vim:ts=4:sw=4:et:fdm=marker:fdl=0

namespace Atk4\Multiupload\Field;


use Atk4\Core\InitializerTrait;
use Atk4\Data\Field;
use Atk4\Data\Model;
use Atk4\Data\Reference\HasOneSql;
use Atk4\Filestore\Form\Control\Upload;
use Atk4\Filestore\Model\File;
use League\Flysystem\Filesystem;

class FileField extends Field
{
    use InitializerTrait {
        init as _init;
    }


    public $ui = ['form' => [\Atk4\Multiupload\Form\Control\Upload::class]];

    /** @var File|null */
    public $fileModel;
    /** @var Filesystem for fileModel init */
    public $flysystem;

    /** @var string */
    protected $fieldNameBase;
    /** @var HasOneSql */
    public $reference;
    
    /** @var Field */
    public $fieldFilename;
    /** @var Field */
    public $fieldUrl;
    
    /** To be removed?
     * Will contain path of the file while it's stored locally
     *
     * @var string
     */
    public $localField;

    public $normalizedField;

    public $referenceLink;


    protected function init(): void
    {
        $this->_init();
        
        if ($this->fileModel === null) {
            $this->fileModel = new File($this->getOwner()->getPersistence());
            $this->fileModel->flysystem = $this->flysystem;
        }
        
        $this->fieldNameBase = preg_replace('/_id$/', '', $this->shortName);
        
        $this->importFields();
        
        $this->referenceLink = $this->getOwner()->addRef($this->shortName, ['model' => function($m) {
        $archive = new $this->fileModel($this->fileModel->getPersistence());
            
        // only show records of currently loaded record
        if (!($GLOBALS['model'] === $m) && array_key_exists('mid', $_REQUEST)) {
            // Very bad workaround as the parent model id cannot be found in the variables - $m is not loaded for VirtualPage modals yet, but it is in the $_REQUEST.
            
            $mcloned = (clone $this->getOwner());
            $entity = $mcloned->load($_REQUEST['mid']);
            
            if ($entity->get($this->shortName)) { $archive->addCondition($archive->expr("FIND_IN_SET(token,'".($entity->get($this->shortName) ?? 'notavailable')."')>0"));
            } else {
                $archive->setLimit(20);
            }
        } elseif (($GLOBALS['model'] === $m) && array_key_exists('entity', $GLOBALS) && ($GLOBALS['entity']->isEntity())) {
            $archive->addCondition($archive->expr("FIND_IN_SET(token,'".($GLOBALS['entity']->get($this->shortName) ?? 'notavailable')."')>0"));
        } else {
            $archive->setLimit(20);
        }
            
            return $archive;
        }])->link;
        
        //$this->importFields();

        $this->getOwner()->onHook(Model::HOOK_BEFORE_SAVE, function(Model $m) {
            if ($m->isDirty($this->shortName)) {
                $oldtokens = $m->getDirtyRef()[$this->shortName];
                $newtokens = $m->get($this->shortName);

                // remove old file, we don't need it
                if($oldtokens) {
                    foreach (explode(',', $oldtokens) as $oldtoken) {
                        if (($newtokens) && (!in_array($oldtoken, explode(',', $newtokens)))) {
                           $this->fileModel->loadBy('token', $oldtoken)->delete();
                        }
                    }
                }

                // mark new file as linked
                if($newtokens) {
                    foreach ((array) explode(',', $newtokens) as $newtoken) {
                        $m->refModel($this->shortName)->loadBy('token', $newtoken)->save(['status'=>'linked']);
                    }
                }
            }
        });
        
            $this->getOwner()->onHook(Model::HOOK_BEFORE_DELETE, function(Model $m) {
                $tokens = $m->get($this->shortName);
            if ($tokens) {
                foreach (explode(',', $tokens) as $token) {
                    $m->refModel($this->shortName)->loadBy('token', $token)->delete();
                }
            }
        });
    }

    function importFields(): void
    {
        //$this->reference->addField($this->normalizedField.'_token', 'token');
     //   $this->fieldUrl = $this->fileModel->addExpression($this->fieldNameBase.'_url', 'group_concat(url)');
     //   $this->fieldFilename = $this->fileModel->addExpression($this->fieldNameBase.'_filename', 'group_concat(meta_filename)');
    }

    /**
     * Idea: update model to reflect current tokens, but not called at init...
     *
     public function set($value): self
     {
     $this->owner->set($this->shortName, $value);
     $this->model->addCondition($this->model->expr("FIND_IN_SET(token,'".($value) ?? 'notavailable')."')>0"));
     
     return $this;
     }
     */
}
