#Useful Notes

Just some aide memoire's for future migrations etc.

##Multiple migrate sources
Example is the `field_news_image/target_id`

This field is based on either a teaser or hero image upload and as such we can map to multiple potential lookups. Namely:

  - news_sql_teasers
  - news_sql_heroes
  
Now, the (out of the box) **migration_lookup** plugin provides that capability but it is important to include the *stub_id* argument so that a stub can created (if applicable).

Originally I had written the custom process plgin `tg_migration_lookup` but in hindsight that was overkill and what we actually need is just the following:

```yaml
#########################################################################################################
# Notice the use of the "stub_id" argument.
#########################################################################################################
  field_news_image/target_id:
    -
      plugin: tg_choose_value
      fieldnames:
        - teaser
        - images
      multiple: false
    -
      plugin: migration_lookup
      migration:
        - news_sql_teasers
        - news_sql_heroes
      stub_id: news_sql_teasers
```

Alternatively if we wanted to use a stub_id based on the fieldname chosen (with value) then we could use the custom plugin `tg_migration_lookup`


##Custom Process Plugins
The idea is that these are developed to be shared between migrations, not just the news SQL & CSV ones. 

See `\Drupal\tg_migration\Plugin\migrate\process` namespace.

