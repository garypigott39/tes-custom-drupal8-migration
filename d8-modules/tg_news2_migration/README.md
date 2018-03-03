# News Live Migration


The live news migration is via SQL connection to the source (News) database.

The migrations are grouped under the **news2** group - this is simply because it emulates the name of the module and we already have a module called tg_news_migration (which did the test CSV based migration).


## Module Dependencies
The news migration is dependent on the following modules:

- migrate (Core)
- migrate_plus (Contrib)
- migrate_tools (Contrib)

The source **SQLBase** migration type is already part of the Core migrate source plugins so no other migration source modules are required.

If we wanted to pull from a non-sql data source then there are various contrib modules available to enable this, e.g. Json, CSV etc.
 
 For an example CSV migration see the _tg_news_migration_ module which imported test data from various csv files, and as such needed to use the **migrate_source_csv** contrib module.

The **migrate_plus** module gives us some additional source and process plugins as well as a couple of examples of migration modules (beer migration and a more advanced wine migration example). 

The **migrate_tools** module gives us all various drush utilities that are useful bits of the migration process, including:

* migrate-status (ms)
* migrate-import (mim)
* migrate-rollback (mr)
* migrate-stop (mst)
* migrate-reset-status (mrs)
* migrate-message (mmsg)
 
 This module also provides the migration UI, which I've been using for managing the group migration process - although we could do the whole process via drush if required (and this will be what we do with the live migration).


## Gotchas - config left after uninstall
When working on the a new migration, uninstalling and resinstalling the module gave an error like 

> Configuration objects... already exist in active configuration

Now, the reason for this is that the configuration is not removed when the module is uninstalled, but re-installing it will then attempt to recreate said config and hence the error. 

So ideally what we want is to remove associated configuration when the module is uninstalled and the easiest way to do this is to make each config dependent on the named module, so in each of the config files you will see some **module dependencies**.

```yaml
dependencies:
  enforced:
    module:
      - tg_news2_migration
```

Alternatively you could add a **hook_uninstall** to your `module.install` file and then clear the migrations that way, e.g.

```php
function my_module_uninstall() {
  db_query('DELETE FROM {config} WHERE name LIKE 'migrate_plus.migration.news2%');
  db_query('DELETE FROM {config} WHERE name LIKE 'migrate_plus.migration_group.news2%');
  drupal_flush_all_caches();
}
```

But to me it's just easier (and safer) to add that dependency.
 

## Gotchas - Invalid source db
So, although I have included requirements checking to try to resolve connecting to the source db (some tables exist and are not empty). 

If however the migration source points to a non-existent database then the inbuilt migration error trapping will actually result in a redirect to the target uri `domain/core/install.php`.

Dealing with this in a better way is on my @todo list.


## Gotchas - Loads of migration tables
So enabling the migration module will create a number of **migration map** tables corresponding to the each of the migration ids. There will also be migration message tables etc. 

Various core (& contrib) modules may also create their own migrate map and message tables, like _d7_node_, _d7_file_ etc. 

For example have a look at the core block module and you will see (in `block/migration_templates`) d6_block and d7_block migration config files. 


## Gotchas - unexpected warnings
You may also see error messages when running `drush ms` e.g.

> [error]  Could not retrieve source count from d6_comment: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'tes_cms.comments' doesn't exist: SELECT COUNT(*) AS expression
FROM 
(SELECT 1 AS expression
FROM 
{comments} c
INNER JOIN {node} n ON c.nid = n.nid) subquery; Array
(
)

It's just that the corresponding source database D6 table does not exist in our migrate database "tes_cms" (see Database connection above). _The table was replaced in D7 by the comment table (and our source database is Drupal 7)_. 

**Fix for excessive migration tables & warning messages** 
found the reason for this and it is due to the name of the database connection we were using. 

Basically "migrate" is the default connection name for migrations and as such enabling the migrate module then used this connection to build a set of migrations for all the taxonomies and entities on the source migration database.

Changing the connection name to "news_migrate" means no more D6/D7 migration tables, as the module cant now find the source database from which to create these (entity) migrations.

     
## Gotchas - stub entry creation
So, I was getting some SQL Integrity errors ("title cannot be NULL") in my message table for the news articles part of the migration. 

I was tearing my hair out trying to work out what the issue was - particularly as all the source nodes I was expecting were being created.

Eventually I figured out that it was the stub entry creation that was giving me error. The problem lay in the process config that I was using for the title field:

```yaml
process:
  title:
    -
      plugin: skip_on_empty
      source: nid
      method: row

    -
      plugin: callback
      callable: ucwords
      source: title
```

Simplifying it to just map `title: title` resolved the issue, as it turned out I didnt need the other bits anyway (config inherited from the test csv version).

Now, the creation of stub entries may cause the **imported total to exceed** the calculated total and may also necessitate multiple runs of the migration, via the UI, there are no more source rows to process.


## Database Connection
The source database connection is defined in the settings.php (or local settings) file as an additional database connection array element, for example

```php
// dont use "migrate" as this is the default migration connection name
// and results in automatic creation of D6/D7 migrations and resultant
// mapping tables etc
//
//$databases['migrate']['default'] = array (
//
$databases['news_migrate']['default'] = array (
  'database' => 'tes_cms',
  'username' => 'user',
  'password' => 'xxxxxxxxx',
  'prefix' => '',
  'host' => 'hostname'
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
```

This is then specified in the config YML files as the source of the migration, for example in the migrate group file `migrate_plus.migration_group.news2.yml` the connection is defined in shared configuration as the **source** field, e.g.

```yaml
shared_configuration:
   source:
     key: news_migrate  # or whatever is the named element in settings (see above)
```

  
## Other Shared Configuration
There are various other possible shared config entries (defined at the group level). These config entries are used by various of the associated migrations (more later).

* **selection**: this is used to define an optional node range (_from/to_ node ids) and whether we only want _live_ (published) nodes, e.g.

```yaml
  selection:
    from: 375000  # 0 is the default
    to: 99999999  # the default
    live: true
```
    
_To select all nodes simply omit the from/to entries._  

* **tags_xref**: where possible some news taxonomy terms may already have been loaded in assorted vocabularies. This is where the vocabularies to search for matching "taxonomy names" is defined. _It is important to also include the destination taxonomy as well in this list_.

```yaml
  tags_xref:
    - regions
    - phases
    - subjects
    - tags

  # this means that each of these vocabularies will be searched for matching term names.
```

* **folders**: this is used by the file download functionality (including _inline files_) and here you define the _source_domain_, _source_public_folder_uri_, _target_folder_, _public_folder_ and whether the source uri is _urlencoded_ (and therefore needs decoding), e.g.

```yaml
  folders:
    source_domain: 'https://tes.com'
    source_public_folder_uri: '/sites/default/files'  # the default
    target_folder: 'public://'
    public_folder_uri: '/sites/default/files'  # the default
    urldecode: true
```

_These details can be overridden on a per config basis_ 

So for example the author images migration (`migrate_plus.migration.news2_author_images.yml`) has a target folder of public://author-images so in its yml file it has:

```yaml
  folders:
    target_folder: 'public://author-images'
```
    
* **required**: there is a dependency on some taxonomy vocabularies being preloaded before the migration takes place. This is where these vocabulary ids are defined. If these vocabularies are not preloaded then the migration will not occur (see source plugins _checkRequirements_ method). 


## Shared Constants
There are also some shared constants for use in the individual migration YML files, these include

```yaml
  constants:
    admin_user: 1
    format_tes: tg_html  # body content default text format
    permanent_file_status: true  # saved file entities are permanent
```

The default laguage code for the migration is defined as _language_none_ **und**


## What no Process Plugins?
_Should probably be re-titled as "What only a few Process Plugins" as we are now using some process plugins (see below)._

As we have source plugins for each of the migration data types, and we can manipulate the data in the **query** (SELECT) and **prepare_row** parts of said plugins then any manipulation that would normally be required of a process plugin can in fact be done there. _So only minimal process transformation is done._

As a result, none of the custom process plugins that we used in the test CSV migration are required here.


## What no Stub?
Or, _"to stub or Not to stub"_ that is the question...

You will notice when looking at the **process** parts of the config YML files that for _entity fields_ which are migration based (i.e. using results from other parts of the migration via _migration_lookup_) that the **no_stub** config argument is set to true, for example the authors migration `migrate_plus.migration.news2_authors.yml` file.

```yaml
  field_author_image/target_id:
    plugin: migration_lookup
    migration: news2_author_images_migration
    source: photo_fid
    no_stub: true  # if not found then dont create "stub"
```

What this means is that if the migration fails to find a corresponding entry in the named migration map table (in the above that is migration_map_news2_author_images_migration) then a "stub" entry will **not** be created for that entity.


## Default is "no_stub: false"
Note that **no_stub: false** is the default config setting for the _migration_lookup_ plugin. 

So, in the case of the above if it was set to false (or not specified) then that would mean that when no mapping found a dummy file entity would be created for source file entity with details gleaned from the source migration.

Ultimately these stub entries would be replaced with real data when the source migration is run and then loads an entity for said stub. 

However, as we have (supposedly) run all the dependent migrations first then all dependent entities should already exist so we dont need to create stubs.

The exception to the above is the **related content** on the news article, as this field is based on itself (so points to itself), see `migrate_plus.migration.news2_articles_migration.yml` file.

```yaml
  field_news_related_content:
    -
      plugin: skip_on_empty
      method: process
      source: related

    -
      plugin: explode
      delimiter: ', '

    -
      plugin: migration_lookup
      migration: news2_articles_migration
      # no_stub: defaults to false, as its based on the current migration we do want to create a stub for re-population
```

Now, if we were also migrating users (which we aren't doing currently) then the **uid** field which is currently just set to be the (constant) _admin user_ could also be a stub field. So that once all the users were populated then the stubs would be filled with the appropriate user details.


## Invalid file entities
So, as mentioned above, we don't create a _"stub file entity"_ for any files that we haven't been able to download and save as file entities on the target system.

Now whilst this is for us the correct behaviour, what it does mean is that we have no easy way of finding missing entities. 

Perhaps it might be better to create stub entries with a specific name so that they can be easily detected and dealt with post migration.


## Migration dependency
Only the **authors** and **news articles** migrations have a dependency on other parts of the migration process. The remaining migrations can occur in any order although the following order is recommended:

1. news2_tags_migration
2. news2_author_images_migration
3. news2_authors_migration
4. news2_attachments_migration 
5. news2_teaser_images_migration
6. news2_articles_migration

_Note, that the teaser images & news articles migrations use the node range selection._ 

The authors also uses the node range selection, but for that configuration it has been **overridden**. The tag and file migrations do not use node id selection.
 
Running `drush ms --group=news2` will show the migrations and a run ordering based on migration config dependencies.

```
cmd: drush ms --group=news2
--------------------------- ------------------------------- -------- ------- ---------- ------------- --------------- 
  Group                       Migration ID                    Status   Total   Imported   Unprocessed   Last Imported  
 --------------------------- ------------------------------- -------- ------- ---------- ------------- --------------- 
  News Full Imports (news2)   news2_author_images_migration   Idle     18      0          18                           
  News Full Imports (news2)   news2_tags_migration            Idle     284     0          284                          
  News Full Imports (news2)   news2_authors_migration         Idle     16735   0          16735                        
  News Full Imports (news2)   news2_teaser_images_migration   Idle     124     0          124                          
  News Full Imports (news2)   news2_attachments_migration     Idle     107     0          107                          
  News Full Imports (news2)   news2_articles_migration        Idle     124     0          124                          
 --------------------------- ------------------------------- -------- ------- ---------- ------------- --------------- 
```

If you don't see all the migrations you expect it could be due to the migration failing it's plugin requirements (see _checkRequirements_ method in plugin).

_Note, that this same ordering is not displayed in the UI which is displayed in migration name order._
 
 
 ## Running via drush
 To run the migrations in their entirety you can use `drush mim --group=news2` which will also apply the dependency rules defined in the migration config.
 
 Running the full migration live is probably not the way to go, instead it may be better to run the smaller steps individually first and then run the large migration steps in phases until there are no further items to migrate.
 
 * news2_author_images_migration
 * news2_tags_migration
 * news2_authors_migration
 * news2_attachments_migration (as there aren't many of these)
 
 Now, the following are the biggies:
 
 * news2-teaser_images migration
 * news2_articles_migration
 
 So, it would be worth runnning these with the `-limit=1000` to perhaps run the import for 1000 items at a time.
 
## What another gotcha
So running the migration I got 3 failures (file downloads)

```
cmd: drush mim --group=news2
 [notice] Processed 18 items (18 created, 0 updated, 0 failed, 0 ignored) - done with 'news2_author_images_migration'
 [notice] Processed 0 items (0 created, 0 updated, 0 failed, 0 ignored) - done with 'news2_tags_migration'
 [notice] Processed 16734 items (16734 created, 0 updated, 0 failed, 0 ignored) - done with 'news2_authors_migration'
 [notice] Processed 124 items (124 created, 0 updated, 0 failed, 0 ignored) - done with 'news2_teaser_images_migration'
 [notice] Processed 107 items (104 created, 0 updated, 3 failed, 0 ignored) - done with 'news2_attachments_migration'
 [error]  news2_attachments_migration Migration - 3 failed. 
```

The file download for the named attachment files had timed out, e.g.

```
FID     SECURITY LEVEL  MESSAGE 
87142	1	            cURL error 28: Operation timed out after 29998 milliseconds with 57946981 out of 64995621 bytes received (see http://curl.haxx.se/libcurl/c/libcurl-errors.html) (https://tes.com/sites/default/files/news_article_files/newteacherssupp.pdf)
```

So, the solution to this may be to increase the **timeout** option in the file download.

Now this can be done in a custom option (say in our own version of the download process plugin) or as a general catch all in the settings file, e.g. `$settings['http_client_config']['timeout'] = 60;`

The failure of that part of the migration also meant that the subsequent news articles migration wasn't run.


## Dealing with migration errors
The migration process records errors in a couple of places:

* migrate_message_[id] table
So, looking at this you will see the actual cause of the error, e.g. 

```
  msgid: 1
  source_ids_hash: 6de7e724dab38c14560a1ab7e3d64573654d0791c2db6836d152a558a66df2c1
  level: 1
  message: cURL error 28: Operation timed out after 29998 milliseconds with 57946981 out of 64995621 bytes received (see http://curl.haxx.se/libcurl/c/libcurl-errors.html) (https://tes.com/sites/default/files/news_article_files/newteacherssupp.pdf)
```

* migrate_map_[id] table
And the mapping of the migration to a target id, where the migration has failed the destination id field will probably be blank and there will be a non-zero row status value, e.g. 

```
source_ids_hash: 6de7e724dab38c14560a1ab7e3d64573654d0791c2db6836d152a558a66df2c1
sourceid1: 87142
destid1: null 	
source_row_status: 3
rollback_action: 0
last_imported: 0
hash: null
```

Now, the above examples are from the news2_attachments_migration so in theory rather than re-running all of the migration as an update we should be able to just run those failed IDs again. Like

```
drush mim news2_attachments_migration --idlist="87142,87421,87394"
# possibly with --update and --force flags 
```

When I tried this it didn't actually work and I had to **delete** the invalid entries from the migration map table and then use the `--idlist`.

```sql
DELETE FROM migrate_map_news2_attachments_migration WHERE destid1 IS NULL AND source_row_status != 0; 
```

## Source Plugin: d7_news_tags
This plugin is responsible for the New Tags (taxonomy term) migration. The source taxonomy vocabulary names are _"News Tags"_, _"News Categories"_ and _"News Sections"_. The target vocabulary (as defined in the YML file) for this is the **tags** vocabulary.

Before migration the term name is checked against the named **xref** vocabularies and if it exists then will not be migrated

_@todo: remove the unwanted News Article Category taxonomy from the D8 site and amend the News Article content type to use the other vocabularies - speak to @Scott about this_


## Source Plugin: d7_authors
This is the authors migration plugin, and is **dependent** on the author image migratiomn having been completed first.


## Source Plugin: d7_news_files
This is a generic plugin that allows managed files to be imported onto the new database - the source file is downloaded to a **target_folder** and a **file entity** created for the file so that it can be linked to any other loaded entity (e.g. Author, News Article etc).

The selection criteria is defined in _source_sql_ selection config, e.g.

```yaml
  # this is from the Author images migration
  source_sql:
    table: field_data_field_byline_photo
    field: field_byline_photo_fid
    live: true
```

See also the **folders** config (which is also mentioned in the shared config section).


## Source Plugin: d7_news_teasers
Like the news files plugin above, this one also downloads files (from the source system) and creates file entites for them. However, it is very specific and only targets News Article teaser (or hero) images - with only a **single** image per node.

The selection criteria used here should be the same node selection range that is used in News Articles plugin (and this is set in the **group shared config**, see above).


## Source Plugin: d7_news_articles
This is the main part of the migration, namely the population of the News Article nodes. This builds on (and is dependent on) the News Attachments, News Tags, and News Teasers migrations so should occur after these - so would normally be the last part of the migration.

As part of the news article migration, inline (body content) embedded files (images and some links, e.g. PDFs) will be downloaded from the source system.

Embedded **media images** are converted to the corresponding image tag and also downloaded - they are saved as _unmanaged_ files.


## Process Plugins
So, I know I said "what no plugins" - what I meant was no (or rather very few) custom process plugins.

We are actually using quite a few Core process plugins, they are after all there to make our life easier, and its always better to use _"out of the box" functionality wherever possible.

|Core or Custom|Name|Notes|
| --- | --- | :--- |
| Core | callback | We used this one in the authors node title (just to run a _ucwords_ against the supplied Author Title field) |
| Core | download | File download plugin - creates the target uri to our files (guzzle get) |
| Core | default_value | Supply a default value for a field process, e.g. content type field in News or Authors |
| Core | skip_on_empty | Skip process (or skip row) in a field process |
| Core | explode | Turn a delimited string into an array, thereby allowing a 1:M field explosion (e.g. news tags) |
| Core | migration_lookup | Standard mechanism of mapping entity ids (xref for entities created in other migrations or maybe even in this migration) |
| Custom | d7_news_file_download | A custom file download (extends core Download) allowing for revised guzzle options. |
| Customm | d7_stub_name | Allows for a nicer format stub name. |

In the test CSV migration we did much more of our field manipulation in the process functionality (unlike this migration where we do most of this in the source _prepareRow_) and so we used lots more, including the following **custom** plugins:

* choose_value
* nice_file_name
* source_path_name
* trim_chars
* custom_migration_lookup
* news_file_download

If you want to see any of these have a look at the **tg_news_migration** module. 

You will also see a mickey mouse destination plugin, _do_nothing_, which was originally used in a migration designed to pull in unmanaged files. The approach for this ultimately changed and this plugin wasnt actually used although you can see the original config for it in `migrate_plus.migration.news_unmanaged_files_migration.yml`. 

_I should also point out that some of these were actually written because at the time I may not have known another way to do it._


## Useful links
In no particular order:
* https://github.com/jigarius/drupal-migration-example
* https://agencychief.com/blog/drupal-8-csv-migration
* https://github.com/wunderio/migrate_source_example
* https://www.drupal.org/project/migrate_plus
* https://evolvingweb.ca/blog/drupal-8-migration-migrating-basic-data-part-1
* https://deninet.com/blog/2017/06/07/building-custom-migration-drupal-8-part-3-users-and-roles
* https://www.colorado.edu/webcentral/2017/04/04/writing-node-migrations-drupal-7-drupal-8
* https://www.sitepoint.com/your-first-drupal-8-migration/
* https://www.metaltoad.com/blog/drupal-8-migrations-part-3-migrating-taxonomies-drupal-7
* https://www.metaltoad.com/blog/drupal-8-migrations-part-4-migrating-nodes-drupal-7
* https://www.drupaleasy.com/blogs/ultimike/2016/04/drupal-6-drupal-81x-custom-content-migration
* https://www.drupal.org/project/migrate/issues/2728233

Code techniques:

* [Parse embedded media elements](https://blog.kalamuna.com/news/converting-drupal-7-media-tags-during-a-drupal-8-migration)
* [Parse links and images from content source](https://drupal.stackexchange.com/questions/24736/how-to-parse-out-links-and-img-src-references-from-body-copy) 
* [Get taxonomy name](http://purencool.com/accessing-taxonomys-name-and-parent-tid-in-drupal-8)


## @todo
So some stuff to do &/or discuss with @Scott
1. Workflow state - done nothing with the source workflow state yet
2. Invalid files - if we cant download a file we forget it, do we need another strategy?
3. Remove "_migration" from migration IDs, at least I will do on te next migration I write.
4. Deal with non-existent source database (just for personal interest).


Enjoy!
