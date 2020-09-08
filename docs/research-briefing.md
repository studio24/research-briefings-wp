# Research Briefings

Research Briefings represents the dataset for many objects used on the website. Below are some important notes regarding the research briefing integration.

## Running the import

You need to be in the WordPress folder when running WP CLI (`/htdocs`) and specify the environment with this command:

`export WP_ENV=localdev` where localdev is the local env


### Importing a single Research Briefing

You can import a single Research Briefing by running the following command:

`wp research-briefing import single SN06019` where `SN06019` is the Research Briefing Identifier.

If you have an issue with the REST API data being loaded locally (e.g. _cURL error 52: Empty reply from server_), please try connecting to the Studio 24 
office VPN.

### Importing the research briefings per website

```
wp research-briefing import all [library] [limit]
```

You can import all the Research Briefing for a specific library by running the following command. For reliability we also 
specify the WordPress site we are importing data into, which is recommended by WP CLI.

```
wp research-briefing import all [library] --url=[library site url]
```

Example:

```
wp research-briefing import all commons --url=https://commonslibrary-parliament.studio24.dev/
```

Valid library site names are:

* `commons` 
* `lords` 
* `post`

You can limit how many records are imported via the limit argument. Please note the Research Briefings API 
orders results from most recent published date. 

Example (import 50 most recent items):

```
wp research-briefing import all commons 50 --url=https://commonslibrary-parliament.studio24.dev/
```

### Recommended cron tasks

It is recommended to run two tasks for the data import:

#### Every 15 minutes

Run an import limited to 200 results for each library site. E.g.

```
wp research-briefing import all commons 200 --url=https://commonslibrary-parliament.studio24.dev/
```

#### Once a day, around 2am

Run a full import for each library site. E.g.

```
wp research-briefing import all commons --url=https://commonslibrary-parliament.studio24.dev/
``` 
