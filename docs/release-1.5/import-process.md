# Research Briefings Import process

The import process fetches either the complete research briefings data for each library or the most recent ones (200 briefings) from the Research briefings API.

After the API data is mapped to the Briefing object, the following checks are performed:

* If the briefing was unpublished or withdrawn in the Research briefing App, it is removed from the search database in order to hide it from the search results page.
* If the research briefing is invalid, it is removed from the search database and excluded from the import process
    * A research briefing is invalid when it has the following missing content:
        * Abstract
        * Description
        * Documents
        
        
If the research briefings objects were successfully returned from the API, the import loops through all briefings and tries to insert each one into WordPress. If the briefing has the `published` status, it is saved/updated in the search table. If it's not published, the briefing is removed from the search index.

##  Importing data from API to Wordpress

The import process determines if we need to create a new 'Research briefing' custom post in Wordpress based on existence of the meta value `identifier` which is compared with the briefing id (i.e CBP-8946). If no custom post matches the `identifier` we create a new custom post, otherwise we update the existing one.


### WordPress field mapping

Postmeta fields:
```
    identifier    => research briefing id,
    topics        => research briefing topics,
    related_link  => research briefing related links
    documents     => research briefing documents
    section       => research briefing section
    created_date  => research briefing created timestamp,
    import_last_update => current timestamp,
```


Post fields:

```
    post_name     => research briefing id,
    post_title    => research briefing title,
    post_type     => "research-briefing",
    post_excerpt  => research briefing descripton
    post_content  => research briefing Html summary
    post_status   => if research briefing has a published status the value is "publish", otherwise it's "draft",
    post_date     => research briefing date
    post_date_gmt => research briefing date formatted to GMT
    meta_input    => postmeta fields
```

### Taxonomies

After setting the corresponding fields for the research briefing custom post, the following taxonomies are added to the newly or updated posts:
   * rb_topics (represents the research briefing Topics)
   * rb_authors (represents the research briefings Authors)
   * rb_types (represents the research briefing Types)
   
   The topics and authors taxonomies are saved by using the `wp_set_object_terms` WordPress method which replaces data by default, whenever the import is ran.
   
   The types taxonomy is used to append new category terms that come from the data import, and also to add the custom terms that users are adding in the CMS. The import does not overwrite the type terms.   
   
   Authors are attached to the post based on their term existence in WordPress, in order to avoid duplications.
   
   
#### Categories   

   The categories are mapped by matching research briefing topics which come from the API with WordPress category terms via crosstagged categories (the WP option `research_briefings_crosstagged` which is a serialized array of categories)
    
   More information about the categories and how the Ingester (often called crosstagging tool) works can be found [here](../categories.md).
   
   
### Setting Featured images

Images are assigned to the newly created research briefing posts from the category images of that corresponding post. More information about the featured images can be found [here](featured-images.md).

### Setting extra properties for Search

Extra variables are processed and set on the briefing object and on the insight object in order to display the necessary information for the search results:
   * Permalink slug
   * Thumbnail
   * Topic terms that are set as Tags 
   * Author terms that are set as AuthorTags
    
### Setting multiple types for Search

Since Insights do not contain the types taxonomy, a separate method  `Wordpress:setMultipleTypes()` was created to set up the multiple Type terms on the briefing object to include this information in the search results page.

A `save_post_research-briefing` hook was created to update the Search records whenever a Type is added manually to a Research briefing in Wordpress. The functionality for this action can be found in the file `htdocs/content/plugins/research-briefings-wp/includes/set-types-on-search.php`

##  Importing Insights

After the research briefings import process has ran, we are also importing Insights content in order to include them in the Search functionality. 

The process to import insights is as follows:
* Retrieving the current insights content from all the multisite posts table
* Saving the insight data in the search repository


NB: information about how the initial data import process worked can be found [here](../import-process.md).


   
    
