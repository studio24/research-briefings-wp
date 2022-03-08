<?php


namespace ResearchBriefing\Model;


interface SearchInterface
{

    public function setPermalinkSlug(string $permalinkSlug);

    public function setThumbnail(string $thumbnail);

    public function setTags(string $tags);

    public function setAuthorTags(string $authors);

}
