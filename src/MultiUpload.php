<?php

declare(strict_types=1);

namespace Atk4\Multiupload;

use Atk4\Ui\Exception;

/**
 * Class Upload.
 */
class MultiUpload extends \Atk4\Ui\Form\Control\Dropdown
{
   // public $inputType = 'hidden';
    /**
     * The action button to open file browser dialog.
     *
     * @var View
     */
    public $action;

    /**
     * The uploaded file id as array for multiple files as array of 'fileId' => 'filename'.
     * This id is return on form submit.
     * If not set, will default to file name.
     * file id is also sent with onDelete Callback.
     *
     * @var string
     */
    public $fileId;


    /**
     * Whether you need to open file browser dialog using input focus or not.
     * default to true.
     *
     * @var bool
     * @obsolete
     * hasFocusEnable has been disable in js plugin and this property will be removed.
     * Upload field is only using click handler now.
     */
    public $hasFocusEnable = false;

    /**
     * The input default template.
     *
     * @var string
     */
    public $defaultTemplate = __DIR__.'/../template/multiupload.html';

    /**
     * Callback is use for onUpload or onDelete.
     *
     * @var \Atk4\Ui\JsCallback
     */
    public $cb;

    /**
     * Allow multiple file or not.
     * CURRENTLY NOT SUPPORTED.
     *
     * @var bool
     */
    public $multiple = true;

    /**
     * An array of string value for accept file type.
     * ex: ['.jpg', '.jpeg', '.png'] or ['images/*'].
     *
     * @var array
     */
    public $accept = [];

    /**
     * Whether cb has been define or not.
     *
     * @var bool
     */
    public $hasUploadCb = false;
    public $hasDeleteCb = false;
    public $hasDownloadCb = false;

    public $jsActions = [];

    /**
     * Keep track of Multi Upload api js file loaded or not.
     * @var bool
     */
    public $isJsLoaded = false;
    
    public const UPLOAD_ACTION = 'upload';
    public const DELETE_ACTION = 'delete';
    public const DOWNLOAD_ACTION = 'download';

    /** @var bool check if callback is trigger by one of the action. */
    private $_isCbRunning = false;
    
    protected function init(): void
        {   
        parent::init();
        
        $this->values = [];
        
        $this->setDropDownOption('allowAdditions', true);
        $this->setDropDownOption('search', false);
        $this->isMultiple = true;
        //$this->inputType = 'hidden';

        $this->cb = \Atk4\Ui\JsCallback::addTo($this);

        if (!$this->action) {
            $this->action = new \Atk4\Ui\Button(['icon' => 'upload', 'disabled' => ($this->disabled || $this->readonly)]);
        }
    }

    /**
     * Allow to set file id and file name
     *  - fileId will be the file id sent with onDelete callback.
     *  - fileName is the field value display to user.
     *
     * @param string      $fileId   // Field id for onDelete Callback
     * @param string|null $fileName // Field name display to user
     * @param mixed       $junk
     *
     * @return $this|void
     */
 /*   public function set($fileId = null, $fileName = null, $junk = null)
    {
        $this->setFileId($fileId);

        if (!$fileName) {
            $fileName = $fileId;
        }

        return $this->setInput($fileName, $junk);
    } */

    /**
     * Set input field value.
     *
     * @param mixed $value the field input value
     *
     * @return $this
     */
    public function setInput($value, $junk = null)
    {
        return parent::set($value, $junk);
    }

    /**
     * Get input field value.
     *
     * @return array|false|mixed|string|null
     */
    public function getInputValue()
    {
        return $this->entityField ? $this->entityField->get() : $this->content;
    }

    /**
     * Set file id.
     */
    public function setFileId($id)
    {
        $this->fileId = $id;
    }

    /**
     * Add a js action to be return to server on callback.
     */
    public function addJsAction($action)
    {
        if (is_array($action)) {
            $this->jsActions = array_merge($action, $this->jsActions);
        } else {
            $this->jsActions[] = $action;
        }
    }
    
    /**
     * onUpload callback.
     * Call when user is uploading a file.
     *
     * @param callable $fx
     */
    public function onUpload(\Closure $fx)
    {   $this->hasUploadCb = true;
    if (($_POST['f_upload_action'] ?? null) === self::UPLOAD_ACTION) {
        $this->cb->set(function () use ($fx) {
            $postFiles = [];
            
            for ($i = 0;; ++$i) {
                $k = 'file' . ($i > 0 ? '-' . $i : '');
                if (!isset($_FILES[$k])) {
                    break;
                }
                
                $postFile = $_FILES[$k];
                if ($postFile['error'] !== 0) {
                    // unset all details on upload error
                    $postFile = array_intersect_key($postFile, array_flip(['error', 'name']));
                }
                $postFiles[] = $postFile;
            }
            
            foreach ($postFiles as $postFile) {
                
            if (count($postFiles) > 0) {
                $fileId = $postFile['name'];
                $this->setFileId($fileId);
                $this->setInput($fileId);
            }
            
            $this->addJsAction($fx($postFile));
            
            if (count($postFiles) > 0 && reset($postFiles)['error'] === 0) {

                $this->addJsAction([
                    $this->js()->atkmultiFileUpload('updateField', [$this->fileId, $postFile['name']]),
                ]);
                }
            }
        
            
            return $this->jsActions;
        });
    }
    }

    /**
     * onDelete callback.
     * Call when user is removing an already upload file.
     *
     * @param callable $fx
     */
    public function onDelete(\Closure $fx)
    {

        $this->hasDeleteCb = true;
        if (($_POST['f_upload_action'] ?? null) === self::DELETE_ACTION) {
            $this->cb->set(function () use ($fx) {

                $fileName = $_POST['f_name'] ?? null; 
                $this->addJsAction($fx($fileName));

                return $this->jsActions; 
            });
        
        }
    }

   
    
    /**
     * onDelete callback.
     * Call when user is removing an already upload file.
     *
     * @param callable $fx
     */
    public function onDownload($fx = null)
    {
        
        $this->hasDeleteCb = true;
        if (($_POST['f_upload_action'] ?? null) === self::DOWNLOAD_ACTION) {
            $this->cb->set(function () use ($fx) {
                
                $fileName = $_POST['f_name'] ?? null;
                $this->addJsAction($fx($fileName));
                
                return $this->jsActions;
            });
                
        }
    }

    protected function renderView(): void
    {
        //need before parent rendering.
        if ($this->disabled) {
            $this->addClass('disabled');
        }
        parent::renderView();

        if ($this->cb->canTerminate()) {
            $uploadActionRaw = $_POST['f_upload_action'] ?? null;
            if (!$this->hasUploadCb && ($uploadActionRaw === self::UPLOAD_ACTION)) {
                throw new Exception('Missing onUpload callback.');
            } elseif (!$this->hasDeleteCb && ($uploadActionRaw === self::DELETE_ACTION)) {
                throw new Exception('Missing onDelete callback.');
            }
        }
        
        if (!empty($this->accept)) {
            $this->template->trySet('accept', implode(',', $this->accept));
        }
        if ($this->multiple) {
            $this->template->trySet('multiple', 'multiple');
        }

        if ($this->placeholder) {
            $this->template->trySet('PlaceHolder', $this->placeholder);
        }
        
        if (!$this->isJsLoaded) {
            $this->getApp()->requireJs('../public/atkmultiupload.js');
        }
       
 
        //$value = $this->field ? $this->field->get() : $this->content;
        $this->js(true)->atkmultiFileUpload([
            'uri' => $this->cb->getJsUrl(),
            'action' => $this->action->name,
            'file' => ['id' => $this->fileId ?: $this->entityField->get(), 'name' => $this->getInputValue()],
            'hasFocus' => $this->hasFocusEnable,
            'submit' => ($this->form->buttonSave) ? $this->form->buttonSave->name : null,
        ]);
    }
}
