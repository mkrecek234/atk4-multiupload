[ATK UI](https://github.com/atk4/ui) implements a high-level User Interface for Web App - such as **Admin System**. One of the most common things for the Admin system is a log-in screen.

This add-on will provide a) an upload field that allows to upload multiple files at once or sequentially and b) a sample integration in atk4/filestore. 


## Installation

Install through composer `composer require mkrecek234/multiupload`

## Usage


Simply add the multiupload field in your form like this.

```
$control = $form->addControl('file', [\atk4\multiupload\MultiUpload::class,
    'empty'      => 'Upload multiple files',
    'isMultiple' => true
]
    );
$control->setModel(new \atk4\filestore\Model\File($app->db));
```
The ->setModel command is only required if you want to translate tokens into filenames (here as an example for atk4/filestore integration).

A click on the upload icon performs an upload of one or multiple files. A further click adds further items. A click on the "X" icon removes an individual file. A click on the filename performs an onDownload action. The file tokens are stored as comma-separated values in a single field which is very convenient not requiring child-tables just for attached files.

You will see 2 demos:
1) demos/uploadtest.php to show the standard upload control element
2) demos/uploadtest-filestore.php to show the integration in atk4/filestore.


