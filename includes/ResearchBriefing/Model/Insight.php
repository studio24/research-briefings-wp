<?php


namespace ResearchBriefing\Model;

use DateTime;
use Exception;

class Insight implements SearchInterface
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var DateTime
     */
    protected $date;
    /**
     * @var string
     */
    protected $excerpt;
    /**
     * @var string
     */
    protected $content;
    /**
     * @var int
     */
    protected $site;
    /**
     * @var string
     */
    protected $permalinkSlug;
    /**
     * @var string
     */
    protected $thumbnail;
    /**
     * @var string
     */
    protected $tags;

    /**
     * Model constructor.
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        if (empty($data)) {
            return;
        }

        // Set data to the model
//        foreach ($data as $key => $value) {
//            $method = 'set' . Str::toCamelCase($key);
//
//            if (method_exists($this, $method)) {
//                $this->$method($value);
//
//            }
//        }

    }

    /**
     * Mapping the main insight wp post properties
     *
     * @param $data
     * @throws Exception
     */
    public function setMappedFields($data)
    {
        if (isset($data->ID)){
            $this->setId($data->ID);
        }

        if (isset($data->post_title)){
            $this->setTitle($data->post_title);
        }

        if (isset($data->post_excerpt)){
            $this->setExcerpt($data->post_excerpt);
        }

        if (isset($data->post_content)){
            $this->setContent($data->post_content);
        }

        if (isset($data->post_date)){
            $this->setDate($data->post_date);
        }

    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Insight
     */
    public function setId(string $id): Insight
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Insight
     */
    public function setTitle(string $title): Insight
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     * @return Insight
     * @throws Exception
     */
    public function setDate($date): Insight
    {
        if (!$date instanceof DateTime) {

            $date = new DateTime($date);
        }

        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    /**
     * @param string $excerpt
     * @return Insight
     */
    public function setExcerpt(string $excerpt): Insight
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return Insight
     */
    public function setContent(string $content): Insight
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getPermalinkSlug(): ?string
    {
        return $this->permalinkSlug;
    }

    /**
     * @param string $permalinkSlug
     * @return Insight
     */
    public function setPermalinkSlug(string $permalinkSlug): Insight
    {
        $this->permalinkSlug = $permalinkSlug;
        return $this;
    }

    /**
     * @return string
     */
    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     * @return Insight
     */
    public function setThumbnail(string $thumbnail): Insight
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }

    /**
     * @return string
     */
    public function getTags(): ?string
    {
        return $this->tags;
    }

    /**
     * @param string $tags
     * @return Insight
     */
    public function setTags(string $tags): Insight
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Method that builds the customized content field for the search table
     * @return string
     */
    public function getSearchContent()
    {
       return $this->getId() .'|'. $this->getExcerpt() . '|' . strip_tags($this->getContent());
    }

    /**
     * @return int
     */
    public function getSite(): int
    {
        return $this->site;
    }

    /**
     * @param int $site
     * @return Insight
     */
    public function setSite(int $site): Insight
    {
        $this->site = $site;
        return $this;
    }

}
