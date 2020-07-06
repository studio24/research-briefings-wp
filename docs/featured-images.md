# Featured images

When there is a new Research briefing post created as part of the import process, the featured image is set from the post's corresponding categories. If any category of the post has a termmeta with the meta key *rb_images*, the image ID of the last category is returned and attached to the given post.

The code that sets up featured images for newly created posts can be found here `htdocs/wp-content/plugins/research-briefings-wp/includes/ResearchBriefing/Wordpress/Wordpress.php`: 
```
Wordpress:setImage()
```


And the code that checks new featured image from categories and updates the images for the posts accordingly, can be found in `htdocs/wp-content/plugins/research-briefings-wp/includes/set-featured-image-from-category.php`
