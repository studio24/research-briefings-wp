<?php


namespace ResearchBriefing\Model;

use http\QueryString;
use Nayjest\StrCaseConverter\Str;

class Search
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
    protected $briefingId;
    /**
     * @var string
     */
    protected $postId;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $date;
    /**
     * @var string
     */
    protected $year;
    /**
     * @var string
     */
    protected $thumbnailLink;
    /**
     * @var string
     */
    protected $categories;

    /** @var  */
    protected $types;

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
        foreach ($data as $key => $value) {
            $method = 'set' . Str::toCamelCase($key);

            if (method_exists($this, $method)) {
                $this->$method($value);

            }
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
     * @return Search
     */
    public function setId(string $id): Search
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
     * @return Search
     */
    public function setTitle(string $title): Search
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return Search
     */
    public function setContent(string $content): Search
    {
        $this->content = $content;
        return $this;
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
     * @return Search
     */
    public function setSite(int $site): Search
    {
        $this->site = $site;
        return $this;
    }

    /**
     * @return string
     */
    public function getBriefingId(): ?string
    {
        return $this->briefingId;
    }

    /**
     * @param string $briefingId
     * @return Search
     */
    public function setBriefingId(?string $briefingId): Search
    {
        $this->briefingId = $briefingId;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostId(): ?string
    {
        return $this->postId;
    }

    /**
     * @param string $postId
     * @return Search
     */
    public function setPostId(?string $postId): Search
    {
        $this->postId = $postId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Search
     */
    public function setUrl(string $url): Search
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     * @return Search
     */
    public function setDate(string $date): Search
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getYear(): string
    {
        return $this->year;
    }

    /**
     * @param string $year
     * @return Search
     */
    public function setYear(string $year): Search
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @return string
     */
    public function getThumbnailLink(): string
    {
        return $this->thumbnailLink;
    }

    /**
     * @param string $thumbnailLink
     * @return Search
     */
    public function setThumbnailLink(string $thumbnailLink): Search
    {
        $this->thumbnailLink = $thumbnailLink;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategories(): string
    {
        return $this->categories;
    }

    /**
     * @param string $categories
     * @return Search
     */
    public function setCategories(string $categories): Search
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTypes(): ?string
    {
        return $this->types;
    }

    /**
     * @param string $types
     * @return Search
     */
    public function setTypes(string $types = null): Search
    {
        $this->types = $types;
        return $this;
    }

}
