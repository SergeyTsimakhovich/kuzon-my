<?php namespace Cds\Study\Models;

use System\Models\File;
use File as FileHelper;
use October\Rain\Network\Http;
use Symfony\Component\HttpFoundation\File\File as FileObj;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * File Model
 */
class CdsFile extends File
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'cds_study_files';

    public static $allowedTypes = [
        "application/msword" => "doc",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "doc",
        "application/excel" => "xlsx",
        "application/pdf" => "pdf",
        "application/zip" => "doc",
        "image/gif" => "gif",
        "image/png" => "png",
        "image/jpeg" => "jpg",
        "image/webp" => "webp",
        "image/svg+xml" => "svg",
    ];

    public $fileImagePath = [
        'doc' => 'assets/img/images/files/doc.png',
        'pdf' => 'assets/img/images/files/pdf.png',
    ];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'id',
        'url' => [
            'nullable',
            'regex:/(http:|https:)?(\/\/)?(player\.|www\.)?(rutube\.ru|vimeo\.com|youtu(be\.com|\.be))\/(video\/|embed\/|watch\?v=|v\/)?([A-Za-z0-9._%-]*)(\&\S+)?/'
        ],
        'title' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:255'
    ];

    protected $customMessages = [
        'url.regex' => 'Можно использовать только ссылки с YouTube, Rutube, Vimeo',
    ];

    protected $attributeNames = [
        'title' => 'наименование',
        'description' => 'описание'
    ];

    public function getFileImg()
    {
        $type = self::$allowedTypes[$this->content_type];
        $img = !empty($this->fileImagePath[$type]) ? $this->fileImagePath[$type] : $this->getPath();

        return $img;
    }

    /** Метод для валидации файлов загруженных через дропзону */
    public static function validateFile($files = [], $allowTypes = ['jpg', 'jpeg', 'png', 'doc', 'docx', 'pdf'], $size = 15360)
    {
        $validFiles = [];
        if (!empty($files)) {
            foreach ($files as $file)
            {
                $item = self::find($file);
                if (!empty($item)) {
                    $getType = !empty(self::$allowedTypes[$item->content_type]) ? self::$allowedTypes[$item->content_type] : null;
                    if (!in_array($getType, $allowTypes) ) {
                        $allowTypesString =  mb_strtoupper(implode(", ", $allowTypes));
                        self::showErrorFiles("Для загрузки разрешены файлы форматов {$allowTypesString}");
                    }

                    if (($item->file_size / 1024) > $size) {
                        $mbSize = round($size/1024,1);
                        self::showErrorFiles("Максимальный размер загружаемого файла {$mbSize} МБ");
                    }

                    $validFiles[] = $item;
                }
            }
        }

        return $validFiles;
    }

    /**
     * Метод для сохранения в базу данных о видео (ссылка на плеер, ссылка на видео)
     */
    public function fromFileVideo($filePath, $videoData)
    {
        if ($filePath === null) {
            return;
        }

        $file = new FileObj($filePath);
        $this->file_name = $file->getFilename();
        $this->file_size = $file->getSize();
        $this->content_type = $file->getMimeType();
        $this->disk_name = $this->getDiskName();

        $this->is_video = true;
        $this->url   = $videoData['url'];
        $this->embed = $videoData['embed'];

        $this->putFile($file->getRealPath(), $this->disk_name);

        return $this;
    }

    public function fromUrlVideo($url, $filename = null)
    {
        if (empty($url)) $this->showError('Необходимо указать ссылку на видео с YouTube');

        $videoData = $this->getThumbVideo($this->getVideoID($url));
        $videoData['url'] = $url;

        $data = Http::get($videoData['thumb']);

        if ($data->code != 200) {
            throw new Exception(sprintf('Ошибка получения файла "%s", код ошибки: %d', $data->url, $data->code));
        }

        if (empty($filename)) {
            $filename = FileHelper::basename($videoData['thumb']);
        }

        if ($data === null) {
            return;
        }

        $tempPath = temp_path($filename);
        FileHelper::put($tempPath, $data);

        $file = $this->fromFileVideo($tempPath, $videoData);
        FileHelper::delete($tempPath);

        return $file;
    }

    /**
     * Анализируем ссылку, получаем хост и ID видео
     *
     * @param [type] $url
     * @return void
     */
    public function getVideoID ($url) {
        if (!is_string($url) || empty($url)) return false;
        $url = str_replace("&amp;", "&", $url);
        $arr = parse_url($url);

        if (!isset($arr['host'])) {
            $this->showError('Разрешено указывать только ссылки YouTube');
        }

        $arr['host'] = str_replace('www.', '', $arr['host']);

        $dataUrl = ['host' => $arr['host']];

        switch ($dataUrl['host']) {
            case 'rutube.ru':
                if (preg_match("/\/video\/([a-zA-Z0-9]+)/", $arr['path'], $matches)) {
                    $dataUrl['video_id'] = $matches[1];
                }
                break;
            case 'youtube.com':
                if (preg_match('/(http(s|):|)\/\/(www\.|)yout(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $url, $matches)) {
                    $dataUrl['video_id'] = $matches[6];
                }
                break;
            case 'youtu.be':
                if (preg_match('/(http(s|):|)\/\/(www\.|)yout(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $url, $matches)) {
                    $dataUrl['video_id'] = $matches[6];
                }
                break;
            case 'vimeo.com':
                if (preg_match("/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/", $url, $matches))
                {
                    $dataUrl['video_id'] = $matches[5];
                }
                break;
            default:
                $dataUrl['host'] = null;
                break;
        }

        if ( !empty($dataUrl['host']) && empty($dataUrl['video_id']) ) {
            $this->showError('Введите корректную ссылку на видео');
        }

        if (empty($dataUrl['host'])) {
            $this->showError('Разрешено только видео с YouTube');
        }

        return $dataUrl;
    }


    /**
     * Передаем хост и ID видео для получения превью видео
     *
     * @param [type] $data
     * @return void
     */
    protected function getThumbVideo($data)
    {
        switch ($data['host']) {
            case 'youtube.com':
                return [
                    'thumb' => "http://img.youtube.com/vi/".$data['video_id']."/hqdefault.jpg",
                    'embed' => "https://www.youtube.com/embed/".$data['video_id']."?enablejsapi=1"
                ];
                break;
            case 'youtu.be':
                return [
                    'thumb' => "http://img.youtube.com/vi/".$data['video_id']."/hqdefault.jpg",
                    'embed' => "https://www.youtube.com/embed/".$data['video_id']."?enablejsapi=1"
                ];
                break;
            case 'rutube.ru':
                $resource = simplexml_load_file("http://rutube.ru/cgi-bin/xmlapi.cgi?rt_mode=movie&rt_movie_id=".$data['video_id']."&utf=1");
                if ($resource) {
                    return [
                        'thumb' => (string) $resource->thumbnail_url,
                        'embed' => (string) $resource->embed_url,
                    ];
                }
                break;
            case 'vimeo.com':
                $resource = unserialize(file_get_contents("http://vimeo.com/api/v2/video/".$data['video_id'].".php"));
                if ($resource) {
                    return [
                        'thumb' => (string) $resource[0]['thumbnail_large'],
                        'embed' => "https://player.vimeo.com/video/".$data['video_id']
                    ];
                }
                break;
            default:
                return "";
                break;
        }
    }

    protected function showError($value)
    {
        throw new \ValidationException(['url' => $value]);
    }

    protected static function showErrorFiles($value)
    {
        throw new \ValidationException(['files' => $value]);
    }
}
