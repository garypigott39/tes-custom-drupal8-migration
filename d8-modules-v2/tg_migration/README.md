## Module: tg_migration

Tes Group - migration wrapper module. 

This provides a set of **process/utility classes** which are then used in the various bits of the migration (e.g. the test CSV migration etc).

The basic idea is that the migrations define their own _source_ plugins but where possible use core, or these custom, process plugins to do the rest.

Thus, none of the sub module migrations should need their own process plugins.

This is a very different strategy to the original idea of splitting out the migrations to separate unconnected modules, they are still separate modules but they are all part of the _tg_migration_ module.


## News Article Structure ##
The structure of the news_article content type is evolving, but currently consists of the following:

| Field name | Field label |
| :--------- | ----------- | 
| nid | Article source nid |
| title | Article Title |
| field_news_author | Article Author
| field_news_body | Article Body |
| field_news_standfirst_article | Article Standfirst |
| field_news_standfirst_short | Card Standfirst - Short |
| field_news_standfirst_long | Card Standfirst - Long |
| field_news_tags | Category |
| field_news_image | Image |
| field_news_related_articles | Related content |
| field_news_headline_short | Short Headline |


## Gotchas ##
Where to start, there have been loads of them but they include:

* 1:M or M:1 mappings, e.g. tags 
* Choice of file id, e.g. hero/teaser images mapping to a single field
* Differences between the CSV and SQL approaches (e.g. fid vs uri for files)

At some point I will document the solution used to each of these...


_Gary Pigott - 07/03/2018_
