<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinker;
use Guzzle\Http\Url;
use MediaAlchemyst\Alchemyst;
use MediaVorus\Media\MediaInterface;
use MediaVorus\MediaVorus;

class media_subdef extends media_abstract implements cache_cacheableInterface
{
    /** @var Application */
    protected $app;

    /** @var string */
    protected $mime;

    /** @var string */
    protected $file;

    /** @var string */
    protected $path;

    /** @var record_adapter */
    protected $record;

    /** @var media_Permalink_Adapter */
    protected $permalink;

    /** @var boolean */
    protected $is_substituted = false;

    /** @var string */
    protected $pathfile;

    /** @var int */
    protected $subdef_id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $etag;

    /** @var DateTime */
    protected $creation_date;

    /** @var DateTime */
    protected $modification_date;

    /** @var bool */
    protected $is_physically_present = false;

    /** @var integer */
    private $size = 0;

    /*
     * Players types constants
     */
    const TYPE_VIDEO_MP4 = 'VIDEO_MP4';
    const TYPE_VIDEO_FLV = 'VIDEO_FLV';
    const TYPE_FLEXPAPER = 'FLEXPAPER';
    const TYPE_AUDIO_MP3 = 'AUDIO_MP3';
    const TYPE_IMAGE = 'IMAGE';
    const TYPE_NO_PLAYER = 'UNKNOWN';

    /*
     * Technical datas types constants
     */
    const TC_DATA_WIDTH = 'Width';
    const TC_DATA_HEIGHT = 'Height';
    const TC_DATA_COLORSPACE = 'ColorSpace';
    const TC_DATA_CHANNELS = 'Channels';
    const TC_DATA_ORIENTATION = 'Orientation';
    const TC_DATA_COLORDEPTH = 'ColorDepth';
    const TC_DATA_DURATION = 'Duration';
    const TC_DATA_AUDIOCODEC = 'AudioCodec';
    const TC_DATA_AUDIOSAMPLERATE = 'AudioSamplerate';
    const TC_DATA_VIDEOCODEC = 'VideoCodec';
    const TC_DATA_FRAMERATE = 'FrameRate';
    const TC_DATA_MIMETYPE = 'MimeType';
    const TC_DATA_FILESIZE = 'FileSize';
    const TC_DATA_LONGITUDE = 'Longitude';
    const TC_DATA_LONGITUDE_REF = 'LongitudeRef';
    const TC_DATA_LATITUDE = 'Latitude';
    const TC_DATA_LATITUDE_REF = 'LatitudeRef';
    const TC_DATA_FOCALLENGTH = 'FocalLength';
    const TC_DATA_CAMERAMODEL = 'CameraModel';
    const TC_DATA_FLASHFIRED = 'FlashFired';
    const TC_DATA_APERTURE = 'Aperture';
    const TC_DATA_SHUTTERSPEED = 'ShutterSpeed';
    const TC_DATA_HYPERFOCALDISTANCE = 'HyperfocalDistance';
    const TC_DATA_ISO = 'ISO';
    const TC_DATA_LIGHTVALUE = 'LightValue';

    /**
     * @param  Application    $app
     * @param  record_adapter $record
     * @param  string         $name
     * @param  bool           $substitute
     */
    public function __construct(Application $app, record_adapter $record, $name, $substitute = false)
    {
        $this->app = $app;
        $this->name = $name;
        $this->record = $record;
        $this->load($substitute);

        $this->generate_url();

        parent::__construct($this->width, $this->height, $this->url);
    }

    /**
     * @param  bool $substitute
     * @return $this
     */
    protected function load($substitute)
    {
        try {
            $datas = $this->get_data_from_cache();
            if (!is_array($datas)) {
                throw new \Exception('Could not retrieve data');
            }
            $this->mime = $datas['mime'];
            $this->width = $datas['width'];
            $this->height = $datas['height'];
            $this->size = $datas['size'];
            $this->etag = $datas['etag'];
            $this->path = $datas['path'];
            $this->url = $datas['url'];
            $this->file = $datas['file'];
            $this->is_physically_present = $datas['physically_present'];
            $this->is_substituted = $datas['is_substituted'];
            $this->subdef_id = $datas['subdef_id'];
            $this->modification_date = $datas['modification_date'];
            $this->creation_date = $datas['creation_date'];

            return $this;
        } catch (\Exception $e) {

        }

        $connbas = $this->record->getDatabox()->get_connection();

        $sql = "SELECT subdef_id, name, file, width, height, mime,"
            . " path, size, substit, created_on, updated_on, etag"
            . " FROM subdef"
            . " WHERE name = :name AND record_id = :record_id";

        $params = [
            ':record_id' => $this->record->getRecordId(),
            ':name'      => $this->name
        ];

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $this->width = (int) $row['width'];
            $this->size = (int) $row['size'];
            $this->height = (int) $row['height'];
            $this->mime = $row['mime'];
            $this->file = $row['file'];
            $this->path = p4string::addEndSlash($row['path']);
            $this->is_physically_present = file_exists($this->getRealPath());
            $this->etag = $row['etag'];
            $this->is_substituted = ! ! $row['substit'];
            $this->subdef_id = (int) $row['subdef_id'];

            if ($row['updated_on'])
                $this->modification_date = new DateTime($row['updated_on']);
            if ($row['created_on'])
                $this->creation_date = new DateTime($row['created_on']);

        } elseif ($substitute === false) {
            throw new Exception_Media_SubdefNotFound($this->name . ' not found');
        }

        if (! $row) {
            $this->find_substitute_file();
        }

        $datas = [
            'mime'               => $this->mime,
            'width'              => $this->width,
            'size'               => $this->size,
            'height'             => $this->height,
            'etag'               => $this->etag,
            'path'               => $this->path,
            'url'                => $this->url,
            'file'               => $this->file,
            'physically_present' => $this->is_physically_present,
            'is_substituted'     => $this->is_substituted,
            'subdef_id'          => $this->subdef_id,
            'modification_date'  => $this->modification_date,
            'creation_date'      => $this->creation_date,
        ];

        $this->set_data_to_cache($datas);

        return $this;
    }

    /**
     * Removes the file associated to a subdef
     *
     * @return \media_subdef
     */
    public function remove_file()
    {
        if ($this->is_physically_present() && is_writable($this->getRealPath())) {
            unlink($this->getRealPath());

            $this->delete_data_from_cache();

            if ($this->get_permalink() instanceof media_Permalink_Adapter) {
                $this->get_permalink()->delete_data_from_cache();
            }

            $this->find_substitute_file();
        }

        return $this;
    }

    /**
     * Find a substitution file for a subdef
     *
     * @return \media_subdef
     */
    protected function find_substitute_file()
    {
        if ($this->record->isStory()) {
            $this->mime = 'image/png';
            $this->width = 256;
            $this->height = 256;
            $this->path = $this->app['root.path'] . '/www/assets/common/images/icons/substitution/';
            $this->file = 'regroup_thumb.png';
            $this->url = Url::factory('/assets/common/images/icons/substitution/regroup_thumb.png');
        } else {
            $mime = $this->record->getMimeType();
            $mime = trim($mime) != '' ? str_replace('/', '_', $mime) : 'application_octet-stream';

            $this->mime = 'image/png';
            $this->width = 256;
            $this->height = 256;
            $this->path = $this->app['root.path'] . '/www/assets/common/images/icons/substitution/';
            $this->file = str_replace('+', '%20', $mime) . '.png';
            $this->url = Url::factory('/assets/common/images/icons/substitution/' . $this->file);
        }

        $this->is_physically_present = false;

        if ( ! file_exists($this->path . $this->file)) {
            $this->path = $this->app['root.path'] . '/www/assets/common/images/icons/';
            $this->file = 'substitution.png';
            $this->url = Url::factory('/assets/common/images/icons/' . $this->file);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function is_physically_present()
    {
        return $this->is_physically_present;
    }

    /**
     * @return record_adapter
     */
    public function get_record()
    {
        return $this->record;
    }

    /**
     * @return media_Permalink_Adapter
     */
    public function get_permalink()
    {
        if ( ! $this->permalink && $this->is_physically_present())
            $this->permalink = media_Permalink_Adapter::getPermalink($this->app, $this->record->getDatabox(), $this);

        return $this->permalink;
    }

    /**
     * @return int
     */
    public function get_record_id()
    {
        return $this->record->getRecordId();
    }

    public function getEtag()
    {
        if (!$this->etag && $this->is_physically_present()) {
            $file = new SplFileInfo($this->getRealPath());
            if ($file->isFile()) {
                $this->setEtag(md5($file->getMTime()));
            }
        }

        return $this->etag;
    }

    public function setEtag($etag)
    {
        $this->etag = $etag;

        $sql = "UPDATE subdef SET etag = :etag WHERE subdef_id = :subdef_id";
        $stmt = $this->record->getDatabox()->get_connection()->prepare($sql);
        $stmt->execute([':subdef_id' => $this->subdef_id, ':etag'      => $etag]);
        $stmt->closeCursor();

        return $this;
    }

    public function set_substituted($substit)
    {
        $this->is_substituted = !!$substit;

        $sql = "UPDATE subdef SET substit = :substit, updated_on=NOW() WHERE subdef_id = :subdef_id";
        $stmt = $this->record->getDatabox()->get_connection()->prepare($sql);
        $stmt->execute(array(
            ':subdef_id' => $this->subdef_id,
            ':substit'   => $this->is_substituted
        ));
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * @return int
     */
    public function get_sbas_id()
    {
        return $this->record->getDataboxId();
    }

    /**
     * @return string
     */
    public function get_type()
    {
        switch ($this->mime) {
            case 'video/mp4':
                $type = self::TYPE_VIDEO_MP4;
                break;
            case 'video/x-flv':
                $type = self::TYPE_VIDEO_FLV;
                break;
            case 'application/x-shockwave-flash':
                $type = self::TYPE_FLEXPAPER;
                break;
            case 'audio/mpeg':
            case 'audio/mp3':
                $type = self::TYPE_AUDIO_MP3;
                break;
            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
                $type = self::TYPE_IMAGE;
                break;
            default:
                $type = self::TYPE_NO_PLAYER;
                break;
        }

        return $type;
    }

    /**
     * @return string
     */
    public function get_mime()
    {
        return $this->mime;
    }

    /**
     * @return string
     */
    public function get_path()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function get_file()
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function get_size()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function get_subdef_id()
    {
        return $this->subdef_id;
    }

    /**
     * @return bool
     */
    public function is_substituted()
    {
        return $this->is_substituted;
    }

    /**
     * @return string
     * @deprecated use {@link self::getRealPath} instead
     */
    public function get_pathfile()
    {
        return $this->getRealPath();
    }

    /**
     * @return DateTime
     */
    public function get_modification_date()
    {
        return $this->modification_date;
    }

    /**
     * @return DateTime
     */
    public function get_creation_date()
    {
        return $this->creation_date;
    }

    /**
     * @return string
     */
    public function renew_url()
    {
        $this->generate_url();

        return $this->get_url();
    }

    /**
     * Return the databox subdef corresponding to the subdef
     *
     * @return \databox_subdef
     */
    public function getDataboxSubdef()
    {
        return $this->record
                ->getDatabox()
                ->get_subdef_structure()
                ->get_subdef($this->record->getType(), $this->get_name());
    }

    public function getDevices()
    {
        if ($this->get_name() == 'document') {
            return [\databox_subdef::DEVICE_ALL];
        }

        try {
            return $this->record
                    ->getDatabox()
                    ->get_subdef_structure()
                    ->get_subdef($this->record->getType(), $this->get_name())
                    ->getDevices();
        } catch (\Exception_Databox_SubdefNotFound $e) {
            return [];
        }
    }

    /**
     * @param int        $angle
     * @param Alchemyst  $alchemyst
     * @param MediaVorus $mediavorus
     *
     * @return media_subdef
     */
    public function rotate($angle, Alchemyst $alchemyst, MediaVorus $mediavorus)
    {
        if ( ! $this->is_physically_present()) {
            throw new \Alchemy\Phrasea\Exception\RuntimeException('You can not rotate a substitution');
        }

        $specs = new \MediaAlchemyst\Specification\Image();
        $specs->setRotationAngle($angle);

        try {
            $alchemyst->turnInto($this->getRealPath(), $this->getRealPath(), $specs);
        } catch (\MediaAlchemyst\Exception\ExceptionInterface $e) {
            return $this;
        }

        $media = $mediavorus->guess($this->getRealPath());

        $sql = "UPDATE subdef SET height = :height , width = :width, updated_on = NOW()"
            . " WHERE record_id = :record_id AND name = :name";

        $params = [
            ':width'     => $media->getWidth(),
            ':height'    => $media->getHeight(),
            ':record_id' => $this->get_record_id(),
            ':name'      => $this->get_name(),
        ];

        $stmt = $this->record->getDatabox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->width = $media->getWidth();
        $this->height = $media->getHeight();

        $this->delete_data_from_cache();

        unset($media);

        return $this;
    }

    /**
     * Read the technical datas of the file.
     * Returns an empty array for non physical present files
     *
     * @return array An array of technical datas Key/values
     */
    public function readTechnicalDatas(MediaVorus $mediavorus)
    {
        if ( ! $this->is_physically_present()) {
            return [];
        }

        $media = $mediavorus->guess($this->getRealPath());

        $datas = [];

        $methods = [
            self::TC_DATA_WIDTH              => 'getWidth',
            self::TC_DATA_HEIGHT             => 'getHeight',
            self::TC_DATA_FOCALLENGTH        => 'getFocalLength',
            self::TC_DATA_CHANNELS           => 'getChannels',
            self::TC_DATA_COLORDEPTH         => 'getColorDepth',
            self::TC_DATA_CAMERAMODEL        => 'getCameraModel',
            self::TC_DATA_FLASHFIRED         => 'getFlashFired',
            self::TC_DATA_APERTURE           => 'getAperture',
            self::TC_DATA_SHUTTERSPEED       => 'getShutterSpeed',
            self::TC_DATA_HYPERFOCALDISTANCE => 'getHyperfocalDistance',
            self::TC_DATA_ISO                => 'getISO',
            self::TC_DATA_LIGHTVALUE         => 'getLightValue',
            self::TC_DATA_COLORSPACE         => 'getColorSpace',
            self::TC_DATA_DURATION           => 'getDuration',
            self::TC_DATA_FRAMERATE          => 'getFrameRate',
            self::TC_DATA_AUDIOSAMPLERATE    => 'getAudioSampleRate',
            self::TC_DATA_VIDEOCODEC         => 'getVideoCodec',
            self::TC_DATA_AUDIOCODEC         => 'getAudioCodec',
            self::TC_DATA_ORIENTATION        => 'getOrientation',
            self::TC_DATA_LONGITUDE          => 'getLongitude',
            self::TC_DATA_LONGITUDE_REF      => 'getLongitudeRef',
            self::TC_DATA_LATITUDE           => 'getLatitude',
            self::TC_DATA_LATITUDE_REF       => 'getLatitudeRef',
        ];

        foreach ($methods as $tc_name => $method) {
            if (method_exists($media, $method)) {
                $result = call_user_func([$media, $method]);

                if (null !== $result) {
                    $datas[$tc_name] = $result;
                }
            }
        }

        $datas[self::TC_DATA_MIMETYPE] = $media->getFile()->getMimeType();
        $datas[self::TC_DATA_FILESIZE] = $media->getFile()->getSize();

        unset($media);

        return $datas;
    }

    public static function create(Application $app, \record_adapter $record, $name, MediaInterface $media)
    {
        $databox = $record->getDatabox();
        $connbas = $databox->get_connection();

        $path = $media->getFile()->getPath();
        $newname = $media->getFile()->getFilename();

        $params = [
            ':record_id'  => $record->getRecordId(),
            ':name'       => $name,
            ':path'       => $path,
            ':file'       => $newname,
            ':width'      => 0,
            ':height'     => 0,
            ':mime'       => $media->getFile()->getMimeType(),
            ':size'       => $media->getFile()->getSize(),
            ':dispatched' => 1,
        ];

        if (method_exists($media, 'getWidth') && null !== $media->getWidth()) {
            $params[':width'] = $media->getWidth();
        }
        if (method_exists($media, 'getHeight') && null !== $media->getHeight()) {
            $params[':height'] = $media->getHeight();
        }

        $sql = "INSERT INTO subdef"
            . " (record_id, name, path, file, width, height, mime, size, dispatched, created_on, updated_on)"
            . " VALUES (:record_id, :name, :path, :file, :width, :height, :mime, :size, :dispatched, NOW(), NOW())"
            . " ON DUPLICATE KEY UPDATE"
            . " path = VALUES(path), file = VALUES(file),"
            . " width = VALUES(width) , height = VALUES(height), mime = VALUES(mime),"
            . " size = VALUES(size), dispatched = VALUES(dispatched), updated_on = NOW()";

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $subdef = new self($app, $record, $name);
        $subdef->delete_data_from_cache();

        if ($subdef->get_permalink() instanceof media_Permalink_Adapter) {
            $subdef->get_permalink()->delete_data_from_cache();
        }

        if ($name === 'thumbnail') {
            /** @var SymLinker $symlinker */
            $symlinker = $app['phraseanet.thumb-symlinker'];
            $symlinker->symlink($subdef->getRealPath());
        }

        unset($media);

        return $subdef;
    }

    protected function generate_url()
    {
        if (!$this->is_physically_present()) {
            return;
        }

        // serve thumbnails using static file service
        if ($this->get_name() === 'thumbnail') {
            if (null !== $url = $this->app['phraseanet.static-file']->getUrl($this->getRealPath())) {
                $url->getQuery()->offsetSet('etag', $this->getEtag());
                $this->url = $url;

                return;
            }
        }
        
        if ($this->app['phraseanet.h264-factory']->isH264Enabled() && in_array($this->mime, ['video/mp4'])) {
            if (null !== $url = $this->app['phraseanet.h264']->getUrl($this->getRealPath())) {
                $this->url = $url;

                return;
            }
        }

        $this->url = Url::factory($this->app->path('datafile', [
            'sbas_id' => $this->record->getDataboxId(),
            'record_id' => $this->record->getRecordId(),
            'subdef' => $this->get_name(),
            'etag' => $this->getEtag(),
        ]));

        return;
    }

    public function get_cache_key($option = null)
    {
        return 'subdef_' . $this->get_record()->getId()
            . '_' . $this->name . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        $databox = $this->get_record()->getDatabox();

        return $databox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        $databox = $this->get_record()->getDatabox();

        return $databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        $this->setEtag(null);
        $databox = $this->get_record()->getDatabox();

        $databox->delete_data_from_cache($this->get_cache_key($option));
    }

    /**
     * @return string
     */
    public function getRealPath()
    {
        return $this->path . $this->file;
    }

    /**
     * @return string
     */
    public function getWatermarkRealPath()
    {
        return $this->path . 'watermark_' . $this->file;
    }

    /**
     * @return string
     */
    public function getStampRealPath()
    {
        return $this->path . 'stamp_' . $this->file;
    }
}
