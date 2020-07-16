# Featured images

Featured images are setup differently for the research briefing libraries.

1. For House of Commons and House of Lords libraries:

When there is a new Research briefing post created as part of the import process, the featured image is set from the post's corresponding categories. If any category of the post has a term meta with the meta key *rb_images*, the image ID of the last category is returned and attached to the given post.

The code that sets up featured images for newly created posts can be found here `htdocs/wp-content/plugins/research-briefings-wp/includes/ResearchBriefing/Wordpress/Wordpress.php`: 
```
Wordpress:setImage()
```

2. For POST:

In the case of a new Research briefing being created as part of the import process, the first image that appears in the HTML content field (called htmlsummary in the API) of a Research briefing is extracted and then uploaded to the media library in WordPress. If the upload is successful, the image is inserted as an attachment and the corresponding metadata is generated and updated in the database. The attachment ID is linked to the given post, setting the featured image for the research briefing article.

The code that sets up featured images for the POST microsite research briefings can be found here `htdocs/wp-content/plugins/research-briefings-wp/includes/ResearchBriefing/Wordpress/Wordpress.php`: 
```
Wordpress:setPostLibraryImage()
```


You can check [here](./htdocs/content/plugins/research-briefings-wp/docs/featured-images.md) the details of the original featured images functionality.
