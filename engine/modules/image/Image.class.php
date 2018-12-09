<?php
/*-------------------------------------------------------
*
*   LiveStreet Engine Social Networking
*   Copyright © 2008 Mzhelskiy Maxim
*
*--------------------------------------------------------
*
*   Official site: www.livestreet.ru
*   Contact e-mail: rus.engine@gmail.com
*
*   GNU General Public License, version 2:
*   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
---------------------------------------------------------
*/

require_once Config::Get('path.root.engine') . '/lib/external/LiveImage/Image.php';

/**
 * Модуль обработки изображений
 * Использует библиотеку LiveImage
 *
 * @package engine.modules
 * @since 1.0
 */
class ModuleImage extends Module
{
    /**
     * Неопределенный тип ошибки при загрузке изображения
     */
    const UPLOAD_IMAGE_ERROR = 1;
    /**
     * Ошибка избыточного размера при загрузке изображения
     */
    const UPLOAD_IMAGE_ERROR_SIZE = 2;
    /**
     * Неизвестный тип изображения
     */
    const UPLOAD_IMAGE_ERROR_TYPE = 4;
    /**
     * Ошибка чтения файла при загрузке изображения
     */
    const UPLOAD_IMAGE_ERROR_READ = 8;
    /**
     * Внутреннаяя ошибка файловой системы сервера
     */
    const UPLOAD_IMAGE_ERROR_FS = 16;
    /**
     * Ошибка удаленного сервера
     */
    const UPLOAD_IMAGE_ERROR_NETWORK = 32;
    /**
     * Тескт последней ошибки
     *
     * @var string
     */
    protected $sLastErrorText = null;
    private $fileInfo;
    private $magic;

    /**
     * Инициализация модуля
     */
    public function Init()
    {
        $this->fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->magic = new Imagick();
    }

    public function Shutdown()
    {
        if ($this->fileInfo) {
            finfo_close($this->fileInfo);
        }
        $this->magic->clear();
    }

    /**
     * Получает текст последней ошибки
     *
     * @return string
     */
    public function GetLastError()
    {
        return $this->sLastErrorText;
    }

    /**
     * Resize,copy image,
     * make rounded corners and add watermark
     *
     * @param  string $sFileSrc Исходный файл изображения
     * @param  string $sDirDest Директория куда нужно сохранить изображение относительно корня сайта (path.root.server)
     * @param  string $sFileDest Имя файла для сохранения, без расширения
     * @param  int $iWidthMax Максимально допустимая ширина изображения
     * @param  int $iHeightMax Максимало допустимая высота изображения
     * @param  int|null $iWidthDest Ширина необходимого изображения на выходе
     * @param  int|null $iHeightDest Высота необходимого изображения на выходе
     * @param  bool $bForcedMinSize Растягивать изображение по ширине или нет, если исходное меньше. При false - изображение будет растянуто
     * @param  LiveImage|null $oImage Объект изображения, если null то будет содано автоматически
     * @return string|bool    Полный серверный путь до сохраненного изображения
     */
    public function Resize($sFileSrc, $sDirDest, $sFileDest, $iWidthMax, $iHeightMax, $iWidthDest = null, $iHeightDest = null, $bForcedMinSize = true, $oImage = null)
    {
        $this->ClearLastError();
        /**
         * Если объект не передан как параметр,
         * создаем новый
         */
        if (!$oImage) $oImage = $this->CreateImageObject($sFileSrc);

        if ($oImage->get_last_error()) {
            $this->SetLastError($oImage->get_last_error());
            return false;
        }

        $sFileDest .= '.' . $oImage->get_image_params('format');
        if (($oImage->get_image_params('width') > $iWidthMax)
            or ($oImage->get_image_params('height') > $iHeightMax)) {
            return false;
        }

        if ($iWidthDest) {
            if ($bForcedMinSize and ($iWidthDest > $oImage->get_image_params('width'))) {
                $iWidthDest = $oImage->get_image_params('width');
            }
            /**
             * Ресайзим и выводим результат в файл.
             * Если не задана новая высота, то применяем масштабирование.
             * Если нужно добавить Watermark, то запрещаем ручное управление alfa-каналом
             */
            $oImage->resize($iWidthDest, $iHeightDest, (!$iHeightDest), (true));

            $sFileTmp = Config::Get('sys.cache.dir') . func_generator(20);
            $oImage->output(null, $sFileTmp);
            return $this->SaveFile($sFileTmp, $sDirDest, $sFileDest, 0666, true);
        } else {
            return $this->SaveFile($sFileSrc, $sDirDest, $sFileDest, 0666, false);
        }
    }

    /**
     * Очищает текст последней ошибки
     *
     */
    public function ClearLastError()
    {
        $this->sLastErrorText = null;
    }

    /**
     * Возвращает объект изображения
     *
     * @param $sFile string    Путь до изображения
     * @return LiveImage
     */
    public function CreateImageObject($sFile)
    {
        return new LiveImage($sFile);
    }

    /**
     * Устанавливает текст последней ошибки
     *
     * @param string $sText Текст ошибки
     */
    public function SetLastError($sText)
    {
        $this->sLastErrorText = $sText;
    }

    /**
     * Сохраняет(копирует) файл изображения на сервер
     * Если переопределить данный метод, то можно сохранять изображения, например, на Amazon S3
     *
     * @param string $sFileSource Полный путь до исходного файла
     * @param string $sDirDest Каталог для сохранения файла относительно корня сайта
     * @param string $sFileDest Имя файла для сохранения
     * @param int|null $iMode Права chmod для файла, например, 0777
     * @param bool $bRemoveSource Удалять исходный файл или нет
     * @return bool | string         Серверный путь до файла
     */
    public function SaveFile($sFileSource, $sDirDest, $sFileDest, $iMode = null, $bRemoveSource = false)
    {
        $sFileDestFullPath = rtrim(Config::Get('path.uploads.storage'), "/") . '/' . trim($sDirDest, "/") . '/' . $sFileDest;
        $this->CreateDirectory($sDirDest);

        $bResult = copy($sFileSource, $sFileDestFullPath);
        if ($bResult and !is_null($iMode)) {
            chmod($sFileDestFullPath, $iMode);
        }
        if ($bRemoveSource) {
            unlink($sFileSource);
        }
        if ($bResult) {
            return $sFileDestFullPath;
        }
        return false;
    }

    /**
     * Создает каталог по указанному адресу (с учетом иерархии)
     *
     * @param string $sDirDest Каталог относительно корня сайта
     */
    public function CreateDirectory($sDirDest)
    {
        @func_mkdir(Config::Get('path.uploads.storage'), $sDirDest);
    }

    /**
     * Проверяет корректность изображения по заданому пути
     *
     * @param string $sFile Путь к файлу
     * @return boolean|string
     */
    public function ValidateImageFile($sFile)
    {
        if (!$this->fileInfo) {
            error_log("Failed to open mime database");
            return $this::UPLOAD_IMAGE_ERROR;
        }
        if (!file_exists($sFile)) {
            error_log("File '$sFile' don't exist!");
            return $this::UPLOAD_IMAGE_ERROR_FS;
        }
        $sMime = finfo_file($this->fileInfo, $sFile);
        $aAllowedMimes = Config::Get('module.image.allowed_mime');
        if (!array_key_exists($sMime, $aAllowedMimes)) {
            return $this::UPLOAD_IMAGE_ERROR_TYPE;
        }
        $this->magic->readImage($sFile);
        if (
            (int)$this->magic->getImageWidth() <= Config::Get('module.image.max_x') &&
            (int)$this->magic->getImageHeight() <= Config::Get('module.image.max_y')
        ) {
            return $aAllowedMimes[$sMime];
        } else {
            return $this::UPLOAD_IMAGE_ERROR_TYPE;
        }
    }

    /**
     * Вырезает максимально возможный квадрат
     *
     * @param  LiveImage $oImage Объект изображения
     * @return LiveImage
     */
    public function CropSquare(LiveImage $oImage, $bCenter = true)
    {
        if (!$oImage || $oImage->get_last_error()) {
            return false;
        }
        $iWidth = $oImage->get_image_params('width');
        $iHeight = $oImage->get_image_params('height');
        /**
         * Если высота и ширина совпадают, то возвращаем изначальный вариант
         */
        if ($iWidth == $iHeight) {
            return $oImage;
        }

        /**
         * Вырезаем квадрат из центра
         */
        $iNewSize = min($iWidth, $iHeight);

        if ($bCenter) {
            $oImage->crop($iNewSize, $iNewSize, ($iWidth - $iNewSize) / 2, ($iHeight - $iNewSize) / 2);
        } else {
            $oImage->crop($iNewSize, $iNewSize, 0, 0);
        }
        /**
         * Возвращаем объект изображения
         */
        return $oImage;
    }

    /**
     * Удаление файла изображения
     *
     * @param string $sFile Полный серверный путь до файла
     * @return bool
     */
    public function RemoveFile($sFile)
    {
        if (file_exists($sFile)) {
            return unlink($sFile);
        }
        return false;
    }

    /**
     * Возвращает серверный адрес по переданному web-адресу
     *
     * @param  string $sPath WEB адрес изображения
     * @return string
     */
    public function GetServerPath($sPath)
    {
        return rtrim(Config::Get('path.uploads.storage'), '/') . '/' . ltrim(parse_url($sPath, PHP_URL_PATH), '/');
    }

    /**
     * Возвращает WEB адрес по переданному серверному адресу
     *
     * @param  string $sPath Серверный адрес(путь) изображения
     * @return string
     */
    public function GetWebPath($sPath)
    {
        return str_replace(Config::Get('path.uploads.storage'), Config::Get('path.uploads.url'), $sPath);
    }

    /**
     * Получает директорию для данного пользователя
     * Используется фомат хранения данных (us/er/id/yyyy/mm/dd/file.jpg)
     *
     * @param  int $sId Целое число, обычно это ID пользователя
     * @return string
     */
    public function GetIdDir($sId)
    {
        return preg_replace('~(.{2})~U', "\\1/", str_pad($sId, 6, "0", STR_PAD_LEFT)) . date('Y/m/d');
    }

    /**
     * Возвращает валидный Html код тега <img>
     *
     * @param  string $sPath WEB адрес изображения
     * @param  array $aParams Параметры
     * @return string
     */
    public function BuildHTML($sPath, $aParams)
    {
        $sText = '<img src="' . $sPath . '"';
        if (isset($aParams['title']) and $aParams['title'] != '') {
            $sText .= ' title="' . htmlspecialchars($aParams['title']) . '"';
        }
        $sText .= ' />';

        return $sText;
    }
}
