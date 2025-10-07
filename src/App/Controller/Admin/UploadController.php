<?php

namespace OpenCCK\App\Controller\Admin;

use Amp\Http\HttpStatus;
use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\FormParser\FormParser;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use Amp\File;

use Exception;
use OpenCCK\App\Controller\AdminController;
use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use function OpenCCK\parseBytes;

class UploadController extends AdminController {
    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);
    }

    /**
     * @return int[]
     * @throws Exception
     */
    public function default(): array {
        $form = Form::fromRequest($this->request, new FormParser(parseBytes(ini_get('post_max_size'))));
        $dirUploads = 'uploads';
        $dirStorage = $form->getValue('dir');
        $dir = 'public' . '/' . $dirUploads . '/' . $dirStorage;
        $uploaded = [];
        if (!is_dir(PATH_ROOT . '/' . $dir)) {
            mkdir(PATH_ROOT . '/' . $dir);
        }
        foreach ($form->getFiles() as $name => $files) {
            foreach ($files as $file) {
                if (!$file->isEmpty()) {
                    $fullPath = PATH_ROOT . '/' . $dir . '/' . $file->getName();
                    if (is_file($fullPath)) {
                        unlink($fullPath);
                        //throw new Exception('File ' . $file->getName() . ' already exists', HttpStatus::CONFLICT);
                    }
                    $uploaded[$name][] = '/' . $dirUploads . '/' . $dirStorage . '/' . $file->getName();
                    File\write($fullPath, $file->getContents());
                    App::getLogger()->info('saved [' . $name . '] ' . $fullPath, [ini_get('post_max_size')]);
                }
            }
        }
        return $uploaded;
    }
}
