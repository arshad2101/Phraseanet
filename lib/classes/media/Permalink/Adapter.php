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
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Doctrine\DBAL\DBALException;
use Guzzle\Http\Url;

class media_Permalink_Adapter implements cache_cacheableInterface
{
    /** @var databox */
    protected $databox;
    /** @var media_subdef */
    protected $media_subdef;
    /** @var int */
    protected $id;
    /** @var string */
    protected $token;
    /** @var boolean */
    protected $is_activated;
    /** @var DateTime */
    protected $created_on;
    /** @var DateTime */
    protected $last_modified;
    /** @var string */
    protected $label;
    /** @var Application */
    protected $app;

    /**
     * @param Application   $app
     * @param databox       $databox
     * @param media_subdef  $media_subdef
     */
    public function __construct(Application $app, databox $databox, media_subdef $media_subdef)
    {
        $this->app = $app;
        $this->databox = $databox;
        $this->media_subdef = $media_subdef;

        $this->load();
    }

    /**
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_token()
    {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function get_is_activated()
    {
        return $this->is_activated;
    }

    /**
     * @return DateTime
     */
    public function get_created_on()
    {
        return $this->created_on;
    }

    /**
     * @return DateTime
     */
    public function get_last_modified()
    {
        return $this->last_modified;
    }

    /**
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }

    /**
     * @return Url
     */
    public function get_url()
    {
        $label = $this->get_label() . '.' . pathinfo($this->media_subdef->get_file(), PATHINFO_EXTENSION);

        return Url::factory($this->app->url('permalinks_permalink', [
            'sbas_id'   => $this->media_subdef->get_sbas_id(),
            'record_id' => $this->media_subdef->get_record_id(),
            'subdef'    => $this->media_subdef->get_name(),
            /** @Ignore */
            'label'     => $label,
            'token'     => $this->get_token(),
        ]));
    }

    /**
     * @return string
     */
    public function get_page()
    {
        return $this->app->url('permalinks_permaview', [
            'sbas_id'   => $this->media_subdef->get_sbas_id(),
            'record_id' => $this->media_subdef->get_record_id(),
            'subdef'    => $this->media_subdef->get_name(),
            'token'     => $this->get_token(),
        ]);
    }

    /**
     * @param  string $token
     * @return $this
     */
    protected function set_token($token)
    {
        $this->token = $token;

        $sql = 'UPDATE permalinks SET token = :token, last_modified = NOW()
            WHERE id = :id';
        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute([':token' => $this->token, ':id'    => $this->get_id()]);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * @param  string                  $is_activated
     * @return $this
     */
    public function set_is_activated($is_activated)
    {
        $this->is_activated = ! ! $is_activated;

        $sql = 'UPDATE permalinks SET activated = :activated, last_modified = NOW()
            WHERE id = :id';
        $stmt = $this->databox->get_connection()->prepare($sql);

        $params = [
            ':activated' => $this->is_activated,
            ':id'        => $this->get_id()
        ];

        $stmt->execute($params);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * @param  string $label
     * @return $this
     */
    public function set_label($label)
    {
        $label = trim($label) ? trim($label) : 'untitled';
        while (strpos($label, '  ') !== false)
            $label = str_replace('  ', ' ', $label);

        $this->label = $this->app['unicode']->remove_nonazAZ09(
            str_replace(' ', '-', $label)
        );

        $sql = 'UPDATE permalinks SET label = :label, last_modified = NOW()
            WHERE id = :id';
        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute([':label' => $this->label, ':id'    => $this->get_id()]);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * @return $this
     */
    protected function load()
    {
        try {
            $datas = $this->get_data_from_cache();
            $this->id = $datas['id'];
            $this->token = $datas['token'];
            $this->is_activated = $datas['is_activated'];
            $this->created_on = $datas['created_on'];
            $this->last_modified = $datas['last_modified'];
            $this->label = $datas['label'];

            return $this;
        } catch (\Exception $e) {

        }

        $sql = '
            SELECT p.id, p.token, p.activated, p.created_on, p.last_modified, p.label
            FROM permalinks p
            WHERE p.subdef_id = :subdef_id';
        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute([':subdef_id' => $this->media_subdef->get_subdef_id()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            throw new Exception_Media_SubdefNotFound ();
        }

        $this->id = (int) $row['id'];
        $this->token = $row['token'];
        $this->is_activated = ! ! $row['activated'];
        $this->created_on = new DateTime($row['created_on']);
        $this->last_modified = new DateTime($row['last_modified']);
        $this->label = $row['label'];

        $datas = [
            'id'            => $this->id,
            'token'         => $this->token,
            'is_activated'  => $this->is_activated,
            'created_on'    => $this->created_on,
            'last_modified' => $this->last_modified,
            /** @Ignore */
            'label'         => $this->label,
        ];

        $this->set_data_to_cache($datas);

        return $this;
    }

    /**
     * @param  Application  $app
     * @param  databox      $databox
     * @param  media_subdef $media_subdef
     * @return $this
     */
    public static function getPermalink(Application $app, databox $databox, media_subdef $media_subdef)
    {
        try {
            return new self($app, $databox, $media_subdef);
        } catch (\Exception $e) {

        }

        return self::create($app, $databox, $media_subdef);
    }

    /**
     * @param  Application  $app
     * @param  databox      $databox
     * @param  media_subdef $media_subdef
     * @return $this
     */
    public static function create(Application $app, databox $databox, media_subdef $media_subdef)
    {
        $sql = 'INSERT INTO permalinks
            (id, subdef_id, token, activated, created_on, last_modified, label)
            VALUES (null, :subdef_id, :token, :activated, NOW(), NOW(), "")';

        $params = [
            ':subdef_id' => $media_subdef->get_subdef_id()
            , ':token'     => $app['random.medium']->generateString(64, TokenManipulator::LETTERS_AND_NUMBERS)
            , ':activated' => '1'
        ];

        $error = null;
        $stmt = $databox->get_connection()->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (DBALException $e) {
            $error = $e;
        }
        $stmt->closeCursor();

        if ($error) {
            throw new RuntimeException('Permalink already exists', $e->getCode(), $e);
        }

        $permalink = self::getPermalink($app, $databox, $media_subdef);
        $permalink->set_label(strip_tags($media_subdef->get_record()->get_title(false, null, true)));

        return $permalink;
    }

    public function get_cache_key($option = null)
    {
        return 'permalink_' . $this->media_subdef->get_subdef_id() . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        return $this->databox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        $this->databox->delete_data_from_cache($this->get_cache_key($option));
    }
}
