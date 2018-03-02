# News Migration Data

This data folder is used for a 1-off import of News articles and related content which is part of the Tes News V2 site. 

The rationale for this type of data migration is that it can be controlled for the purposes of testing - with just a few items selected for testing/theming purposes (applies specifically to the news articles). 

For "go live" a SQL connection version wouldd be done to pull in all the news articles.

### News Article Taxonomy

The data file `tags.csv` is built by a CSV export from the source system (using views csv export) containing all the vocabulary terms that News currently uses. 

The source tags come from multiple vocabularies but are being combined into a single News Article Category vocabulary on the target system.

The expected format of the csv file is: 

* tid
* vocabulary name
* taxonomy name

The **Key** field is term id, the vocabulary name (source vocabulary) is prefixed to the taxonomy name (term title) when not the default (News tags) vocabulary. But for more details of this have a look at the *prepareRow* handler in `src/Plugin/migrate/cource/NewsTagsCSV.php`

For source view see D7 site view name `tes_v2_news_taxonomy_export`

### News Authors
 
The data file `authors.csv` is built in a similar way to the above, this now contains **just the "test data"** details from the source Byline content type. On analysis of the source data the only fields of any interest were title, body, and photo. The other fields had no (or very little) content.

The expected format of the csv file is:

* nid
* title (author name)
* body 
* photo (just used for a lookup)

The **Key** field is node id.

For source view see D7 site view name `tes_v2_news_export`

### News Authors Images

The data file `author_images.csv` contains a path (minus domain) to the source file for **all** author images. This is also included in the authors file but that only provides a lookup to link created file entities to the source filename.

The expected format of this file is:

* nid
* photo

### News Artices files

The data file `files.csv` contains a path (minus domain) to the source file for news article files. This is also included in the articles file but that only provides a lookup to link created file entities to the source filename.

The expected format of this file is:

* nid
* type (where the file came from, e.g. "teaser image")
* path

### News Articles

The data file `articles.csv` contains basic news article node data. The fields being extracted conform to the data structure for the target News Article content type.

Before this extract is run it is **assumed** that the corresponding authors, taxonomy, and files migrations will have been completed, as those migrations provide lookup links to related entities (author, taxonomy, file entity).  

The expected format of this file may change as the target content type evolves. Currently that is:

* nid 
* title 
* body
* images (file entity lookup)
* teaser_image (file entity lookup)
* files (file entity lookup)
* category (taxonomy lookup)
* section (taxonomy lookup)
* tags (taxonomy lookup)

Note that only one of the image fields is loaded, the default being teaser_image.


