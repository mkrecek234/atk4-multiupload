<?php

declare(strict_types=1);

namespace Atk4\Multiupload;

use Atk4\Data\Model;
use Atk4\Ui\Exception;
use Atk4\Ui\Js\JsBlock;
use Atk4\Ui\Js\JsExpressionable;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class Upload.
 */
class MultiUpload extends \Atk4\Ui\Form\Control\Dropdown
{
    public string $inputType = 'hidden';
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

    /**
     * Local storage for the display value when this control is used
     * standalone, i.e. NOT bound to a model entity field (no Form::addControl()/entityField).
     *
     * atk4/ui 6.0 removed View::$content and View::set() (#2073), so this class
     * can no longer fall back on the base View content storage and needs its own.
     *
     * @var mixed
     */
    private $inputValue;
    
    protected function init(): void
        {   
        parent::init();
        
        $this->values = [];
        
        $this->setDropDownOption('allowAdditions', true);
        $this->setDropDownOption('search', false);

        $this->multiple = true;
        //$this->inputType = 'hidden';

        $this->cb = \Atk4\Ui\JsCallback::addTo($this);

        if ($this->action === null) {
            $this->action = new \Atk4\Ui\Button([
                'icon' => 'upload',
                'class.disabled' => $this->disabled || $this->readOnly,
            ]);
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
    public function set($fileId = null, $fileName = null, $junk = null)
    {
        $this->setFileId($fileId);

        if (!$fileName) {
            $fileName = $fileId;
        }

        return $this->setInput($fileName, $junk);
    }

    /**
     * Set input field value.
     *
     * When bound to a model entity field (normal case, e.g. via Form::addControl()),
     * the value is written straight into the entity so it round-trips like any
     * other form control. Otherwise it's kept in $this->inputValue, since
     * atk4/ui 6.0 no longer provides View::set()/View::$content to fall back on.
     *
     * @param mixed $value the field input value
     * @param mixed $junk  kept for backward-compatibility, unused
     *
     * @return $this
     */
    public function setInput($value, $junk = null)
    {
        if ($this->entityField !== null) {
            $this->entityField->set($value);
        } else {
            $this->inputValue = $value;
        }

        return $this;
    }

    /**
     * Get input field value.
     *
     * @return array|false|mixed|string|null
     */
    public function getInputValue() : ?string
    {
        return $this->entityField !== null ? $this->entityField->get() : $this->inputValue;
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
    {
        $this->hasUploadCb = true;
        if ($this->getApp()->tryGetRequestPostParam('f_upload_action') === self::UPLOAD_ACTION) {
            $this->cb->set(function () use ($fx) {
                $postFiles = [];

                for ($i = 0;; ++$i) {
                    $k = 'file' . ($i > 0 ? '-' . $i : '');
                    $uploadedFile = $this->getApp()->tryGetRequestUploadedFile($k);
                    if ($uploadedFile === null) {
                        break;
                    }

                    $postFile = $this->uploadedFileToLegacyArray($uploadedFile);
                    if ($postFile['error'] !== \UPLOAD_ERR_OK) {
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

                    if (count($postFiles) > 0 && reset($postFiles)['error'] === \UPLOAD_ERR_OK) {
                        $this->addJsAction([
                            $this->js()->atkmultiFileUpload('updateField', [$this->fileId, $postFile['name']]),
                        ]);
                    }
                }

                $this->jsActions[] =
                    new \Atk4\Ui\Js\JsExpression("$(this).parents('.form.ui.initial').data('isDirty', true)");

                return new JsBlock($this->jsActions);
            });
        }
    }

    /**
     * Converts a PSR-7 UploadedFileInterface (as returned by App::getRequest())
     * into the legacy $_FILES-shaped array (['name', 'type', 'size', 'error', 'tmp_name'])
     * so that existing/derived onUpload() handlers (e.g. Form\Control\Upload::uploaded())
     * keep working unchanged against atk4/ui 6.0+, which no longer guarantees
     * populated $_FILES/$_POST superglobals and instead exposes uploads via PSR-7.
     *
     * @return array{name: string|null, type: string|null, size: int|null, error: int, tmp_name: string|null}
     */
    private function uploadedFileToLegacyArray(UploadedFileInterface $uploadedFile): array
    {
        $result = [
            'name' => $uploadedFile->getClientFilename(),
            'type' => $uploadedFile->getClientMediaType(),
            'size' => $uploadedFile->getSize(),
            'error' => $uploadedFile->getError(),
            'tmp_name' => null,
        ];

        if ($result['error'] === \UPLOAD_ERR_OK) {
            $stream = $uploadedFile->getStream();
            $uri = $stream->getMetadata('uri');

            if (is_string($uri) && is_file($uri)) {
                // most PSR-7 implementations (Nyholm, Laminas Diactoros, Guzzle)
                // back an un-moved uploaded file with a real filesystem path
                $result['tmp_name'] = $uri;
            } else {
                // fallback: persist the stream to a real temp file so that consumers
                // relying on a filesystem path (getimagesize(), md5_file(), fopen(), ...)
                // keep working regardless of the underlying PSR-7 implementation
                $tmpPath = tempnam(sys_get_temp_dir(), 'atk4upl');
                $dest = fopen($tmpPath, 'wb');
                $stream->rewind();
                while (!$stream->eof()) {
                    fwrite($dest, $stream->read(8192));
                }
                fclose($dest);
                $result['tmp_name'] = $tmpPath;
            }
        }

        return $result;
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
        if ($this->getApp()->tryGetRequestPostParam('f_upload_action') === self::DELETE_ACTION) {
            $this->cb->set(function () use ($fx) {

                $fileName = $this->getApp()->tryGetRequestPostParam('f_name');
                $this->addJsAction($fx($fileName));

                $this->jsActions[] =
                    new \Atk4\Ui\Js\JsExpression("$(this).parent('.form.ui.initial').data('isDirty', true)");

                return new JsBlock($this->jsActions);
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
        if ($this->getApp()->tryGetRequestPostParam('f_upload_action') === self::DOWNLOAD_ACTION) {
            $this->cb->set(function () use ($fx) {

                $fileName = $this->getApp()->tryGetRequestPostParam('f_name');
                $this->addJsAction($fx($fileName));

                return new JsBlock($this->jsActions);
            });
                
        }
    }

    /**
     * Used when a custom callback is defined for row rendering. Sets
     * values to row template and appends it to main template.
     *
     * @param mixed                               $row
     * @param ($row is Model ? never : array-key) $key
     */
    protected function _addCallBackRow($row, $key = null): void
    {
        if ($this->model !== null) {
            $res = ($this->renderRowFunction)($row);
            $this->_tItem->set('value', array_key_exists('value', $res) ? $res['value'] : $this->getApp()->uiPersistence->typecastAttributeSaveField($this->model->getField($this->model->idField), $row->getId()));
        } else {
            $res = ($this->renderRowFunction)($row, $key); // @phpstan-ignore-line https://github.com/phpstan/phpstan/issues/10283#issuecomment-1850438891
            $this->_tItem->set('value', (string) $res['value']); // @phpstan-ignore-line https://github.com/phpstan/phpstan/issues/10283
        }

        $this->_tItem->set('title', $res['title']);

        $this->_tItem->del('Icon');
        if (isset($res['icon']) && $res['icon']) {
            // compatibility with how $values property works on icons: 'icon'
            // is defined in there
            $this->_tIcon->set('iconClass', 'icon ' . $res['icon']);
            $this->_tItem->dangerouslyAppendHtml('Icon', $this->_tIcon->renderToHtml());
        }

        // add item to template
        $this->template->dangerouslyAppendHtml('Item', $this->_tItem->renderToHtml());
    }

    protected function jsRenderDropdown(): JsExpressionable
    {
        $dropdownOptions = $this->dropdownOptions;
        $dropdownOptions['clearable'] = false;

        return $this->jsDropdown(true)->dropdown($dropdownOptions);
    }

    protected function renderView(): void
    {
        parent::renderView();

        if ($this->cb->canTerminate()) {
            $uploadActionRaw = $this->getApp()->tryGetRequestPostParam('f_upload_action');
            if (!$this->hasUploadCb && ($uploadActionRaw === self::UPLOAD_ACTION)) {
                throw new Exception('Missing onUpload callback.');
            } elseif (!$this->hasDeleteCb && ($uploadActionRaw === self::DELETE_ACTION)) {
                throw new Exception('Missing onDelete callback.');
            }
        }

        if ($this->accept !== []) {
            $this->template->set('accept', implode(', ', $this->accept));
        }

        if ($this->disabled || $this->readOnly) {
            $this->template->dangerouslySetHtml('disabled', 'disabled="disabled"');
        }

        if ($this->multiple) {
            $this->template->dangerouslySetHtml('multiple', 'multiple="multiple"');
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
