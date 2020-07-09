<?php


namespace ResearchBriefing;

use DateTime;
use Exception;
use ResearchBriefing\Model\SearchInterface;

class Briefing implements SearchInterface
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
    protected $description;
    /**
     * @var string
     */
    protected $abstract;
    /**
     * @var string
     */
    protected $htmlSummary;
    /**
     * @var string
     */
    protected $publisher;
    /**
     * @var string
     */
    protected $section;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var boolean
     */
    protected $published;
    /**
     * @var array
     */
    protected $topics;

    /**
     * @var string
     */
    protected $resourceId;
    /**
     * @var array
     */
    protected $authors;
    /**
     * @var array
     */
    protected $documents;
    /**
     * @var DateTime
     */
    protected $date;
    /**
     * @var DateTime
     */
    protected $created;
    /**
     * @var array
     */
    protected $relatedLink;
    /**
     * @var string
     */
    protected $oldSiteUrl;
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
     * @var string
     */
    protected $typeTags;

    /**
     * Mapping the main research briefing array fields to the object properties
     *
     * @param $data
     * @throws Exception
     */
    public function setMappedFields($data)
    {
        if (isset($data['identifier']['_value'])){
            $this->setId($data['identifier']['_value']);
        }

        if (isset($data['title'])){
            $this->setTitle($data['title']);
        }

        if (isset($data['description'][0])){
            $this->setDescription($data['description'][0]);
        }

        if (isset($data['abstract']['_value'])){
            $this->setAbstract($data['identifier']['_value']);
        }

        if (isset($data['publisher']['prefLabel']['_value'])){
            $this->setPublisher($data['publisher']['prefLabel']['_value']);
        }

        if (isset($data['section'][0]['prefLabel']['_value'])){
            $this->setSection($data['section'][0]['prefLabel']['_value']);
        }

        if (isset($data['subType']['prefLabel']['_value'])){
            $this->setType($data['subType']['prefLabel']['_value']);
        }

        if (isset($data['topic'])){
            $this->setTopics($data['topic']);
        }

        if (isset($data['date']['_value'])){
            $this->setDate($data['date']['_value']);
        }

        if (isset($data['_about'])){

            // Grab the internal resource id
            $resourceId = $this->extractOldResourceId($data['_about']);

            $this->setResourceId($resourceId);
        }

    }

    /**
     * Whether a research briefing item is valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (
            empty($this->getAbstract()) &&
            empty($this->getDescription()) &&
            empty($this->getDocuments())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Mapping the completed briefing array fields to the object properties
     *
     * @param $data
     * @throws Exception
     */
    public function setCompleteBriefingFields($data) {
        if (isset($data['result']['primaryTopic']['htmlsummary'])){
            // $filteredHtml = filter_var($data['result']['primaryTopic']['htmlsummary'], FILTER_SANITIZE_SPECIAL_CHARS);
            $this->setHtmlSummary(trim(html_entity_decode($data['result']['primaryTopic']['htmlsummary'])));
        }

        if (isset($data['result']['primaryTopic']['published']['_value'])){
            $this->setPublished(filter_var($data['result']['primaryTopic']['published']['_value'], FILTER_VALIDATE_BOOLEAN));
        }
        if (isset($data['result']['primaryTopic']['created']['_value'])){
            $this->setCreated($data['result']['primaryTopic']['created']['_value']);
        }

        if (isset($data['result']['primaryTopic']['relatedLink'])){

            $relatedLink = $this->buildRelatedLinkArray($data['result']['primaryTopic']['relatedLink']);
            $this->setRelatedLink($relatedLink);
        }

        if (isset($data['result']['primaryTopic']['externalLocation'])){
            $this->setOldSiteUrl($data['result']['primaryTopic']['externalLocation']);
        }

//        if (isset($data['result']['primaryTopic']['creator']) || isset($data['result']['primaryTopic']['researchContributor'])) {
            $authors = $this->buildAuthorsArray($data['result']['primaryTopic']);
            $this->setAuthors($authors);
//        }

        $documents = $this->buildDocumentsArray($data['result']['primaryTopic']);
        $this->setDocuments($documents);
    }

    /**
     * Method that takes input of an old resource URL in the format of
     * http://data.parliament.uk/resources/339941
     * and extracts the ID needed to get complete individual briefing data
     *
     * @param $resourceUrl
     * @return mixed
     */
    public function extractOldResourceId(string $resourceUrl) {
        $id = explode("/", $resourceUrl)[4];

        return $id;
    }

    /**
     * Processing the relatedLink data and returning the label and url
     *
     * @param array $relatedLink
     * @return array
     */
    public function buildRelatedLinkArray(array $relatedLink) {
        $link = [];
        if (is_array($relatedLink) ) {
            foreach ($relatedLink as $linkArray) {
                $link[] = [
                    'label' => $linkArray['label']['_value'],
                    'url' => $linkArray['website']
                ];
            }
        }

        return $link;
    }

    /**
     * Processing the customized authors data
     *
     * @param $briefing
     * @return array
     */
    public function buildAuthorsArray(array $briefing)
    {
        $creatorData = [];
        $contributorData = [];

        if (isset($briefing['creator'])) {
            if (is_array($briefing['creator']) && isset($briefing['creator'][0])) {
                foreach ($briefing['creator'] as $index => $creator) {

                    if (isset($creator['givenName']['_value'])) {
                        $givenName = $creator['givenName']['_value'];
                    } else {
                        $givenName = '';
                    }

                    if (isset($creator['familyName']['_value'])) {

                        $familyName = $creator['familyName']['_value'];
                    } else {
                        $familyName = '';
                    }

                    if (isset($creator['fullName']['_value'])) {

                        $fullName = $creator['fullName']['_value'];
                    } else {
                        $fullName = '';
                    }

                    $creatorData[] = $fullName ? $fullName : (($givenName || $familyName) ?  $givenName . " " . $familyName : '');
                }
            } else {

                $fullName = isset($briefing['creator']['fullName']) ? ($briefing['creator']['fullName']['_value'] ? $briefing['creator']['fullName']['_value'] : '') : '';
                $givenName = $briefing['creator']['givenName']['_value'] ? $briefing['creator']['givenName']['_value'] : '';
                $familyName = $briefing['creator']['familyName']['_value'] ? $briefing['creator']['familyName']['_value'] : '';

                $creatorData[] = $fullName ? $fullName : (($givenName || $familyName) ?  $givenName . " " . $familyName : '');
            }

        }

        if (isset($briefing['researchContributor'])) {

            foreach($briefing['researchContributor'] as $index => $researchContributor) {

                if (isset($researchContributor['givenName']['_value'])){
                    $givenName = $researchContributor['givenName']['_value'];
                } else {
                    $givenName = '';
                }

                if (isset($researchContributor['familyName']['_value'])){
                    $familyName = $researchContributor['familyName']['_value'];
                } else {
                    $familyName = '';
                }

                if (isset($researchContributor['fullName']['_value'])) {

                    $fullName = $researchContributor['fullName']['_value'];
                } else {
                    $fullName = '';
                }

                $contributorData[] = $fullName ? $fullName : (($givenName || $familyName) ?  $givenName . " " . $familyName : '');
            }
        }

        $authors = array_filter(array_merge($creatorData, $contributorData));
//        $authors = [
//            'Creators' => $creatorData ? $creatorData : '',
//            'Contributors' => $contributorData ? $contributorData : ''
//        ];

        return $authors;
    }

    /**
     * Processing the customized documents data
     *
     * @param $briefing
     * @return array
     */
    public function buildDocumentsArray(array $briefing) {

        $briefingDocuments = [];
        $attachments = [];

        if (isset($briefing['briefingDocument'])) {
            $briefingDocuments[]= [
                'documentTitle'  => $briefing['briefingDocument']['attachmentTitle'],
                'documentUrl' => $briefing['briefingDocument']['fileUrl'],
                'documentFileType'  => $briefing['briefingDocument']['mediaType'],
                'documentFileSize' => $briefing['briefingDocument']['sizeOfFile'][0],
                'type'             => 'briefingDocument',

            ];
        }

        if (isset($briefing['attachment'])) {

            if (is_array($briefing['attachment']) && isset($briefing['attachment'][0])) {

                foreach ($briefing['attachment'] as $index => $attachment) {
                    $attachments[] = [
                        'documentTitle'    => $attachment['attachmentTitle'],
                        'documentUrl'      => $attachment['fileUrl'],
                        'documentFileType' => $attachment['mediaType'],
                        'documentFileSize' => $attachment['sizeOfFile'][0],
                        'type'             => 'attachment',
                    ];
                }
            } else {
                $attachments[] = [
                    'documentTitle'    => $briefing['attachment']['attachmentTitle'],
                    'documentUrl'      => $briefing['attachment']['fileUrl'],
                    'documentFileType' => $briefing['attachment']['mediaType'],
                    'documentFileSize' => $briefing['attachment']['sizeOfFile'][0],
                    'type'             => 'attachment',

                ];
            }
        }

        $documents = array_merge($briefingDocuments, $attachments);

        return $documents;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set Research Briefing ID, e.g. CDP-2020-0004
     *
     * This also sets the permalink (slug) to the RB ID
     *
     * @param string $id
     * @return Briefing
     */
    public function setId(string $id): Briefing
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
     * @return Briefing
     */
    public function setTitle(string $title): Briefing
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Briefing
     */
    public function setDescription(string $description): Briefing
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getAbstract(): ?string
    {
        return $this->abstract;
    }

    /**
     * @param string $abstract
     * @return Briefing
     */
    public function setAbstract(string $abstract): Briefing
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlSummary(): ?string
    {
        return $this->htmlSummary;
    }

    /**
     * @param string $htmlSummary
     * @return Briefing
     */
    public function setHtmlSummary(string $htmlSummary): Briefing
    {
        $this->htmlSummary = $htmlSummary;
        return $this;
    }

    /**
     * @return string
     */
    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    /**
     * @param string $publisher
     * @return Briefing
     */
    public function setPublisher(string $publisher): Briefing
    {
        $this->publisher = $publisher;
        return $this;
    }

    /**
     * @return string
     */
    public function getSection(): ?string
    {
        return $this->section;
    }

    /**
     * @param string $section
     * @return Briefing
     */
    public function setSection(string $section): Briefing
    {
        $this->section = $section;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the briefing type
     *
     * This translates the incoming type from the Research Briefing API and
     * the ones that have been manually added in the CMS into the types
     * we want to display on the website.
     *
     *
     * @param string $type
     * @return Briefing
     */
    public function setType(string $type): Briefing
    {
        switch ($this->getSite()) {
            case 1:
                // Commons
                switch ($type) {
                    case 'Commons Debate packs':
                        $this->type = 'Commons Debate Pack';
                        break;
                    case 'Commons Briefing papers':
                    default:
                        $this->type = 'Commons Research Briefing';
                        break;
                }
                break;
            case 2:
                // Lords
                $this->type = 'Lords Research Briefing';
                break;
            case 3:
                // POST
                switch ($type) {
                    case 'POSTnotes':
                        $this->type = 'POSTnote';
                        break;
                    case 'POSTbriefs':
                    default:
                    $this->type = 'POSTbrief';
                        break;
                }
                break;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * @param bool $published
     * @return Briefing
     */
    public function setPublished(bool $published): Briefing
    {
        $this->published = $published;
        return $this;
    }

    /**
     * @return array
     */
    public function getTopics(): ?array
    {
        return $this->topics;
    }

    /**
     * @param array $topics
     * @return Briefing
     */
    public function setTopics(array $topics): Briefing
    {
        $this->topics = $topics;
        return $this;
    }

    /**
     * @return string
     */
    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    /**
     * @param string $resourceId
     * @return Briefing
     */
    public function setResourceId(string $resourceId): Briefing
    {
        $this->resourceId = $resourceId;
        return $this;
    }

    /**
     * @return array
     */
    public function getAuthors(): ?array
    {
        return $this->authors;
    }

    /**
     * @param array $authors
     * @return Briefing
     */
    public function setAuthors(array $authors): Briefing
    {
        $this->authors = $authors;
        return $this;
    }

    /**
     * @return array
     */
    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    /**
     * @param array $documents
     * @return Briefing
     */
    public function setDocuments(array $documents): Briefing
    {
        $this->documents = $documents;
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
     * @return Briefing
     * @throws Exception
     */
    public function setDate($date): Briefing
    {
        if (!$date instanceof DateTime) {

            $date = new DateTime($date);
        }

        $this->date = $date;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     * @return Briefing
     * @throws Exception
     */
    public function setCreated($created): Briefing
    {

        if (!$created instanceof DateTime)
        {
            $created = new DateTime($created);
        }

        $this->created = $created;
        return $this;
    }

    /**
     * @return array
     */
    public function getRelatedLink(): ?array
    {
        return $this->relatedLink;
    }

    /**
     * @param array $relatedLink
     * @return Briefing
     */
    public function setRelatedLink(array $relatedLink): Briefing
    {
        $this->relatedLink = $relatedLink;
        return $this;
    }

    /**
     * @return string
     */
    public function getOldSiteUrl(): ?string
    {
        return $this->oldSiteUrl;
    }

    /**
     * @param string $oldSiteUrl
     * @return Briefing
     */
    public function setOldSiteUrl(string $oldSiteUrl): Briefing
    {
        $this->oldSiteUrl = $oldSiteUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getPermalinkSlug(): string
    {
        return $this->permalinkSlug;
    }

    /**
     * @param string $permalinkSlug
     * @return Briefing
     */
    public function setPermalinkSlug(string $permalinkSlug): Briefing
    {
        $this->permalinkSlug = $permalinkSlug;
        return $this;
    }

    /**
     * @return string
     */
    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     * @return Briefing
     */
    public function setThumbnail(string $thumbnail): Briefing
    {
        $this->thumbnail = $thumbnail;
        return $this;
    }

    /**
     * @return string
     */
    public function getTags(): string
    {
        return $this->tags;
    }

    /**
     * @param string $tags
     * @return Briefing
     */
    public function setTags(string $tags): Briefing
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Get the multiple types (from the RB import and WP) for the search table
     *
     * @return string
     */
    public function getTypeTags(): string
    {
        return $this->typeTags;
    }

    /**
     * @param string $typeTags
     * @return Briefing
     */
    public function setTypeTags(string $typeTags): Briefing
    {
        $this->typeTags = $typeTags;
        return $this;
    }


    /**
     * Get the briefing id for the search table
     * @return string
     */
    public function getBriefingId()
    {
        return $this->getId();
    }

    /**
     * Method that builds the customized content field for the search table
     * @return string
     */
    public function getSearchContent()
    {
        return $this->getId() ."|". $this->getDescription() ."|". strip_tags($this->getHtmlSummary());
    }

    /**
     * Determine what site we are on based on the type of the briefing
     *
     * @return int
     */
    public function getSite(): int
    {

        if (strpos($this->getPublisher(), 'Commons') !== false) {
            return 1;
        } elseif (strpos($this->getPublisher(), 'Lords') !== false) {
            return 2;
        } elseif (strpos($this->getPublisher(), 'POST') !== false) {
            return 3;
        }
    }
}
