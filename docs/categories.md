# Categories mapping

Research Briefings topics are mapped to Wordpress topics within the crosstagging tool called *Research Briefings*. This tool is available in the WordPress admin panel.

## How the tool works

Initially, all the imported data is assigned to the category *Research Briefing*. The topics of each custom post data is then translated to the list of categories based on the topic url, which represents the identifier. 

If the topic isn't matched, it will appear as a new category under the _Unassigned Research Briefings Topics_ column. This can be dragged into its own category or an existing one. 

Parent topics created manually will also appear in the Ingester under the _Second Reading Categories_ column to allow imported terms to be assigned to them.

## Technical details

Whenever the Ingester tool is used to save the new hierachy of categories, the `research_briefings_crosstagged` option is updated with new information. This represents a serialized array that stores WordPress topic terms ID as the key and the name of the API topic terms and their corresponding URL.

The corresponding code for the categories functionality can be found in `htdocs/wp-content/plugins/research-briefings-wp/includes/ResearchBriefing/Wordpress/Wordpress.php`:

```
Wordpress::getCategoriesToAttach()
Wordpress::getCategoriesByTopicUrl()
Wordpress::getCrosstaggedCategories()
```

