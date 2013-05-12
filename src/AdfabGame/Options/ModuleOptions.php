<?php

namespace AdfabGame\Options;

use Zend\Stdlib\AbstractOptions;

class ModuleOptions extends AbstractOptions implements GameEditOptionsInterface
{
    /**
     * Turn off strict options mode
     */
    protected $__strictMode__ = false;

    /**
     * drive path to game media files
     */
    protected $media_path = 'public/media/game';

    /**
     * url path to game media files
     */
    protected $media_url = 'media/game';

    /**
     * core_layout config
     */
    protected $core_layout = array();

    /**
     * @var string
     */
    protected $gameEntityClass = 'AdfabGame\Entity\Game';
    protected $leaderBoardEntityClass = 'AdfabGame\Entity\LeaderBoard';

    /**
     * Set leaderBoard entity class name
     *
     * @param $leaderBoardEntityClass
     * @return ModuleOptions
     */
    public function setLeaderBoardEntityClass($leaderBoardEntityClass)
    {
        $this->leaderBoardEntityClass = $leaderBoardEntityClass;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLeaderBoardEntityClass()
    {
        return $this->leaderBoardEntityClass;
    }

    /**
     * Set game entity class name
     *
     * @param $gameEntityClass
     * @return ModuleOptions
     */
    public function setGameEntityClass($gameEntityClass)
    {
        $this->gameEntityClass = $gameEntityClass;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGameEntityClass()
    {
        return $this->gameEntityClass;
    }

    /**
     * Set media path
     *
     * @param  string                           $media_path
     * @return \AdfabGame\Options\ModuleOptions
     */
    public function setMediaPath($media_path)
    {
        $this->media_path = $media_path;

        return $this;
    }

    /**
     * @return string
     */
    public function getMediaPath()
    {
        return $this->media_path;
    }

    /**
     *
     * @param  string                           $media_url
     * @return \AdfabGame\Options\ModuleOptions
     */
    public function setMediaUrl($media_url)
    {
        $this->media_url = $media_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->media_url;
    }

    /**
     *
     * @param  string                           $core_layout
     * @return \AdfabGame\Options\ModuleOptions
     */
    public function setCoreLayout($core_layout)
    {
        $this->core_layout = $core_layout;

        return $this;
    }

    /**
     * @return string
     */
    public function getCoreLayout()
    {
        return $this->core_layout;
    }

}
