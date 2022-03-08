# How search works

MySQL performs a case-insensitive search across the title and content fields of research briefings and insight articles. 
Results are ranked based on the frequency of the search keywords in title or content. So if the keyword is "NHS" and 
this word appears 10 times in an article, that is ranked higher than another article where is appears 5 times.

If multiple search keywords are entered an exact match is used. This results in more accurate search results. 

The user can choose optional filters by year and site. The search defaults to the current site and all years.

## What search cannot do

We cannot currently:
* rank results higher if they are more recent
* rank results higher if text appears in the title field
* support boolean operators
* support stemming or synonyms
* support user defined boosting on particularly important documents

We plan to review search in Phase 2, possibly looking at using Elasticsearch.

## Technical details

Technically this uses the [MySQL Full Text search](https://dev.mysql.com/doc/refman/5.7/en/fulltext-natural-language.html) in Natural Language mode.

Search code can be found in `plugins/research-briefings-wp/includes/ResearchBriefing/Repository/SearchRepository.php`:

* SearchRepository::performSearch()
* SearchRepository::countSearchResults()

Keyword search terms are filtered & trimmed before a search is performed.
